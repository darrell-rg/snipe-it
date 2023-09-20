<?php

namespace App\Helpers;

use App\Models\Setting;
use Image;
use App\Helpers\RFIDHelper;
use RuntimeException;
// This is the PHP for the 6x6 Label
use Illuminate\Support\Facades\Log;

class ZPLHelper
{
    public static function printZpl($asset,$username="",$printerIP = '192.168.10.77') {
        $zpl = self::get4x6ZPL($asset, $username);
        $result = "Printing: ". $asset->id;
        
        // if($_GET["print"]=="test")
            // $zpl = "~JA\n~HI\n~HI\n";

        Log::warning("sending zpl to ".$printerIP);
        //$client = new ZplClient($printerIP,9100);
        $client = ZplClient::printer($printerIP,9100);
        $client->send($zpl);
        //wait a litte while for data to come in
        usleep(100_000);
        //$result = $client->readNormal(30);
        $result = $client->read();
        $client->disconnect();
        //this is a formating hack because printonx zpl can not send newlines in HV
        $result = str_replace("SL4M","\nSL4M",$result);
        $result = str_replace("SL4MTagID=","TagID=",$result);
        $result = str_replace("SL4MEPC=","EPC=",$result);


        return $result;
    }

    
    public static function getPreviewZpl($asset) {

        $zpl = self::get4x6ZPL($asset);
        //these replacemnts are fixes for the lablelry preview
        $previewReplacements = array(
            "^FT542,1178^BQN,2,7" => "^FT542,1218^BQN,2,7" //fix qr code pos
        );

        //remove comments and newlines
        $lines = explode("\n",$zpl);
        $a = array_filter($lines, function ($x) { return ! str_starts_with($x,'^FX'); });
        $a = strtr(implode('',$a),$previewReplacements);
        // $singleLineZpl = rawUrlencode($a);  //use rawUrlencode so spaces do not get changed into + 
        
        return $a;
        
    }

    public static function get4x6ZPL($asset, $username="") {
        // This is the PHP for the 6x6 Label
        $qr = config('app.url');
        $qr = "$qr/hardware/$asset->id";
        $modelName = explode("-",$asset->model->name);
        $heartCenter = $asset->_snipeit_pith_18;  // "FOHC"
        //$heartCenter = $asset->model->model_number;
        $sup = ($asset->supplier) ? $asset->supplier->name : '';
        $or = $asset->order_number;
        $dt = Helper::getFormattedDateObject($asset->purchase_date, 'date', false);//"2022-14-07";
        $grade = $asset->_snipeit_grade_2;
        $con = $asset->_snipeit_condition_9;
        $dry = $asset->_snipeit_moisture_3;
        $bdf = str_contains($asset->_snipeit_bdf_8,'.') ? explode(".",$asset->_snipeit_bdf_8)[0] : $asset->_snipeit_bdf_8;
        $inv = $asset->company->name;
        $bc = $asset->asset_tag;

        //pad the barcode
        if((strlen($bc)<15)){
            $bc = str_pad($bc, 15, " ", STR_PAD_RIGHT);;   
        }


        $rfidSerial = $asset->serial;
        //TODO: check that serial is a valid 12 byte hex String

        if((strlen($rfidSerial)!=24)){
            Log::warning("Found Invalid RFID serial string, generating a new one.");
            $rfidSerial = RFIDHelper::getRFIDHexString($asset); 
        }

        //Change password
        //^RFW,H,P^FD<access password>^FS
        //This command writes and specifies both the access password (12345678) and the kill password
        // (88887777) separated by a comma.
        //^RFW,H,P^FD12345678,88887777^FS
        // The following command locks all memory banks using a previously specified access password.
        //^RLM,L,L,L,L^FS
        $rfidZplCode = <<<EOD
        ^FX Write the RFID in hex 96 bits is 12 bytes
        ^RFW,H^FD$rfidSerial^FS
        ^FX read the RFID EPC into Field3 hex format(H)
        ^FN3^RFR,H,0,1,1^FS
        ^HV3,16,SL4MEPC=^FS
        ^FX Read the 32-bit unique  tag ID into Field05and print 
        ^FN1^RFR,H,0,1,2^FS
        ^FX print field 3 to the data out port(normaly telent 9100)
        ^HV1,,SL4MTagID=^FS
        ^PQ1,0,1,Y^XZ
        EOD;

        //TODO: escape carrot and tilde so txt can not break the label
        //shift QR code 20 dots 
        $replacements = array(
            "Port Orford Cedar" => $asset->model->manufacturer->name,
            "FOHC" => $heartCenter,
            "111" => $asset->_snipeit_thickness_4,
            "222" => $asset->_snipeit_width_5,
            "333" => $asset->_snipeit_length_7,
            "Delson" => $sup,
            "123456" => $or,
            "S-DRY" => $dry,
            "10/07/2022" => $dt,
            "#2 Btr  App" => $grade,
            "Wane, Mold" => $con,
            " 288" => $bdf,
            "For Moisture Testing" => $asset->notes,
            "TETW" => $inv,
            "User: Darrell" => $username,
            "https://dev2.sawpit.app/hardware/1234567890" => $qr,
            // "^FDTID:6BYTES^FS" => "^FDTID:^FS^FN2^FS", // add TID
            // "^FDEPC:12BYTESOFDATA^FS" => "^FDEPC:^FS^FN3^FS", //add EPC
            "ASSET_____TAG16C" => $bc,
            "^FB291,1,0,C" => '', //remove text wrapping in W H L
            "^MMT" => '', //remove MMT comand, it confuses the tear off
            "^PQ1,0,1,Y^XZ" => $rfidZplCode
        );



        //Layout in ZebraDesigner Essentials, filename SawPit4x2V2.nlbl
        //Then print to file to get the ZPL  

        $zpl = file_get_contents('/var/www/html/snipeit/resources/views/partials/Sawpit4x6V3.ZPL');

        $zpl = strtr($zpl,$replacements);

        return $zpl;

    }

