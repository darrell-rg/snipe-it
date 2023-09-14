/*
 * Author: Darrell Taylor
 * Date:  2023-09-08
 * Description:
 *      Plotting GPS locations of assets on a map 
 **/

import Map from 'ol/Map.js';
import Static from 'ol/source/ImageStatic.js';
import View from 'ol/View.js';
import { Image as ImageLayer, Tile as TileLayer, Vector as VectorLayer } from 'ol/layer.js';
import { getCenter } from 'ol/extent.js';
import { transform, fromLonLat, transformExtent, get as getProjection, } from 'ol/proj.js';
import { OSM, Vector as VectorSource } from 'ol/source.js';
import { Point } from 'ol/geom';
import Feature from 'ol/Feature';
import { Circle as CircleStyle, Stroke, Style, Icon, Fill } from 'ol/style.js';
import { easeOut } from 'ol/easing.js';
import { getVectorContext } from 'ol/render.js';
import { unByKey } from 'ol/Observable.js';
import { Control, defaults as defaultControls } from 'ol/control.js';
import Geolocation from 'ol/Geolocation.js';

// import bootstrap

//EPSG:4326 is  WGS 84 --= WGS84 - World Geodetic System 1984, used in GPS
//EPSG:3857 is WGS 84 / Pseudo-Mercator -- Spherical Mercator, Google Maps, OpenStreetMap, Bing, ArcGIS, ESRI
const webMercatorProj = getProjection("EPSG:3857");
const gpsProjection = getProjection("EPSG:4326");
function lngLatToWebMercator(lngLat) {
  return transform(lngLat, gpsProjection, webMercatorProj)
}

window.getViewExtent = function () {
  const extent = olMap.getView().calculateExtent(olMap.getSize())
  return extent;
}

function scale(a) {
  //this is to align the map
  const x1shift = 17.0
  const y1shift = -5.0
  const x2shift = -2.0
  const y2shift = -5.0
  return [a[0] + x1shift, a[1] + y1shift, a[2] + x2shift, a[3] + y2shift];
}
// Extent of /rmjolMap.webp
//topleft is 2nd diag rock in field  39.99126088233365, -105.07481263082146
//bottom right corner of field is 39.988670996411216, -105.07148774841838
//NOTE extents in web mercator are mirrored so it actualy bottom left, top right
let topLeft = [-105.07148774841838, 39.99126088233365];
let bottomRight = [-105.0748126308214, 39.988670996411216];

// 0.0001 is about 5 meters
let itemOffset = [0.0001, 0.0003];
let itemLocation = [-105.0721 + itemOffset[0], 39.98995 + itemOffset[1]];

function updateLocation(geometry = null) {
  const noteSelector = "#_snipeit_last_gps_16";
  let value = window.lastGpsString;
  if (value) {
    let gpsLoc = value.replace('@', '').split(',').reverse();

    let newLoc = lngLatToWebMercator(gpsLoc);
    //  itemLocation = [itemLocation[0]+itemOffset[0],itemLocation[1]+itemOffset[1]];
    console.log("got  latLng from lastGpsString", gpsLoc, newLoc)

    if (geometry)
      itemLocationGeom.setCoordinates(newLoc);

    return gpsLoc;

  }
  else {
    console.log("did not get  lastGpsString")
  }
  return [0, 0];
}

let imageExtentDeg = [bottomRight[0], bottomRight[1], topLeft[0], topLeft[1]];
let webMercatorCenter = lngLatToWebMercator(getCenter(imageExtentDeg))
const imageExtentWebMercator = scale(transformExtent(imageExtentDeg, 'EPSG:4326', 'EPSG:3857'));

if (window.lastGpsString) {
  //center on item if we have a loc for it
  itemLocation = updateLocation();
  webMercatorCenter = lngLatToWebMercator(itemLocation);
}


let imgUrl = "/img/rmjMap.jpg";
const imageLayer = new ImageLayer({
  source: new Static({
    url: imgUrl,
    imageExtent: imageExtentWebMercator
  }),
  opacity: 0.8
});

const tileLayer = new TileLayer({
  source: new OSM({
    wrapX: false,
    opacity: 1.0
  }),
});


const itemLocationGeom = new Point(fromLonLat(itemLocation));
const itemPosFeature =new Feature(itemLocationGeom);
itemPosFeature.setStyle(
  new Style({
    image: new CircleStyle({
      radius: 6,
      fill: new Fill({
        color: 'Red',
      }),
      stroke: new Stroke({
        color: '#fff',
        width: 2,
      }),
    }),
  })
);


