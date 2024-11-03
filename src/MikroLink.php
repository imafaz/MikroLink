<?php

declare(strict_types=1);

/**
 * lightweight library for manage mikrotik with api
 *
 * @version 0.0.1
 * @author Mr Afaz
 * @package MikroLink
 * @copyright Copyright 2024 MikrotikMng library
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/imafaz/MikroLink
 */



namespace MikrotikApi;

use EasyLog\Logger;

/**
 * @method void __construct(bool $debug = false, int $timeout = 1,  int $attempts = 3,int  $delay = 0)
 * @method bool connect(string $ip, string $username, string $password, int $port, $ssl = false)
 * @method array read(true)
 * @method bool write(string $command, $param2 = true)
 * @method mixed encodeLength(mixed $length)
 * @method void disconnect()
 * @method mixed parseResponse(mixed $response)
 * @method void debug(string $text)
 * @method void __destruct()
 * @method array exec(string $command, array $params = null)
 */


class MikroLink
{

    /**
     * debuging var
     *
     * @var bool
     */
    var $debug;


    /**
     * connected to mikrotik var
     *
     * @var bool
     */
    var $connected = false;


    /**
     * ssl var
     *
     * @var bool
     */
    var $ssl;

    
    /**
     * socket object
     *
     * @var object
     */
    var $socket;



       /**
     * socket error number
     *
     * @var int
     */
    var $error_no;



       /**
     * socket error string
     *
     * @var sttring
     */
    var $error_str;


       /**
     * mikrotik router port (ssl or not)
     *
     * @var int
     */
    var $port;


       /**
     * time out connect to router
     *
     * @var int
     */
    var $timeout;


       /**
     * try connect count
     *
     * @var int
     */
    var $attempts;

    
    /**
     * delay after connected
     *
     * @var int
     */
    var $delay;


    
    
    /**
     * loggger object
     *
     * @var object
     */
    private $logger;
    


    /**
     * __construct: init options
     * 
     * @param  bool $debug
     * @param  int $timeout
     * @param  int $attempts
     * @param  int $delay
     * 
     * @return void
     */
    public function __construct( int $timeout = 1,  int $attempts = 3,int  $delay = 0,$logFile = 'mikrolink.log',$printLog = false)
    {
        $this->logger = new Logger($logFile, $printLog);
        $this->timeout = $timeout;
        $this->attempts = $attempts;
        $this->delay = $delay;
    }

    /**
     * connect: connect and login to mikrotik
     * 
     * @param  string $ip
     * @param  string $username
     * @param  string $password
     * @param  int $port
     * @param  bool $ssl
     * 
     * @return bool
     */
    public function connect(string $ip, string $username, string $password, int $port,bool  $ssl = false)
    {

        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {
            $this->connected = false;
            $PROTOCOL = ($ssl ? 'ssl://' : '');
            $context = stream_context_create(array('ssl' => array('ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false)));
            $this->logger->debug('Connection attempt #' . $ATTEMPT . ' to ' . $PROTOCOL . $ip . ':' . $port . '...');
            $this->socket = @stream_socket_client($PROTOCOL . $ip . ':' . $port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $context);
            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login', false);
                $this->write('=name=' . $username, false);
                $this->write('=password=' . $password);
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0])) {
                    if ($RESPONSE[0] == '!done') {
                        if (!isset($RESPONSE[1])) {
                            // Login method post-v6.43
                            $this->connected = true;
                            break;
                        } else {
                            // Login method pre-v6.43
                            $MATCHES = array();
                            if (preg_match_all('/[^=]+/i', $RESPONSE[1], $MATCHES)) {
                                if ($MATCHES[0][0] == 'ret' && strlen($MATCHES[0][1]) == 32) {
                                    $this->write('/login', false);
                                    $this->write('=name=' . $username, false);
                                    $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1])));
                                    $RESPONSE = $this->read(false);
                                    if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                                        $this->connected = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }

        if ($this->connected) {
            $this->logger->debug('Connected...');
        } else {
            $this->logger->error($this->error_str);
            die;
        }
        return $this->connected;
    }


    /**
     * read: read mikrotik api socket
     * 
     * @param  bool $parse
     * 
     * @return array
     */
    public function read(bool $parse = true)
    {
        $RESPONSE     = array();
        $receiveddone = false;
        while (true) {
            $BYTE   = ord(fread($this->socket, 1));
            $LENGTH = 0;
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) {
                    $LENGTH = (($BYTE & 63) << 8) + ord(fread($this->socket, 1));
                } else {
                    if (($BYTE & 224) == 192) {
                        $LENGTH = (($BYTE & 31) << 8) + ord(fread($this->socket, 1));
                        $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                    } else {
                        if (($BYTE & 240) == 224) {
                            $LENGTH = (($BYTE & 15) << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        } else {
                            $LENGTH = ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                            $LENGTH = ($LENGTH << 8) + ord(fread($this->socket, 1));
                        }
                    }
                }
            } else {
                $LENGTH = $BYTE;
            }

            $_ = "";
            if ($LENGTH > 0) {
                $_      = "";
                $retlen = 0;
                while ($retlen < $LENGTH) {
                    $toread = $LENGTH - $retlen;
                    $_ .= fread($this->socket, $toread);
                    $retlen = strlen($_);
                }
                $RESPONSE[] = $_;
                $this->logger->debug('>>> [' . $retlen . '/' . $LENGTH . '] bytes read.');
            }

            if ($_ == "!done") {
                $receiveddone = true;
            }

            $STATUS = socket_get_status($this->socket);
            if ($LENGTH > 0) {
                $this->logger->debug('>>> [' . $LENGTH . ', ' . $STATUS['unread_bytes'] . ']' . $_);
            }

            if ((!$this->connected && !$STATUS['unread_bytes']) || ($this->connected && !$STATUS['unread_bytes'] && $receiveddone)) {
                break;
            }
        }

        if ($parse) {
            $RESPONSE = $this->parseResponse($RESPONSE);
        }

        return $RESPONSE;
    }




