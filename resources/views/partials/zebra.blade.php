<?php

$qr = "rmj.sawpit.app/hardware/2366";
$topLine = "DF-8x18x24";  //$asset->_snipeit_lastgpsping_16
$sup = "Delson";
$or = "12374";
$dt = "2022-14-07";
$gr = "#1 FOHC";
$con = "Mold, Wane";
$bc = "ABCDE1234567890";

$zpl = <<<EOD
^XA
^FX Top section with model info
^CFA,90
^FO10,10^FD$topLine^FS
^FX order info
^CFA,30
^FO220,105^FD SUP: $sup^FS
^FO220,135^FD OR#: $or^FS
^FO220,165^FD  DT: $dt^FS
^FO220,195^FD  GR: $gr^FS
^FO220,225^FD CON: $con^FS
^FX Third section with bar code.
^BY4,2,100
^FO10,320^BCN,100,Y,Y,Y,N^FD$bc^FS
^FX QR code mag=6, errorCorrection=H (highest reliability) or Q (high reliability)  input mode A (automated encode mode switching) 
^FO10,10^BQ,,6^FDHA,$qr^FS
^FX right staple box
^CFA,15
^FO620,95^GB190,190,3^FS
^FO670,170^FDStaple^FS
^FO670,190^FD Here^FS
^XZ
EOD;

//remove comments and newlines
$lines = explode("\n",$zpl);
$a = array_filter($lines, function ($x) { return ! str_starts_with($x,'^FX'); });
$singleLineZpl = rawUrlencode(implode('',$a));  //use rawUrlencode so spaces do not get changed into + 


$apiUrl = "https://api.labelary.com/v1/printers/8dpmm/labels/4x2/0/$singleLineZpl";
$exampleUrl = "/img/exampleZebraLabel4x2.png"



?>


<div class="text-center col-md-12" style="padding-bottom: 15px;">
    <a href="{{$apiUrl}}" data-toggle="lightbox" id="labelImageLink">
        <img src="{{$apiUrl}}" class="assetimg img-responsive" alt="label" id="mapImage" style="max-height: 128px;">
    </a>
<pre style="text-align:left;display:all">
{{$zpl}}
</pre>
</div>

