@include('partials.zebraClient')

<?php
//this is for the 4x2 label
$qr = config('app.url');
$qr = "$qr/hardware/$asset->id";
// errorCorrection=H (highest reliability) or Q (high reliability)  input mode A
$qrMode = "QA";
//H needs mag 5 and Q needs mag 6 to fill empty space
//TODO: figure out maxium length of qr, make qrMag smaller if qr code string is too long
$qrMag = "6";
$modelName = explode("-",$asset->model->name);
$topLine = $modelName[0].'-'.$asset->name;  // "DF-8x18x23"
//moving FOHC to the end of the grade line since it makes the top line too long for 4x2 label

$topLineLen = strlen($topLine);

//font A size 90 can fit 12 chars
$topLineFont = "A,90";
if ($topLineLen<12){
    $topLine = str_pad($topLine, 13, " ", STR_PAD_BOTH);
}
if ($topLineLen>12){
    //font size 80 can fit 14 chars
    $topLineFont = "A,80";
}
if ($topLineLen>14){
    //font size 70 can fit 16 chars
    $topLineFont = "A,70";
}
if ($topLineLen>16){
    //font size 60 can fit 18 chars
    $topLineFont = "A,60";
}

$sup = $asset->supplier->name;
$or = $asset->order_number;
$dt = Helper::getFormattedDateObject($asset->purchase_date, 'date', false);//"2022-14-07";
$gr = $asset->_snipeit_grade_2 .' '. $asset->model->model_number; //model_no is BHC or FOHC
$con = $asset->_snipeit_condition_9;
$bc = $asset->asset_tag;//$asset->serials[1];
$barcodeWidth = "3";
$barcodeRatio = "2";


// dr = row['Dryness']
//     if(dr.startswith('G')):
//         dr = 'GR'
//     if(dr.startswith('A')):
//         dr = 'AD'
//     if(dr.startswith('K')):
//         dr = 'KD'
//     return dr
//width 4 fits up to 15 chars    0123456789ABCDE
if (strlen($bc)>15){
    $barcodeWidth = "3";   
}
if((strlen($bc)<15)){
    $bc = str_pad($bc, 15, " ", STR_PAD_RIGHT);;   
}

$zpl = <<<EOD
^XA
^FX POI will flip 180, so that the label will print on top of the rfid inlay
^PON
^FX Top section, designed for 203 dpi (8dpmm)
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
^FX Q H needs mag 6 and Q needs mag 7 to fill empty space
^FO10,100^BQ,,$qrMag^FD$qrMode,$qr^FS
^FX right staple box
^CFA,15
^FO620,95^GB190,190,3^FS
^FO670,170^FDStaple^FS
^FO670,190^FD Here^FS
^FX Bottom section with 1-D bar code 
^FX 
^BY$barcodeWidth,$barcodeRatio,100
^FO100,320^BCN,100,Y,Y,Y,N^FD$bc^FS
^FX read the RFID into field 2
^RT2,0,1,1^FS
^FX print the rfid data to the bottom of the label
^FO20,900^A0N,60^FN2^FS
^FX Write the RFID in hex 96 bits is 12 bytes
^RFW,A^FDDARRELL37337^FS
^FX read the RFID into field 3
^RT3,0,1,1^FS
^FX print the rfid data to the bottom of the label
^FO20,1000^A0N,60^FN3^FS
^FX print field 3 to the data out port(normaly telent 9100)
^HV3,16,TAGNO = ^FS
^XZ
EOD;

//remove comments and newlines
$lines = explode("\n",$zpl);
$a = array_filter($lines, function ($x) { return ! str_starts_with($x,'^FX'); });
$singleLineZpl = rawUrlencode(implode('',$a));  //use rawUrlencode so spaces do not get changed into + 
$dpmm = "8dpmm"; //8dpmm is 203dpi, can also use 12 dpmm (300 dpi) 
$apiUrl = "https://api.labelary.com/v1/printers/$dpmm/labels/4x2/0/$singleLineZpl";
$exampleUrl = "/img/exampleZebraLabel4x2.png";
//api.labelary.com makes a png with 2px per dot so the png is 812x406 
//The factors of 406 are 1, 2, 7, 14, 29, 58, 203, 406. Pick a factor so preview is not blurry
$padding = 0;
$height = 203+($padding*2);
$width = 2*$height;
//height="{{$height}}" width="{{$width}}" 
//border:1px solid #ddd;border-radius:4px"

$result = '';
//$client = new Client('192.168.10.77');
// $client->send($zpl);
// $result = $client->read();
// unset($client);
?>

<div class="col-md-12" style="padding-top: 5px;" >
<img src="{{$apiUrl}}" class="img-thumbnail" 
alt="Zebra Label preview for {{ $asset->getDisplayNameAttribute() }}">
{{--hidden pre tag for debugging zpl--}}  
@if(env('APP_ENV')=='development') 
<pre style="text-align:left">
{{$result}}

{{$zpl}}
</pre>
@endif



</div>