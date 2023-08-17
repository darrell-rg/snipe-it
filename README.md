# Sawpit 

Sawpit is an asset tracking system for tracking wooden beams at 

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

RFID support is TBD.  

The downside of RFID vs barcode is that with RFID you never know exactly which beam you scanned.  You could point it at one beam and actually scan a different beam in the stack. The barcode laser ensures the worker knows exactly which beam was scanned.    For workers doing manual scans, barcode is better.
   

Where RFID is good is for area scans, since it can scan many tags at once. I think the solution is to mount RFID scanners with GPS on the forklifts.   As the forklift drives around the RFID scanner will pick up various tags.  Every time the forklift sees a tag, it sends the current GPS coordinates and tagID to the sawpit.app server.   Sawpit.app keeps track of the last known GPS coordinates where each beam was seen.   If beams are always moved by forklift this automatically keeps track of where they are without the workers having to do anything.


## Labels

We want the biggest we can fit so that they are easy to read at night and there is room for staples without killing the rfid or barcode.


Most likely 4x2 or 3x1.  Z-Perform 1500T is paper, not rated for outdoors but seems to be all that is availble with rfid on  the zebra stock price list.

Thermal Transfer RFID Labels - pair with Zebra's 6000 wax or 3200 wax/resin ribbon
4 x 2 10033971 X ZBR2000 UCODE 8 Z-Perform 1500T 500 2 3 lbs. X 78.39 $ 0.157 + .02 for ribion = $0.18 per label
Stores 128bit/16 char user serial + 48bit/6char TID
05586GS11007 - ZEBRA 4.33" X 243' 5586 WAX/RESIN RIBBON (CASE)  $85.61 per case, 12 



Z-Xtreme 4000T High-Tack Up to 3 years outdoors would be good but may have to get custom printed. 