# Sawpit 

Sawpit is an asset tracking system for tracking wooden beams  

-----

## Mapping
Sawpit started as a laptop tracker (Snipe-IT) and we have turned it into a lumber tracker.  Because of this, there are some fields that have been re-purposed.
Eventually we may want to use the localization to rename the user displayed field


| RMJ Term           | Sawpit Term          |           notes                           |
| ----------         | ------------------   |   ----------------------------            |
| Inventory          | Company              |       owner of the log                    |
| Manufacturer       | Species              |                                           |
| Summary Name       | Model                |   Model should be interchangeable with any other item of the same type |
| WxHxL              | Asset Name           |  dimensions for quick searching             |
| Srl Old            | Serial               |                                             |
| Srl New            | Asset Tag            |                                             |
| Beam               | Asset                |    Each beam is unique                      |
 


-----

### Installation

Start with a blank ubuntu 22.04 machine. Then run the install script as in the instructions [installation manual](https://snipe-it.readme.io/docs/downloading#3-download-the-installer).   Make sure you use urls that point at this fork to get the customized version.

-----
### User's Manual
For help using , check out the [user's manual](https://snipe-it.readme.io/docs/overview).


-----

### Upgrading

Push your changes to this branch and run upgrade.php, see the [upgrading documentation](https://snipe-it.readme.io/docs/upgrading).

----

## RFID

RFID support plan.  USA RFID is 902-928 MHz  	52 channels of 500 kHz	4 W EIRP  https://rfid4u.com/rfid-regulations/

915mhz center freq is 33cm or 13 inches

The downside of RFID vs barcode is that with RFID you never know exactly which beam you scanned.  You could point it at one beam and actually scan a different beam in the stack. The barcode laser ensures the worker knows exactly which beam was scanned.    For workers doing manual scans, barcode is better.
   

Where RFID is good is for area scans, since it can scan many tags at once. I think the solution is to mount RFID scanners with GPS on the forklifts.   As the forklift drives around the RFID scanner will pick up various tags.  Every time the forklift sees a tag, it sends the current GPS coordinates and tagID to the sawpit.app server.   Sawpit.app keeps track of the last known GPS coordinates where each beam was seen.   If beams are always moved by forklift this automatically keeps track of where they are without the workers having to do anything.

Seeonic SightWare has a 4 port device with cellular and gps ready to go. Price on request.

## Labels

We want the biggest we can fit so that they are easy to read at night and there is room for staples without killing the rfid or barcode.  The inlay should be small enough so we can put it behind a barcode and then the the barcode will serve as the keep-out area for staples. 


Most likely 4x2 or 3x1.  Z-Perform 1500T is paper, not rated for outdoors but seems to be all that is availble with rfid on  the zebra stock price list.  Zebra ZBR2000 RFID inlay will work with 4x2, the size is 3.74x0.31 

Thermal Transfer RFID Labels - pair with Zebra's 6000 wax or 3200 wax/resin ribbon
4 x 2 10033971 X ZBR2000 UCODE 8 Z-Perform 1500T 500 2 3 lbs. X 78.39 $ 0.157 + .02 for ribion = $0.18 per label
Stores 128bit/16 char user serial + 48bit/6char TID
05586GS11007 - ZEBRA 4.33" X 243' 5586 WAX/RESIN RIBBON (CASE)  $85.61 per case, 12 

Z-Xtreme 4000T High-Tack Up to 3 years outdoors would be good but may have to get custom printed. 



## Costs

Cost for 25k item inventory:

Startup costs estimates:
    1 SightWare-FT1-FCC to mount on forklift = $5000 ??? waiting on quote 
    25k rfid labels at $0.2 ea = $5000
    5 ipads or laptops       = $5000  -- already have?
    3 Zebra ZD621R label printers = $6000 (get wifi and 300 dpi)
    5 bluetooth barcode readers for tables = $500
    5 usb barcode readers for computers = $100
    3 umbrella carts with YETI500X for power  = $2000
        Cart holds yeti battery, trashcan, computer, printer,  keyboard, staple gun
        Umbrella or something to shade screen/ protect from rain 
        https://www.homedepot.com/p/Husky-2-Tier-Plastic-4-Wheeled-Service-Cart-in-Black-12603/205736982



    Total: @25k startup cost estimate


Monthly recurring costs estimates:
    Sawpit.app Software as a service  $0.10 item/month  = $2500 
        Includes backups, hosting, maintenance, security updates
        Includes 10hrs of feature work/support a month
        Archived items cost zero

    Sim card for SightWare = $100?


    Total Monthly:  $2600 


## Implenting:

1. Software 
    Import existing inv from spreadsheets
    - should model include length
    - create fast entry page for when a truck comes in 

1. Hardware
    - set up computer carts with printer, barcode scanner, rfid reader

1. Define locations,  set out labled orange cones 
1. For each beam in yard, find imported
    - find the imported record, or create a new record
    - print a new RFID label
    - attach new label
    - set location


1. use Bulk audit to move beams location
1. use bulk edit to set status




1. Set up computer carts
