<?php

$qr = "rmj.sawpit.app/hardware/$asset->id";
$topLine = $asset->model->name;  // "DF-8x18x24"
//moving FOHC to the end of the grade line since it makes the top line too long for 4x2 label
$topLine = str_replace(' ','',$topLine);
$topLine = str_replace('-FOHC-','-',$topLine);
$topLine = str_replace('-HC-','-',$topLine);
$topLineLen = strlen($topLine);


//font size 90 can fit 12 chars
//font size 80 can fit 14 chars
//font size 70 can fit 16 chars
$topLineFont = "A,90";
if ($topLineLen<12){
    $topLine = str_pad($topLine, 13, " ", STR_PAD_BOTH);
}
if ($topLineLen>12){
    $topLineFont = "A,90";
}
if ($topLineLen>14){
    $topLineFont = "A,80";
}
if ($topLineLen>16){
    $topLineFont = "A,70";
}
if ($topLineLen>18){
    $topLineFont = "A,60";
}

$sup = $asset->supplier->name;
$or = $asset->order_number;
$dt = Helper::getFormattedDateObject($asset->purchase_date, 'date', false);//"2022-14-07";
$gr = $asset->_snipeit_grade_2;  
if (str_contains($asset->model->name,'FOHC')) {
    $gr = "$gr FOHC";
}
else {
    $gr = "$gr HC";
}
$con = $asset->_snipeit_condition_9;
$bc = $asset->asset_tag;//$asset->serials[1];
$barcodeWidth = "4";
$barcodeRatio = "3";
//width 4 fits up to 15 chars    0123456789ABCDE
if (strlen($bc)>15){
    $barcodeWidth = "3";   
}
if((strlen($bc)<15)){
    $bc = str_pad($bc, 15, " ", STR_PAD_RIGHT);;   
}

$zpl = <<<EOD
^XA
^FX Top section with model info
^CF$topLineFont
^FO5,5^FD$topLine^FS
^FX order info
^CFA,30
^FO220,105^FD SUP: $sup^FS
^FO220,135^FD OR#: $or^FS
^FO220,165^FD  DT: $dt^FS
^FO220,195^FD  GR: $gr^FS
^FO220,225^FD CON: $con^FS
^FX QR code mag=6, errorCorrection=H (highest reliability) or Q (high reliability)  input mode A
^FX QR H needs mag 6 and Q needs mag 7 to fill empty space
^FO10,100^BQ,,6^FDHA,$qr^FS
^FX right staple box
^CFA,15
^FO620,95^GB190,190,3^FS
^FO670,170^FDStaple^FS
^FO670,190^FD Here^FS
^FX Bottom section with 1-D bar code 
^FX 
^BY$barcodeWidth,$barcodeRatio,100
^FO8,320^BCN,100,Y,Y,Y,N^FD$bc^FS
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
    <pre style="text-align:left;display:none">
{{$zpl}}
    </pre>
</div>

