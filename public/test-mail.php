<?php
// Include config and setup
require_once __DIR__ . '/../config/setup.php';

// Test email connection
echo "<h1>Email Connection Test</h1>";

// Ensure PHPMailer autoloader is included
require_once BASE_PATH . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get email configuration from environment
$host = getenv('MAIL_HOST');
$port = getenv('MAIL_PORT');
$from = getenv('MAIL_FROM') ?: 'test@camagru.local';

echo "<p>Testing connection to mail server: $host:$port</p>";

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->Debugoutput = 'html';

    // Server settings
    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->Port       = $port;
    
    // Disable authentication for MailHog
    $mail->SMTPAuth   = false;
    
    // Disable encryption for MailHog
    $mail->SMTPSecure = '';
    $mail->SMTPAutoTLS = false;

    // Set sender and recipient
    $mail->setFrom($from, 'Test Sender');
    $mail->addAddress('test@example.com', 'Test Recipient');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = 'This is a test email to verify MailHog connection works.';

    // Send the email
    echo "<p>Attempting to send test email...</p>";
    $mail->send();
    echo "<p style='color: green;'>Message has been sent successfully!</p>";
    
    echo "<p>You can check the received email at <a href='http://localhost:8025' target='_blank'>MailHog Web Interface (localhost:8025)</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</p>";
    
    // Additional debugging info
    echo "<h2>Debugging Information</h2>";
    echo "<pre>";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "From: $from\n";
    
    // Test network connectivity
    echo "\nTesting network connectivity...\n";
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$connection) {
        echo "Failed to connect to $host:$port - $errstr ($errno)\n";
    } else {
        echo "Successfully connected to $host:$port\n";
        fclose($connection);
    }
    echo "</pre>";
}