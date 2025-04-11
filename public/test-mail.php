<?php
// Include config and setup
require_once __DIR__ . '/../config/setup.php';

// Include the SMTP client
require_once BASE_PATH . '/services/SMTPClient.php';

// Test email connection
echo "<h1>Email Connection Test</h1>";

// Get email configuration from environment
$host = getenv('MAIL_HOST') ?: 'mailhog';
$port = getenv('MAIL_PORT') ?: '1025';
$from = getenv('MAIL_FROM') ?: 'test@camagru.local';
$fromName = 'Test Sender';
$to = 'test@example.com';
$subject = 'SMTP Test Email';
$message = '<div style="font-family: Arial, sans-serif; color: #333;">
    <h2>This is a test email</h2>
    <p>This email was sent using the custom SMTPClient.</p>
    <p>If you received this email, the connection to MailHog is working!</p>
</div>';

echo "<p>Testing connection to mail server: $host:$port</p>";

// Create new SMTPClient instance with debug mode enabled
$smtp = new SMTPClient($host, $port, null, null, true);

try {
    echo "<pre>";
    
    // Connect to server
    echo "Connecting to SMTP server... ";
    if ($smtp->connect()) {
        echo "SUCCESS\n";
        
        // Send a test email
        echo "Sending test email... ";
        if ($smtp->sendEmail($from, $fromName, $to, $subject, $message)) {
            echo "SUCCESS\n";
            echo "</pre>";
            
            echo "<p style='color: green;'>Message has been sent successfully!</p>";
            echo "<p>You can check the received email at <a href='http://localhost:8025' target='_blank'>MailHog Web Interface (localhost:8025)</a></p>";
            
            // Email details
            echo "<h2>Email Details</h2>";
            echo "<ul>";
            echo "<li><strong>From:</strong> $fromName &lt;$from&gt;</li>";
            echo "<li><strong>To:</strong> $to</li>";
            echo "<li><strong>Subject:</strong> $subject</li>";
            echo "</ul>";
            
            echo "<h3>Message Content:</h3>";
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; max-width: 600px;'>";
            echo $message;
            echo "</div>";
        } else {
            echo "FAILED\n";
            echo "</pre>";
            echo "<p style='color: red;'>Failed to send email. Check the error log for details.</p>";
        }
        
        // Close the connection
        $smtp->close();
    } else {
        echo "FAILED\n";
        echo "</pre>";
        echo "<p style='color: red;'>Failed to connect to SMTP server. Check the error log for details.</p>";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
}

// Test network connectivity
echo "<h2>Network Connectivity Test</h2>";
$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if (!$connection) {
    echo "<p style='color: red;'>Failed to connect to $host:$port - $errstr ($errno)</p>";
} else {
    echo "<p style='color: green;'>Successfully connected to $host:$port</p>";
    fclose($connection);
}