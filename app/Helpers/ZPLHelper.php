<?php

namespace App\Helpers;

use App\Models\Setting;
use Image;

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

    /*
     * I know it's gauche  to return a shitty HTML string, but this is just a helper and since it will be the same every single time,
     * it seemed pretty safe to do here. Don't you judge me.
     */
    public static function get4x6ZPL($asset, $username="") {
        // This is the PHP for the 6x6 Label
        $qr = config('app.url');
        $qr = "$qr/hardware/$asset->id";
        $modelName = explode("-",$asset->model->name);
        $heartCenter = $modelName[1];  // "DF-FOHC"
        $heartCenter = $asset->model->model_number;
        $sup = ($asset->supplier) ? $asset->supplier->name : '';
        $or = $asset->order_number;
        $dt = Helper::getFormattedDateObject($asset->purchase_date, 'date', false);//"2022-14-07";
        $grade = $asset->_snipeit_grade_2 .' '. $asset->model->model_number; //model_no is BHC or FOHC
        $con = $asset->_snipeit_condition_9;
        $dry = $asset->_snipeit_dryness_3;
        $bdf = str_contains($asset->_snipeit_bdf_8,'.') ? explode(".",$asset->_snipeit_bdf_8)[0] : $asset->_snipeit_bdf_8;
        $inv = $asset->company->name;
        $bc = $asset->asset_tag;

        //pad the barcode
        if((strlen($bc)<15)){
            $bc = str_pad($bc, 15, " ", STR_PAD_RIGHT);;   
        }

        // TODO: use SGTIN-96 standard for RFID data
        // https://docs.zebra.com/content/tcm/us/en/printers/software/zebra-zpl-ii,-zbi-2,-set-get-do,-mirror,-wml-programming-guide/c-zpl-rfid-zpl-rfid-commands/r-zpl-rfid-rb.html
        //https://awc.org/codes-and-standards/weights-and-measurement/

        // Serialised Global Trade Item Number (SGTIN)
        // The Serialised Global Trade Item Number EPC scheme is used to assign a unique identity to an
        // instance of a trade item, such as a specific instance of a product or SKU.
        // urn:epc:id:sgtin:CompanyPrefix.ItemRefAndIndicator.SerialNumber

        // Global Location Number With or Without Extension (SGLN)
        // The SGLN EPC scheme is used to assign a unique identity to a physical location, such as a specific
        // building or a specific unit of shelving within a warehouse.
        // General syntax:
        // urn:epc:id:sgln:CompanyPrefix.LocationReference.Extension

        // BYTE 0: Version Num
        // BYTE 1: Species_ID  256 possible species 
        // BYTE 2: W in 1/4 Inches  = 64" max W
        // BYTE 3: H in 1/4 Inches  = 64" max H
        // BYTE 4: L in 1/4 Feet    = 64' max len 
        // BYTE 5: 1bit FOHC + 4bit Grade = 16 grades  + 3bit moisture = 8 moistures
        // BYTE 6-12 : Serial number  
        // 2.8147498e+14 = 48bits 
        // ZPL ^RB to set up SIGTIN 
        // https://docs.zebra.com/content/tcm/us/en/printers/software/zebra-zpl-ii,-zbi-2,-set-get-do,-mirror,-wml-programming-guide/c-zpl-rfid-zpl-rfid-commands/r-zpl-rfid-rb.html


        // Header
        // Filter Value
        // Partition
        // Company Prefix Index
        // Item Reference
        // Serial Number
        // SGTIN-96
        // 8 bits   3 bits   3 bits   20–40 bits  24 bits  38 bits

        // 10 (binary value)
        // 8 (decimal capacity)
        // 8 (decimal capacity)
        // 16,383 (decimal capacity)
        // 9 to 1,048,575 (decimal capacity*)
        // 33,554,431 (decimal capacity)

        $rfidSerial = $asset->serial;
        $rfidZplCode = <<<EOD
        ^FX Write the RFID in hex 96 bits is 12 bytes
        ^RFW,A^FD$rfidSerial^FS
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