    /**
     * write: write in mikrotik api socket
     * 
     * @param  string $command
     * @param  bool|int $param2
     * 
     * @return bool
     */
    public function write(string $command,bool|int $param2 = true)
    {
        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $com) {
                $com = trim($com);
                fwrite($this->socket, $this->encodeLength(strlen($com)) . $com);
                $this->logger->debug('<<< [' . strlen($com) . '] ' . $com);
            }

            if (gettype($param2) == 'integer') {
                fwrite($this->socket, $this->encodeLength(strlen('.tag=' . $param2)) . '.tag=' . $param2 . chr(0));
                $this->logger->debug('<<< [' . strlen('.tag=' . $param2) . '] .tag=' . $param2);
            } elseif (gettype($param2) == 'boolean') {
                fwrite($this->socket, ($param2 ? chr(0) : ''));
            }

            return true;
        } else {
            return false;
        }
    }



    /**
     * encodeLength: encodes a length value into a specific binary format
     * 
     * @param  mixed $length
     * 
     * @return mixed
     */
    public function encodeLength(mixed $length)
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }

        return $length;
    }


    /**
     * disconnect: drop socket
     * 
     * @param  mixed $length
     * 
     * @return void
     */
    public function disconnect()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->connected = false;
        $this->logger->debug('Disconnected...');
    }


    /**
     * parseResponse:  extracting and organizing data based mikrotik
     * 
     * @param  mixed $response
     * 
     * @return array
     */
    public function parseResponse(mixed $response)
    {
        if (is_array($response)) {
            $PARSED      = array();
            $CURRENT     = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, array('!fatal', '!re', '!trap'))) {
                    if ($x == '!re') {

                        $PARSED[] = [];

                        $CURRENT = &$PARSED[count($PARSED) - 1];
                    } else {

                        if (!isset($PARSED[$x])) {

                            $PARSED[$x] = [];
                        }

                        $CURRENT = &$PARSED[$x];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = array();
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }

            if (empty($PARSED) && !is_null($singlevalue)) {
                $PARSED = $singlevalue;
            }

            return $PARSED;
        } else {
            return array();
        }
    }

    /**
     * __destruct: disconnect after 
     * 
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }


    /**
     * exec: execute command with parse response
     * 
     * @param  string $command
     * @param  array|null $params = null
     * 
     * @return array
     */
    public function exec(string $command, array|null $params = null)
    {
        if (is_array($params)) {
            $count = count($params);
            $this->write($command, !$params);
            $i = 0;
            foreach ($params as $k => $v) {
                switch ($k[0]) {
                    case "?":
                        $el = "$k=$v";
                        break;
                    case "~":
                        $el = "$k~$v";
                        break;
                    default:
                        $el = "=$k=$v";
                        break;
                }

                $last = ($i++ == $count - 1);
                $this->write($el, $last);
            }

            return $this->read();
        } else {
            $this->write($command);
            return $this->read();
        }
    }
}
