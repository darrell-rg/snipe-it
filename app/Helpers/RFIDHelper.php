<?php

namespace App\Helpers;

use App\Models\Setting;
use Image;

use RuntimeException;
// This is the PHP for the 6x6 Label
use Illuminate\Support\Facades\Log;

class RFIDHelper
{


    public static function floatStringToByte($str,$resolution=0.125) {

        //res is 0.125
        if(strlen($str)<1)
            return 0;
        $f = (float)filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $f_scaled = ($f+0.0000000001) / $resolution;

        // $i =  intval(round($f_scaled,0,PHP_ROUND_HALF_UP));
        $i =  intval($f_scaled);
        //Log::warning("f is ".$f." f_scaled is".$f_scaled." byte is ".dechex($i));

        if($i >= 255)
            return 255;
        if($i < 0)
            return 0;

       
        return $i;

    }

    public static function setUnitsBits($str,$byte=0) {
        //Nominal in/ft = 00
        //Actual in/ft  = 01
        //Nominal mm/m = 10
        //Actual mm/km  =11

        //first clear out the bits we want to set
        $byte = $byte & 0b0011_1111;

        if(str_contains($str,'mm/ft'))
            $byte = $byte | 0b1000_0000;
        
        if(str_contains($str,'Actual'))
            $byte = $byte | 0b0100_0000;

        return $byte;

    }

    public static function gradePithMoistureToByte($grade,$pith,$moisture) {
        //first clear out everything
        $byte = 0b0000_0000;

        $grade = strtoupper($grade);
        // Not Graded is 00
        if(str_contains($grade,'NOT GRADED'))
            $byte = $byte | 0b0000_0000;
        if(str_contains($grade,'#1'))
            $byte = $byte | 0b0100_0000;
        if(str_contains($grade,'#2'))
            $byte = $byte | 0b1000_0000;
        if(str_contains($grade,'#3'))
            $byte = $byte | 0b1100_0000;
        if(str_contains($grade,'BTR'))
            $byte = $byte | 0b0001_0000;
        if(str_contains($grade,'SS'))
            $byte = $byte | 0b0010_0000;


        //pith not specifed is 00
        //FOHC = 01
        if(str_contains($pith,'FOHC'))
            $byte = $byte | 0b0000_0100;
        //BHC = 10
        if(str_contains($pith,'BHC'))
            $byte = $byte | 0b0000_1000;
        //SC = 11
        if(str_contains($pith,'SC'))
            $byte = $byte | 0b0000_1100;


        //GRN is 00 = >19%
        //DRY is 01 = <19%
        if(str_contains($moisture,'DRY'))
            $byte = $byte | 0b0000_0001;
        //KD is 10 = <19%
        if(str_contains($moisture,'KD'))
            $byte = $byte | 0b0000_0010;
        //MC15 = <15%
        if(str_contains($moisture,'15'))
            $byte = $byte | 0b0000_0011;

        return $byte;
    }

