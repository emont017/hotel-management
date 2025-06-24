<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified version for Hotel Management System
 */

class PHPMailer {
    public $Host = 'localhost';
    public $Port = 25;
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $IsHTML = true;
    public $CharSet = 'UTF-8';
    
    private $to = [];
    private $smtp_connection;
    
    public function isSMTP() {
        // Set mailer to use SMTP
    }
    
    public function addAddress($email, $name = '') {
        $this->to[] = ['email' => $email, 'name' => $name];
    }
    
    public function send() {
        if ($this->SMTPAuth && $this->Host && $this->Port) {
            return $this->sendSMTP();
        } else {
            return $this->sendMail();
        }
    }
    
    private function sendSMTP() {
        try {
            // Create socket connection
            $this->smtp_connection = $this->smtpConnect();
            if (!$this->smtp_connection) {
                return false;
            }
            
            // Authenticate
            if (!$this->smtpAuth()) {
                $this->smtpClose();
                return false;
            }
            
            // Send email
            $result = $this->smtpSendEmail();
            $this->smtpClose();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function smtpConnect() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'ciphers' => 'DEFAULT:!DH'
            ]
        ]);
        
        // For Gmail, use TLS on port 587
        if ($this->Port == 587) {
            $connection = @stream_socket_client(
                "tcp://{$this->Host}:{$this->Port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
        } else {
            // For SSL port 465
            $connection = @stream_socket_client(
                "ssl://{$this->Host}:{$this->Port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $context
            );
        }
        
        if (!$connection) {
            error_log("SMTP Connection failed: $errstr ($errno)");
            return false;
        }
        
        // Read server greeting
        $response = fgets($connection, 512);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP Greeting failed: $response");
            fclose($connection);
            return false;
        }
        
        return $connection;
    }
    
    private function smtpAuth() {
        // Send EHLO
        fputs($this->smtp_connection, "EHLO localhost\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '250') {
            return false;
        }
        
        // Read all EHLO responses
        while (substr($response, 3, 1) == '-') {
            $response = fgets($this->smtp_connection, 512);
        }
        
        // Start TLS if on port 587
        if ($this->Port == 587) {
            fputs($this->smtp_connection, "STARTTLS\r\n");
            $response = fgets($this->smtp_connection, 512);
            if (substr($response, 0, 3) != '220') {
                return false;
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($this->smtp_connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                return false;
            }
            
            // Send EHLO again after TLS
            fputs($this->smtp_connection, "EHLO localhost\r\n");
            $response = fgets($this->smtp_connection, 512);
            if (substr($response, 0, 3) != '250') {
                return false;
            }
            
            // Read all EHLO responses
            while (substr($response, 3, 1) == '-') {
                $response = fgets($this->smtp_connection, 512);
            }
        }
        
        // Authenticate
        fputs($this->smtp_connection, "AUTH LOGIN\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send username
        fputs($this->smtp_connection, base64_encode($this->Username) . "\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '334') {
            return false;
        }
        
        // Send password
        fputs($this->smtp_connection, base64_encode($this->Password) . "\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP Auth failed: $response");
            return false;
        }
        
        return true;
    }
    
    private function smtpSendEmail() {
        // MAIL FROM
        fputs($this->smtp_connection, "MAIL FROM: <{$this->From}>\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '250') {
            return false;
        }
        
        // RCPT TO
        foreach ($this->to as $recipient) {
            fputs($this->smtp_connection, "RCPT TO: <{$recipient['email']}>\r\n");
            $response = fgets($this->smtp_connection, 512);
            if (substr($response, 0, 3) != '250') {
                return false;
            }
        }
        
        // DATA
        fputs($this->smtp_connection, "DATA\r\n");
        $response = fgets($this->smtp_connection, 512);
        if (substr($response, 0, 3) != '354') {
            return false;
        }
        
        // Email headers and body
        $email_data = "From: {$this->FromName} <{$this->From}>\r\n";
        $email_data .= "To: {$this->to[0]['email']}\r\n";
        $email_data .= "Subject: {$this->Subject}\r\n";
        $email_data .= "MIME-Version: 1.0\r\n";
        $email_data .= "Content-Type: text/html; charset={$this->CharSet}\r\n";
        $email_data .= "\r\n";
        $email_data .= $this->Body;
        $email_data .= "\r\n.\r\n";
        
        fputs($this->smtp_connection, $email_data);
        $response = fgets($this->smtp_connection, 512);
        
        return substr($response, 0, 3) == '250';
    }
    
    private function smtpClose() {
        if ($this->smtp_connection) {
            fputs($this->smtp_connection, "QUIT\r\n");
            fclose($this->smtp_connection);
        }
    }
    
    private function sendMail() {
        // Fallback to PHP mail() function
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset={$this->CharSet}";
        $headers[] = "From: {$this->FromName} <{$this->From}>";
        $headers[] = "Reply-To: {$this->From}";
        
        foreach ($this->to as $recipient) {
            if (!mail($recipient['email'], $this->Subject, $this->Body, implode("\r\n", $headers))) {
                return false;
            }
        }
        
        return true;
    }
}
?> 