const myPositionFeature = new Feature();
myPositionFeature.setStyle(
  new Style({
    image: new CircleStyle({
      radius: 6,
      fill: new Fill({
        color: '#3399CC',
      }),
      stroke: new Stroke({
        color: '#fff',
        width: 2,
      }),
    }),
  })
);

const accuracyFeature = new Feature();

const vectorLayer = new VectorLayer({
  source: new VectorSource({
    features: [itemPosFeature,accuracyFeature, myPositionFeature],
  }),
});

function el(id) {
  return document.getElementById(id);
}

const geolocation = new Geolocation({
  // enableHighAccuracy must be set to true to have the heading value.
  trackingOptions: {
    enableHighAccuracy: true,
  },
  gpsProjection
});


const olMap = new Map({
  layers: [
    tileLayer,
    imageLayer,
    vectorLayer
  ],
  target: 'map',
  view: new View({
    center: webMercatorCenter,
    zoom: 18,
    enableRotation: false
    // projection: webMercatorProj,
  }),
  controls: defaultControls({
    attribution: false,
    zoom: false,
    rotate: false
  }),
});

window.olMap = olMap;


function encodePosition(coords) {
  console.log(coords)
  return "@"+coords[1]+","+coords[0]
}


function addGpsListeners(){
  console.log("adding gps listeners")
  let needsViewFit = true;

  el('gpstrack').addEventListener('change', function () {
    console.log("starting gps tracking")
    geolocation.setTracking(this.checked);
    //needsViewFit = this.checked;
  });
  
  // update the HTML page when the position changes.
  geolocation.on('change', function () {
    el('accuracy').innerText = geolocation.getAccuracy() + ' [m]';
    // el('altitude').innerText = geolocation.getAltitude() + ' [m]';
    el('heading').innerText = geolocation.getHeading() + ' [rad]';
    el('gpspos').innerText = encodePosition(geolocation.getPosition())+ ' [m/s]';
  });
  
  // handle geolocation error.
  geolocation.on('error', function (error) {
    console.log(error.message);
    const info = document.getElementById('gpsinfo');
    info.innerHTML = error.message;
    info.style.display = '';
  });
  
  geolocation.on('change:accuracyGeometry', function () {
    accuracyFeature.setGeometry(geolocation.getAccuracyGeometry());
  });

  geolocation.on('change:position', function () {
    const coordinates = geolocation.getPosition();
    const myPosGeom = coordinates ? new Point(fromLonLat(coordinates)) : null;
    myPositionFeature.setGeometry(myPosGeom);
    flash(myPositionFeature, 1)
    if (needsViewFit && myPosGeom)
    {
      var padding = [20, 20, 20, 20];
      olMap.getView().fit(vectorLayer.getSource().getExtent(), {
        padding: padding,
      });
      needsViewFit = false;
    }

  });
  
}


const duration = 3000;
function flash(feature, repeats = 10) {
  let start = Date.now();
  const flashGeom = feature.getGeometry().clone();
  const listenerKey = tileLayer.on('postrender', animate);
  //   console.log("flashing")
  function animate(event) {
    const frameState = event.frameState;
    const elapsed = frameState.time - start;
    if (elapsed >= duration * repeats) {
      unByKey(listenerKey);
      return;
    }
    const vectorContext = getVectorContext(event);
    const elapsedRatio = (elapsed % duration) / duration;
    // radius will be 5 at start and 30 at end.
    const radius = easeOut(elapsedRatio) * 25 + 5;
    const opacity = easeOut(1 - elapsedRatio);
    //console.log("animateing radius= ",elapsedRatio,radius)
    const style = new Style({
      image: new CircleStyle({
        radius: radius,
        stroke: new Stroke({
          color: 'rgba(255, 0, 0, ' + opacity + ')',
          width: 0.25 + opacity,
        }),
      }),
    });

    vectorContext.setStyle(style);
    vectorContext.drawGeometry(flashGeom);
    // tell OpenLayers to continue postrender animation
    olMap.render();
  }
}




function getIconFeature(lonLat) {
  const xMarkImg = '/img/xMarker.png'
  return new Feature({
    geometry: new Point(itemLocation),
    name: 'Asset',
    population: 4000,
    rainfall: 500,
    style: new Style({
      image: new Icon({
        anchor: [0.5, 0.5],
        anchorXUnits: 'fraction',
        anchorYUnits: 'fraction',
        src: xMarkImg
      }),
    })
  });
}
flash(itemPosFeature, 1)

//this is for updating location from lastgps field
//window.setInterval(updateLocation, 5000), geom;
addGpsListeners()
//window.setTimeout(addGpsListeners,2000);
// const staticImgExtent = imageLayer.getSource().getImageExtent()
// console.log("static img extent =",staticImgExtent)