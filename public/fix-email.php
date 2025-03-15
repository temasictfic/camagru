<?php
// public/fix-email.php

// Include config and setup
require_once __DIR__ . '/../config/setup.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Force update of Email class configuration
echo "<h1>Email Configuration Fix</h1>";

try {
    // Print current environment settings
    echo "<h2>Environment Settings</h2>";
    echo "<pre>";
    echo "MAIL_HOST: " . getenv('MAIL_HOST') . "\n";
    echo "MAIL_PORT: " . getenv('MAIL_PORT') . "\n";
    echo "MAIL_FROM: " . getenv('MAIL_FROM') . "\n";
    echo "</pre>";
    
    // Check if Email.php file is accessible
    $emailFilePath = BASE_PATH . '/services/Email.php';
    if (file_exists($emailFilePath)) {
        echo "<p style='color: green;'>Email.php file exists at: $emailFilePath</p>";
        
        // Read file content
        $fileContent = file_get_contents($emailFilePath);
        
        // Check for key configurations
        $configChecks = [
            'SMTPAuth' => strpos($fileContent, '$mail->SMTPAuth   = false;'),
            'SMTPSecure' => strpos($fileContent, '$mail->SMTPSecure = \'\';'),
            'SMTPAutoTLS' => strpos($fileContent, '$mail->SMTPAutoTLS = false;')
        ];
        
        echo "<h2>Configuration Checks</h2>";
        echo "<ul>";
        foreach ($configChecks as $config => $found) {
            if ($found !== false) {
                echo "<li style='color: green;'>$config properly configured</li>";
            } else {
                echo "<li style='color: red;'>$config not properly configured</li>";
            }
        }
        echo "</ul>";
        
        // Create a test instance of Email class
        if (class_exists('Email')) {
            echo "<p>Testing Email class instantiation...</p>";
            $emailService = new Email();
            echo "<p style='color: green;'>Email class instantiated successfully</p>";
        } else {
            echo "<p style='color: red;'>Email class not found. Make sure it's properly loaded.</p>";
        }
    } else {
        echo "<p style='color: red;'>Email.php file not found at: $emailFilePath</p>";
    }
    
    // Create a direct test using PHPMailer
    echo "<h2>Direct PHPMailer Test</h2>";
    
    // Include PHPMailer
    require_once BASE_PATH . '/vendor/autoload.php';
    
    
    // Create test function
    function testEmailSend($host, $port) {
        $mail = new PHPMailer(true);
        
        try {
            // Configure PHPMailer directly with the same settings we want in Email class
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function($str, $level) {
                echo "DEBUG: $str<br>";
            };
            
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->Port       = $port;
            $mail->SMTPAuth   = false;
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
            
            $mail->setFrom('test@camagru.local', 'Test Sender');
            $mail->addAddress('test@example.com', 'Test Recipient');
            
            $mail->isHTML(true);
            $mail->Subject = 'Direct PHPMailer Test';
            $mail->Body    = 'This is a direct test of PHPMailer.';
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            echo "ERROR: " . $mail->ErrorInfo . "<br>";
            return false;
        }
    }
    
    // Test direct connection
    $testResult = testEmailSend(getenv('MAIL_HOST'), getenv('MAIL_PORT'));
    if ($testResult) {
        echo "<p style='color: green;'>Direct PHPMailer test successful!</p>";
    } else {
        echo "<p style='color: red;'>Direct PHPMailer test failed.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}