    public static function get96BitEPC($asset) {
        // This is the PHP for the 6x6 Label

        // SGTIN-96 standard for RFID data
        // https://docs.zebra.com/content/tcm/us/en/printers/software/zebra-zpl-ii,-zbi-2,-set-get-do,-mirror,-wml-programming-guide/c-zpl-rfid-zpl-rfid-commands/r-zpl-rfid-rb.html
        //https://awc.org/codes-and-standards/weights-and-measurement/

        // Serialised Global Trade Item Number (SGTIN)
        // The Serialised Global Trade Item Number EPC scheme is used to assign a unique identity to an
        // instance of a trade item, such as a specific instance of a product or SKU.
        // urn:epc:id:sgtin:CompanyPrefix.ItemRefAndIndicator.SerialNumber

        // SGTIN-96
        // Header   8 bits 
        // Filter Value 3 bits
        // Partition 3 bits
        // Company Prefix Index 20-40 bits
        // Item Reference 24 bits
        // Serial Number 38 bits

        // Global Location Number With or Without Extension (SGLN)
        // The SGLN EPC scheme is used to assign a unique identity to a physical location, such as a specific
        // building or a specific unit of shelving within a warehouse.
        // General syntax:
        // urn:epc:id:sgln:CompanyPrefix.LocationReference.Extension

        // BYTE 0: Version,  0x01 to 0x2B are not used/reserved, at 0x2C GDTI-96 starts
        // BYTE 1: 1bit 0=inch/1=mm 1bit 0=nominal dims 1=actual dims  6bit Species_ID = 64 possible species
        // BYTE 2: W in 1/8 Inches  = 32" max W
        // BYTE 3: H in 1/8 Inches  = 32" max H
        // BYTE 4: L in 1/4 Feet    = 64' max len 
        // BYTE 5: 4bit Grade = 16 grades + 2bit pith = 4 pith +  2bit moisture = 8 moistures
        // BYTE 6-8 : sawpit instance number  16 bits = 65536
        // BYTE 8-12 : Serial number (itemID) 32 bits = 4,294,967,296 
        // ZPL ^RB to set up SIGTIN 
        // https://docs.zebra.com/content/tcm/us/en/printers/software/zebra-zpl-ii,-zbi-2,-set-get-do,-mirror,-wml-programming-guide/c-zpl-rfid-zpl-rfid-commands/r-zpl-rfid-rb.html

        $rfidSerial = $asset->serial;

        $byte_0 = 0x20; //version 
        $byte_1 = RFIDHelper::setUnitsBits($asset->_snipeit_units_23,$asset->model->manufacturer->id);
        //assuming inches for now
        $byte_2 = RFIDHelper::floatStringToByte($asset->_snipeit_thickness_4,0.125);
        $byte_3 = RFIDHelper::floatStringToByte($asset->_snipeit_width_5,0.125);
        $byte_4 = RFIDHelper::floatStringToByte($asset->_snipeit_length_7,0.25);

        //Log::warning("Length byte is 0x".bin2hex($byte_4));

        $grade = $asset->_snipeit_grade_2;
        $pith = $asset->_snipeit_pith_18;  
        $moisture = $asset->_snipeit_moisture_3;

        $byte_5 =  RFIDHelper::gradePithMoistureToByte($grade,$pith,$moisture);

        $byte_6_7 = 0x0000;  //0 = testing instance, 1=RMJ instance

        if(str_contains(config('app.url'),"rmj.sawpit.app"))
            $byte_6_7 = 0x0001;  //0 = testing instance, 1=RMJ instance

        $byte_8_11 = $asset->id;
    
        //use big-endian
        $binarydata = pack("CCCCCCnN", $byte_0,$byte_1,$byte_2,$byte_3,$byte_4,$byte_5,$byte_6_7,$byte_8_11);

        return $binarydata;

    }


    public static function getRFIDHexString($asset) {
        $binarydata = RFIDHelper::get96BitEPC($asset);

        // $epcAsHexStr = sprintf('%12X', $binarydata );
        $epcAsHexStr = strtoupper(bin2hex($binarydata));
        //Log::warning("EPC is  ".$epcAsHexStr);
        return $epcAsHexStr;
    }


    public static function formatEPC($str) {
        // makes the string more readable. 
        $epcAsHexStr =  chunk_split(strtoupper($str), 2, ' ');

        return "EPC: ".$epcAsHexStr;
    }

}




class int_helper
{
    public static function int8($i) {
        return is_int($i) ? pack("c", $i) : unpack("c", $i)[1];
    }

    public static function uInt8($i) {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    public static function int16($i) {
        return is_int($i) ? pack("s", $i) : unpack("s", $i)[1];
    }

    public static function uInt16($i, $endianness=false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("n", $i);
        }
        else if ($endianness === false) {  // little-endian
            $i = $f("v", $i);
        }
        else if ($endianness === null) {  // machine byte order
            $i = $f("S", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }

    public static function int32($i) {
        return is_int($i) ? pack("l", $i) : unpack("l", $i)[1];
    }

    public static function uInt32($i, $endianness=false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("N", $i);
        }
        else if ($endianness === false) {  // little-endian
            $i = $f("V", $i);
        }
        else if ($endianness === null) {  // machine byte order
            $i = $f("L", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }

    public static function int64($i) {
        return is_int($i) ? pack("q", $i) : unpack("q", $i)[1];
    }

    public static function uInt64($i, $endianness=false) {
        $f = is_int($i) ? "pack" : "unpack";

        if ($endianness === true) {  // big-endian
            $i = $f("J", $i);
        }
        else if ($endianness === false) {  // little-endian
            $i = $f("P", $i);
        }
        else if ($endianness === null) {  // machine byte order
            $i = $f("Q", $i);
        }

        return is_array($i) ? $i[1] : $i;
    }
}