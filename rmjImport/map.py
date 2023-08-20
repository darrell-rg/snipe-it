""" Defines images processing tasks """

# designed for pillow 10

from PIL import Image, ImageFilter, ImageCms, ImageFont, ImageDraw
from PIL.ExifTags import TAGS, GPSTAGS

from PIL import ExifTags
import numpy as np
import csv
import datetime
import math

import pprint
pp = pprint.PrettyPrinter(indent=4)  
current_time = datetime.datetime.now()



def decimal_gps_to_exif(decimal):
    
    # Define the GPS position
    lat = (decimal[0], 0, 0)  # latitude in degrees, minutes, seconds
    lon = (decimal[1], 0, 0)  # longitude in degrees, minutes, seconds

    # Convert the GPS coordinates to rational format
    lat_rational = np.array(lat, dtype=np.uint32) * [1e6, 60, 3600]
    lon_rational = np.array(lon, dtype=np.uint32) * [1e6, 60, 3600]

    # Create the GPS tag data
    gps_data = {
        0x0002: 'GPSLatitude',
        0x0004: 'GPSLongitude',
        0x0001: 'GPSLatitudeRef',
        0x0003: 'GPSLongitudeRef',
        0x0005: 'GPSAltitudeRef',
        0x0006: 'GPSAltitude',
        0x0007: 'GPSTimeStamp',
        0x0011: 'GPSDOP',
    }
    gps_data[0x0002] = lat_rational.tolist()
    gps_data[0x0004] = lon_rational.tolist()
    gps_data[0x0001] = 'N' if lat[0] >= 0 else 'S'
    gps_data[0x0003] = 'E' if lon[0] >= 0 else 'W'

    #TODO: add map datum
    data = {'GPSLatitudeRef': gps_data[0x0001],
    'GPSLatitude': gps_data[0x0002],
    'GPSLongitudeRef': gps_data[0x0003],
    'GPSLongitude': gps_data[0x0004]
    }

    return data


def get_decimal_from_dms(dms, ref):
    degrees = dms[0][0] / dms[0][1]
    minutes = dms[1][0] / dms[1][1] / 60.0
    seconds = dms[2][0] / dms[2][1] / 3600.0

    if ref in ['S', 'W']:
        degrees = -degrees
        minutes = -minutes
        seconds = -seconds

    return round(degrees + minutes + seconds, 5)

def get_coordinates(geotags):
    lat = get_decimal_from_dms(geotags['GPSLatitude'], geotags['GPSLatitudeRef'])
    lon = get_decimal_from_dms(geotags['GPSLongitude'], geotags['GPSLongitudeRef'])

    return (lat,lon)


def decdeg2dms(dd):
   is_positive = dd >= 0
   dd = abs(dd)
   minutes,seconds = divmod(dd*3600,60)
   degrees,minutes = divmod(minutes,60)
#    degrees = degrees if is_positive else -degrees
   return (degrees,minutes,seconds)

def createMapImage(map_image, mark_pos=[640, 480], mark_gps=[39.99103771661651, -105.07273998372258], output_file_name = 'AA_64_64.jpg', label=None):
    """Adds X to Image"""
    
    mark = Image.open('xMarker.png')
    markImgWidth, markImgHeight = mark.size
    #the exif offset is the xy pos of the center of x in image
    mark_offset= [math.floor(mark_pos[0] + markImgWidth/2),math.floor(mark_pos[1] + markImgHeight/2)]
    mark_string = f"{mark_offset[0]},{mark_offset[1]}@{mark_gps[0]},{mark_gps[1]}"

    # print("markString=",mark_string)
    target_size = map_image.size
    # create x overlay
    overlay = Image.new('RGBA', target_size, (0, 0, 0, 0))
    #to paste in the overlay, we want the topleft corner of the x image
    mark_offset= [math.floor(mark_pos[0] ),math.floor(mark_pos[1])]
    overlay.paste(mark, mark_offset)

    base = map_image if map_image.mode == 'RGBA' else map_image.convert('RGBA')

    final = Image.alpha_composite(base, overlay)
    final = final if final.mode == 'RGB' else final.convert('RGB')

    exifdict = decimal_gps_to_exif(mark_gps)
    exif = get_jester_exif()
    gps_ifd = exif.get_ifd(ExifTags.IFD.GPSInfo)
    # gps_ifd[ExifTags.GPS.GPSDateStamp]  = "1999:99:99 99:99:99"
    gps_ifd[ExifTags.GPS.GPSLatitudeRef]  = "N"
    gps_ifd[ExifTags.GPS.GPSLatitude]  = decdeg2dms(mark_gps[0])
    gps_ifd[ExifTags.GPS.GPSLongitudeRef]  = "W"
    gps_ifd[ExifTags.GPS.GPSLongitude]  = decdeg2dms(mark_gps[1])
    #TODO: set GPSMapDatum 
    exif[ExifTags.Base.XPTitle]=mark_string

    # pp.pprint(gps_ifd)

    final.save(output_file_name, format='JPEG', quality=75, exif=exif.tobytes())


