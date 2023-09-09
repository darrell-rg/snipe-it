<?php
// This is the PHP for the 6x6 Label
use Illuminate\Support\Facades\Log;


if ( class_exists('Client')) {
    Log::info("skipping creation of client");
}
else 
{
    
class CommunicationException extends RuntimeException
{
    //
}
class Client
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
     * @return Client
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

        Log::info("Connecting to printer at  $host : $port");
        @socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 4, 'usec' => 0));
        @socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 4, 'usec' => 0));
        if (!$this->socket || !@socket_connect($this->socket, $host, $port)) {
            $error = $this->getLastError();
            throw new CommunicationException($error['message'], $error['code']);
        }
        Log::info("Conneecting to printer at  $host : $port");
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
            Log::error("socket_recv() failed; reason: " . @socket_strerror(@socket_last_error($socket)) . "\n");
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
// $client = new Client('192.168.10.77');
// $client->send($zpl);
// $result = $client->read();
// unset($client);

?>