
<script nonce="{{ csrf_token() }}">
    let noteSelector = "#note";
    let mapLinkSelector = "#map-link";
    let gpsWatch = null;
    let showAccuracy = false;
    function makeMapLink(position) {
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        const acc = Math.ceil(position.coords.accuracy)
        const mapLink = document.querySelector(mapLinkSelector);
        if(mapLink)
        {
            mapLink.href = "";
            mapLink.textContent = "";
            mapLink.href = `https://www.openstreetmap.org/#map=18/${latitude}/${longitude}`;
            mapLink.textContent = `Show @${latitude}°,${longitude}° on Map.`;
        }

    }
    function encodePosition(position) {
        return "@"+position.coords.latitude+","+position.coords.longitude
    }
    function handlePositionUpdate(position,note_el) {
        var note_el = document.querySelector(noteSelector);
        const acc = Math.ceil(position.coords.accuracy);
        var noteString = encodePosition(position);
        if(showAccuracy)
            noteString += "\n Accuracy:"+acc+"m";  // 95% confidence level,meters
        note_el.value = noteString;
        makeMapLink(position);
    }
    function handlePositionError(e) {
        const mapLink = document.querySelector(mapLinkSelector);
        if(mapLink){
            mapLink.href = "";
             mapLink.textContent = `GPS Error: ${E}`;
        }
        console.log(e);
    }
    function requestPosition(){
        //get one gps fix
        const options = {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0,
        };
        //Dummy one, which will result in a working next statement. this is safari bug workaround
        navigator.geolocation.getCurrentPosition(function () {}, function () {}, {});
        //The working next statement.
        navigator.geolocation.getCurrentPosition(handlePositionUpdate, handlePositionError, options);
    }

    function startGpsWatcher(note="#note", mapLink="#map-link"){
        mapLinkSelector = mapLink;
        if (navigator.geolocation) {
            const options = {
                enableHighAccuracy: false,
                timeout: 5000,
                maximumAge: 5000,
            };
            gpsWatch = navigator.geolocation.watchPosition(handlePositionUpdate, handlePositionError, options);
        } else {
            handlePositionError("GPS not found, try enabling location access in browser settings.")
        }
        console.log("Gps watcher started")
    }

    @if (isset($noteSelector))
        noteSelector = '{{$noteSelector}}';
    @endif

    @if (isset($mapLinkSelector))
        mapLinkSelector = '{{$mapLinkSelector}}';
    @endif

    @if (isset($startGpsWatcher))
        $(document).ready(function(){
            startGpsWatcher()
        })
    @endif


    @if (isset($addClickToSetGpsLink))
        //add link below custom field in create/edit
        noteSelector = "#_snipeit_last_gps_16";
        showAccuracy = false;
        let el = document.querySelector(noteSelector+" + p");
        if(el){
            el.addEventListener("click", requestPosition);
        }
    @endif


    @if (isset($loadMap))
        @if($asset->id)
            //devine map from item id
            var index = {{$asset->id}};
            var lastGpsString = '{{$asset->_snipeit_last_gps_16}}';

            //only show map if we have gps data
            if (lastGpsString.length > 4 ){
                window.lastGpsValue=lastGpsString;
                // const map_el = document.getElementById("mapImage");
                // const mapImagelink_el = document.getElementById("mapImagelink");
                // map_el.src = "/img/maps/"+grid+".webp";
                // mapImagelink_el.href = map_el.src;
                // map_el.style = 'max-height:640px;';
                // $("#mapImageDiv").show();
            }
            else{
                // $("#mapImageDiv").hide();
            }

        @endif

    @endif


</script>
@if (isset($loadMap))
<script type="module" src="{{ url('js/build/map.js') }}" nonce="{{ csrf_token() }}"></script>
@endif    