<?php

class Email {
    private $host;
    private $port;
    private $username;
    private $password;
    private $from;

    public function __construct() {
        $this->host = getenv('MAIL_HOST');
        $this->port = getenv('MAIL_PORT');
        $this->username = getenv('MAIL_USERNAME');
        $this->password = getenv('MAIL_PASSWORD');
        $this->from = getenv('MAIL_FROM');
    }

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

    private function send($to, $subject, $message) {
        // Set content-type header for sending HTML email
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->from . "\r\n";
        
        // Additional headers
        $headers .= "Reply-To: " . $this->from . "\r\n";
        
        // Send email
        return mail($to, $subject, $message, $headers);
    }
}