<?php

class SMTPClient {
    private $host;
    private $port;
    private $username;
    private $password;
    private $debug;
    private $socket;
    private $timeout = 30;
    private $lastResponse = '';

    /**
     * Create a new SMTP client instance
     * 
     * @param string $host SMTP server hostname
     * @param int $port SMTP server port
     * @param string $username SMTP username (optional)
     * @param string $password SMTP password (optional)
     * @param bool $debug Enable debug output
     */
    public function __construct($host, $port, $username = null, $password = null, $debug = false) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;
    }

    /**
     * Connect to the SMTP server
     * 
     * @return bool True if connection was successful
     */
    public function connect() {
        if ($this->socket) {
            $this->log("Already connected, closing existing connection");
            $this->close();
        }
        
        $this->log("Connecting to {$this->host}:{$this->port}");
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        
        if (!$this->socket) {
            $this->log("Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Get server greeting
        $response = $this->getResponse();
        if (substr($response, 0, 3) !== '220') {
            $this->log("SMTP server did not greet correctly: $response");
            $this->close();
            return false;
        }
        
        // Say hello to the server
        if (!$this->send("EHLO " . gethostname())) {
            $this->close();
            return false;
        }
        
        // If username and password are provided, authenticate
        if (!empty($this->username) && !empty($this->password)) {
            // Check if server supports AUTH LOGIN
            if (strpos($this->lastResponse, "AUTH LOGIN") !== false) {
                if (!$this->authenticate()) {
                    $this->close();
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Authenticate with the SMTP server using LOGIN method
     * 
     * @return bool True if authentication was successful
     */
    private function authenticate() {
        if (!$this->send("AUTH LOGIN")) {
            return false;
        }
        
        if (!$this->send(base64_encode($this->username))) {
            return false;
        }
        
        if (!$this->send(base64_encode($this->password))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send an email
     * 
     * @param string $from From email address
     * @param string $fromName From name
     * @param string $to To email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML message body
     * @param string $textBody Plain text message body
     * @return bool True if the email was sent successfully
     */
    public function sendEmail($from, $fromName, $to, $subject, $htmlBody, $textBody = '') {
        if (!$this->socket && !$this->connect()) {
            return false;
        }
        
        // Set sender
        if (!$this->send("MAIL FROM:<{$from}>")) {
            return false;
        }
        
        // Set recipient
        if (!$this->send("RCPT TO:<{$to}>")) {
            return false;
        }
        
        // Begin data
        if (!$this->send("DATA")) {
            return false;
        }
        
        // Compose email headers and body
        $message = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$from}>\r\n"
                 . "To: <{$to}>\r\n"
                 . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n"
                 . "\r\n"
                 . "--boundary\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: quoted-printable\r\n"
                 . "\r\n"
                 . quoted_printable_encode($textBody ?: strip_tags(str_replace('<br>', "\r\n", $htmlBody)))
                 . "\r\n\r\n"
                 . "--boundary\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "Content-Transfer-Encoding: quoted-printable\r\n"
                 . "\r\n"
                 . quoted_printable_encode($htmlBody)
                 . "\r\n\r\n"
                 . "--boundary--\r\n";
        
        // Send message content
        if (!$this->send($message . "\r\n.")) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Send a command to the SMTP server
     * 
     * @param string $command Command to send
     * @return bool True if command was successful
     */
    private function send($command) {
        $this->log("SENT: $command");
        
        if (!$this->socket) {
            $this->log("Socket not connected");
            return false;
        }
        
        $result = @fwrite($this->socket, $command . "\r\n");
        if ($result === false) {
            $this->log("Failed to send command");
            return false;
        }
        
        $response = $this->getResponse();
        $code = substr($response, 0, 3);
        
        // Check if command was successful
        if ($code && $code[0] >= '4') {
            $this->log("Command failed: $response");
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the SMTP server response
     * 
     * @return string Server response
     */
    private function getResponse() {
        if (!$this->socket) {
            return '';
        }
        
        $data = '';
        $endTime = time() + $this->timeout;
        
        // Continue reading until timeout or end of response (lines starting with number and space)
        while (time() < $endTime) {
            // If we can't read from the socket, break
            if (feof($this->socket)) {
                $this->log("Connection closed by server");
                break;
            }
            
            $line = @fgets($this->socket, 515);
            if ($line === false) {
                $this->log("Failed to read from socket");
                break;
            }
            
            $data .= $line;
            
            // If the 4th character is a space, it's the end of the response
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        
        $this->log("RECV: $data");
        $this->lastResponse = $data;
        
        return $data;
    }
    
    /**
     * Close the SMTP connection
     */
    public function close() {
        if ($this->socket) {
            $this->send("QUIT");
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    /**
     * Destructor - close the socket if open
     */
    public function __destruct() {
        $this->close();
    }
    
    /**
     * Log a message if debug is enabled
     * 
     * @param string $message Message to log
     */
    private function log($message) {
        if ($this->debug) {
            error_log("SMTP: $message");
        }
    }
}