def generateMapImages(folder="./maps",mapFilename='rmjcMapWithScale.png'):

    #topleft is nw of corner of blue box behind shed
    # formate is y,x
    topleft=[39.99103771661651, -105.07273998372258]
    #bottom right is black square se corner of property
    bottomright=[39.98901792900641, -105.07181577814869]

    x_divisions='ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    y_divisions='ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'

    x_divisions='ABCDEFGH'
    y_divisions='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'

    deg_x_inc = (topleft[1]-bottomright[1]) / len(x_divisions) 
    deg_y_inc =(topleft[0]-bottomright[0]) / len(y_divisions) 

    #rmjcMap.png currently 1119x3190
    mapImg = Image.open(mapFilename)
    mapImgWidth, mapImgHeight = mapImg.size

    pix_x_inc = mapImgWidth/len(x_divisions)
    pix_y_inc = mapImgHeight/len(y_divisions)

    name_gps_tripples=[]

    for xi, x_grid in enumerate(x_divisions):
        for yi, y_grid in enumerate(y_divisions):
            gps_x = topleft[0] - (xi * deg_x_inc)
            gps_y = topleft[1] + (yi * deg_y_inc)
            gps = [gps_x,gps_y]

            mark_x = xi * pix_x_inc
            mark_y = yi * pix_y_inc
            mark_pos = [math.floor(mark_x),math.floor(mark_y)]
            filename = f"{folder}/{x_grid}{y_grid}_{mark_x}_{mark_y}.jpg" 
            filename = f"{folder}/{x_grid}{y_grid}.jpg" 

            print(f"writing {filename} gps = {gps} mark={mark_pos}")
            #https://www.google.com/maps/place/39%C2%B059'25.0%22N+105%C2%B004'21.1%22W/@39.990266,-105.0731717,421m/data=!3m2!1e3!4b1!4m4!3m3!8m2!3d39.990265!4d-105.072528?entry=ttu
            #https://www.google.com/maps/@39.9903441,-105.0730027,421m/data=!3m1!1e3?entry=ttu
            gps_dict = {'filename':filename,'lng':gps_x,'lat':gps_y,'mark_x_pos':mark_x,'mark_y_pos':mark_y}
            name_gps_tripples.append(gps_dict)


            createMapImage(mapImg, mark_pos, gps, filename, label=False)

            # break
    
    
    with open(folder+'/ref.csv', 'w', newline='') as csvfile:
        fieldnames = name_gps_tripples[0].keys()
        writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
        writer.writeheader()
        for row in name_gps_tripples:
            writer.writerow(row)

def print_exif(image_file_path):
    exif_table = {}
    image = Image.open(image_file_path)
    info = image.getexif()
    for tag, value in info.items():
        decoded = TAGS.get(tag, tag)
        exif_table[decoded] = value
    pp.pprint(exif_table)

    # gps_info = {}
    # for key in exif_table['GPSInfo'].keys():
    #     decode = GPSTAGS.get(key,key)
    #     gps_info[decode] = exif_table['GPSInfo'][key]

    # return gps_info    

def print_exif_gps(image_file_path):
    exif_table = {}
    image = Image.open(image_file_path)
    exif = image.getexif()
    pp.pprint(exif)
    gps_ifd = exif.get_ifd(ExifTags.IFD.GPSInfo)
    pp.pprint(gps_ifd)

def get_jester_exif():
    image = Image.open('jester.jpg')
    info = image.getexif()
    for tag, value in info.items():
        decoded = TAGS.get(tag, tag)
        if 'GPS' in decoded:
            continue
        del info[tag]
    return info


def randomizeMaps():
    mapGrids="B4,C1,C6,C7,C8,CA,CG,CH,D2,D3,D4,D5,E2,E3,E4,E5,E6,E9,EC,G5,G8,GA,GB,H1,H6,H7,H8,H9,HA,HB,HC,HD,HE,HF,HG,HL,HI,HH,HJ"
    mapGrids=mapGrids.split(',')

    def getRandMapImg():
        #   'image': 'https://sawpit.app/uploads/assets/asset-image-1092-OYQSyNFi3U.jpg',
        grid = random.choice(mapGrids)
        fn = f"https://sawpit.app/uploads/assets/maps/{grid}.jpg"
        return fn

    def setRandomMapImg(tag):
        existing_id = getID(tag)
        A = snipeit.Assets()
        asset = {'image':ge}
        results = A.updateDevice(server, token, str(existing_id) , json.dumps(asset))
        jsonData = json.loads((results).decode('utf-8').replace("'",'"'))

    InventoryCSVfn = 'Master Inventory - RMJC 4-15-2023 - Inventory Master List.csv'
    sumRegex = re.compile(r"(\w+) - (\w+) - ([0123456789.x]+)")
    #TODO: strip whitespace from column names

    skiprows = 0
    with open(InventoryCSVfn, newline='') as csvfile:
        reader = csv.DictReader(csvfile)
        rowcount = 0
        for row in reader:
            rowcount+=1
            if rowcount < skiprows:
                continue

            if(len(row['SRL_OLD']) < 3):
                print(f"Row {rowcount} is missing SRL_OLD, skipping it")
                continue
            summaryMatch = sumRegex.match(row['Summary Name'])
            if(summaryMatch is None):
                print(f"Row {rowcount} has bad summary {row['Summary Name']}, skipping it")
                continue
            asset = csvToAsset(row)
            uploadAssetIfNotExist(asset)
            if rowcount > 1000:
                break



if __name__ == "__main__":

    generateMapImages()
    # print_exif_gps("jester.jpg")
    # print_exif_gps("jester.jpg")
    print_exif("./maps/AA.jpg")
    print_exif_gps("./maps/AA.jpg")