    public static function get4x2ZPL($asset, $username="") {

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

        return $zpl;
        //api.labelary.com makes a png with 2px per dot so the png is 812x406 
        //The factors of 406 are 1, 2, 7, 14, 29, 58, 203, 406. Pick a factor so preview is not blurry

    }

}

class CommunicationException extends RuntimeException
{
    //
}

if ( class_exists('ZplClient')) {
    Log::info("skipping creation of client");
}
else 
{
        

class ZplClient
{
    /**
     * The endpoint.
     *
     * @var resource
     */
    protected $socket;
    /**
     * Create an instance.
     *
     * @param string $host
     * @param int $port
     */
    public function __construct($host, $port = 9100)
    {
        $this->connect($host, $port);
    }
    /**
     * Destroy an instance.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
    /**
     * Create an instance statically.
     *
     * @param string $host
     * @param int $port
     * @return ZplClient
     */
    public static function printer(string $host, int $port = 9100): self
    {
        return new static($host, $port);
    }
    /**
     * Connect to printer.
     *
     * @param string $host
     * @param int $port
     * @throws CommunicationException if the connection fails.
     */
    protected function connect(string $host, int $port): void
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        Log::warning("Connecting to printer at  $host : $port");
        @socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));
        @socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 10, 'usec' => 0));
        if (!$this->socket || !@socket_connect($this->socket, $host, $port)) {
            $error = $this->getLastError();
            throw new CommunicationException($error['message'], $error['code']);
        }
        Log::warning("Connected to printer at  $host : $port");
    }
    /**
     * Close connection to printer.
     */
    public function disconnect(): void
    {
        if ($this->socket ){
            @socket_close($this->socket);
        }
        $this->socket = false;
    }
    /**
     * Send ZPL data to printer.
     *
     * @param string $zpl
     * @throws CommunicationException if writing to the socket fails.
     */
    public function send(string $zpl): void
    {
        if (false === @socket_write($this->socket, $zpl)) {
            $error = $this->getLastError();
            throw new CommunicationException($error['message'], $error['code']);
        }
    }
    /**
     * Read From Printer
     *
     * @throws CommunicationException if reading the socket fails.
     *  ~HI returns 29 chars: SL4M(203dpi),V1.04M,8,32768KB 
     */
    public function readNormal(int $maxReadLen=1024): string
    {
        $data = @socket_read($this->socket, $maxReadLen,  PHP_NORMAL_READ); // PHP_BINARY_READ or PHP_NORMAL_READ
        if (false === $data) {
            $error = $this->getLastError();
            return $error['message'];
            //throw new CommunicationException($error['message'], $error['code']);
        }
        return $data;
    }

    public function read(int $maxReadLen=1024): ?string
    {
        $buf = 'This is my buffer.';
        if (false !== ($bytes = @socket_recv($this->socket, $buf, 2048, MSG_WAITALL))) {
            Log::info("Read $bytes bytes from socket_recv()");
        } else {
            Log::error("socket_recv() failed; reason: " . @socket_strerror(@socket_last_error($this->socket)) . "\n");
        }
        // if ($buf === null) {
        //     $buff = 'No data';
        // }
        return $buf;
    }


    /**
     * Get the last socket error.
     *
     * @return array
     */
    protected function getLastError(): array
    {
        $code = socket_last_error($this->socket);
        $message = socket_strerror($code);

        return compact('code', 'message');
    }
}
}
// $client = new ZplClient('192.168.10.77');
// $client->send($zpl);
// $result = $client->read();
// unset($client);