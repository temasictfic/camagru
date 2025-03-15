<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Email {
    private $host;
    private $port;
    private $username;
    private $password;
    private $from;
    private $fromName;
    private $debug;

    public function __construct() {
        $this->host = getenv('MAIL_HOST');
        $this->port = getenv('MAIL_PORT');
        $this->username = getenv('MAIL_USERNAME');
        $this->password = getenv('MAIL_PASSWORD');
        $this->from = getenv('MAIL_FROM');
        $this->fromName = getenv('APP_NAME') ?: 'Camagru';
        $this->debug = getenv('APP_ENV') === 'development';
        
        // Ensure PHPMailer autoloader is included
        require_once BASE_PATH . '/vendor/autoload.php';
    }

    /**
     * Send verification email to user
     */
    public function sendVerificationEmail($to, $username, $token) {
        $subject = "Verify your account - Camagru";
        $verificationLink = APP_URL . "/verify?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Verify your account</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2 style='color: #333;'>Welcome to Camagru!</h2>
                <p>Hello $username,</p>
                <p>Thank you for signing up. Please click the link below to verify your account:</p>
                <p><a href='$verificationLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Account</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>$verificationLink</p>
                <p>If you did not create an account, please ignore this email.</p>
                <p>Regards,<br>The Camagru Team</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $message);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($to, $username, $token) {
        $subject = "Reset your password - Camagru";
        $resetLink = APP_URL . "/reset-password?token=" . $token;
        
        $message = "
        <html>
        <head>
            <title>Reset your password</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2 style='color: #333;'>Reset Your Password</h2>
                <p>Hello $username,</p>
                <p>We received a request to reset your password. Please click the link below to set a new password:</p>
                <p><a href='$resetLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>$resetLink</p>
                <p>If you did not request this, please ignore this email.</p>
                <p>Regards,<br>The Camagru Team</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $message);
    }

    /**
     * Send comment notification email
     */
    public function sendCommentNotification($to, $username, $imageId, $commenterUsername) {
        $subject = "New comment on your photo - Camagru";
        $imageLink = APP_URL . "/gallery?image=" . $imageId;
        
        $message = "
        <html>
        <head>
            <title>New comment on your photo</title>
        </head>
        <body>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;'>
                <h2 style='color: #333;'>New Comment</h2>
                <p>Hello $username,</p>
                <p>$commenterUsername has commented on your photo.</p>
                <p><a href='$imageLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Comment</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>$imageLink</p>
                <p>Regards,<br>The Camagru Team</p>
                <p style='font-size: 12px; color: #777;'>You can disable these notifications in your profile settings.</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->send($to, $subject, $message);
    }

    /**
     * Send email using PHPMailer
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message HTML message body
     * @return bool Success status
     */
    private function send($to, $subject, $message) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            if ($this->debug) {
                // Instead of outputting to browser, log to file or store in a variable
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                // Redirect output to a variable
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer Debug: $str");
                };
            }
            
            $mail->isSMTP();                                      // Send using SMTP
            $mail->Host       = $this->host;                      // Set the SMTP server
            $mail->SMTPAuth   = true;                             // Enable SMTP authentication
            $mail->Username   = $this->username;                  // SMTP username
            $mail->Password   = $this->password;                  // SMTP password
            
            // For Gmail, enable these settings
            if (strpos($this->host, 'gmail') !== false) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                $mail->Port       = 587;                            // TCP port to connect to (use 587 for TLS)
            } else {
                // Default secure connection setting
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;     // Use SMTPS (implicit TLS)
                $mail->Port       = $this->port;                     // TCP port as specified in config
            }

            // Recipients
            $mail->setFrom($this->from, $this->fromName);
            $mail->addAddress($to);     
            $mail->addReplyTo($this->from, $this->fromName);

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body    = $message;
            
            // Plain text alternative for email clients that don't support HTML
            $mail->AltBody = strip_tags(str_replace('<br>', "\r\n", $message));

            // Send the email
            $mail->send();
            
            if ($this->debug) {
                error_log("Email sent successfully to $to");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Email error: " . $mail->ErrorInfo);
            return false;
        }
    }
}