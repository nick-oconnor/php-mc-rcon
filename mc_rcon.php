<?php
    
    class mc_rcon
    {
        function __construct($dest, $port, $password)
        {
            $this->sock = fsockopen($dest, $port);
            if ($this->sock === false)
            {
                $this->connect_errno = 1;
                $this->connect_error = "Connection failed";
                return;
            }
            
            $this->send(3, $password);
            $response = $this->receive();
            if ($response["id"] === -1)
            {
                $this->connect_errno = 2;
                $this->connect_error = "Access denied";
                return;
            }
        }
        
        function __destruct()
        {
            fclose($this->sock);
        }
        
        public function connect_errno()
        {
            return $this->connect_errno;
        }
        
        public function connect_error()
        {
            return $this->connect_error;
        }
        
        public function command($command)
        {
            $this->send(2, $command);
            return $this->receive()["body"];
        }
        
        private $sock;
        private $connect_errno = 0;
        private $connect_error;
        
        private function pack_int($int)
        {
            if (pack("L", 1) === pack("N", 1))
            {
                return strrev(pack("l", $int));
            }
            return pack("l", $int);
        }
        
        private function unpack_int($string)
        {
            if (pack("L", 1) === pack("N", 1))
            {
                return unpack("lint", strrev($string))["int"];
            }
            return unpack("lint", $string)["int"];
        }
        
        private function send($type, $body)
        {
            $data = $this->pack_int(0) . $this->pack_int($type) . pack("a*", $body) . "\0\0";
            $packet = $this->pack_int(strlen($data)) . $data;
            fwrite($this->sock, $packet, strlen($packet));
        }
        
        private function receive()
        {
            $size = $this->unpack_int(fread($this->sock, 4));
            $response["id"] = $this->unpack_int(fread($this->sock, 4));
            $response["type"] = $this->unpack_int(fread($this->sock, 4));
            $response["body"] = unpack("a*body/a*empty", fread($this->sock, $size - 8))["body"];
            return $response;
        }
    }
    
?>
