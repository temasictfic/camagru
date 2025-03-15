<?php
// Save this file as public/debug.php

// Include config and setup
require_once __DIR__ . '/../config/setup.php';

echo "<h1>Camagru Debug Information</h1>";

// Check if GD library is installed
echo "<h2>GD Library</h2>";
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>GD Library is installed.</p>";
    
    // Check GD Version
    $gd_info = gd_info();
    echo "<p>Version: " . $gd_info['GD Version'] . "</p>";
    
    // Check if PNG support is enabled
    echo "<p>PNG Support: " . ($gd_info['PNG Support'] ? "Yes" : "No") . "</p>";
    echo "<p>JPEG Support: " . ($gd_info['JPEG Support'] ? "Yes" : "No") . "</p>";
} else {
    echo "<p style='color: red;'>GD Library is NOT installed!</p>";
}

// Check uploads directory
echo "<h2>Uploads Directory</h2>";
$uploadsDir = BASE_PATH . '/public/uploads/';

echo "<p>Path: " . $uploadsDir . "</p>";

if (is_dir($uploadsDir)) {
    echo "<p style='color: green;'>Uploads directory exists.</p>";
    
    if (is_writable($uploadsDir)) {
        echo "<p style='color: green;'>Uploads directory is writable.</p>";
    } else {
        echo "<p style='color: red;'>Uploads directory is NOT writable!</p>";
        echo "<p>Current permissions: " . substr(sprintf('%o', fileperms($uploadsDir)), -4) . "</p>";
    }
} else {
    echo "<p style='color: red;'>Uploads directory does NOT exist!</p>";
    
    // Try to create it
    echo "<p>Attempting to create uploads directory...</p>";
    if (mkdir($uploadsDir, 0777, true)) {
        echo "<p style='color: green;'>Successfully created uploads directory.</p>";
    } else {
        echo "<p style='color: red;'>Failed to create uploads directory!</p>";
    }
}

// Check overlays directory
echo "<h2>Overlays Directory</h2>";
$overlaysDir = BASE_PATH . '/public/img/overlays/';

echo "<p>Path: " . $overlaysDir . "</p>";

if (is_dir($overlaysDir)) {
    echo "<p style='color: green;'>Overlays directory exists.</p>";
    
    // List overlays
    $overlays = [];
    $files = scandir($overlaysDir);
    
    echo "<p>Overlays:</p>";
    echo "<ul>";
    $foundPng = false;
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir($overlaysDir . $file)) {
            $info = pathinfo($file);
            if (isset($info['extension']) && strtolower($info['extension']) === 'png') {
                echo "<li>" . $file . "</li>";
                $foundPng = true;
            }
        }
    }
    
    if (!$foundPng) {
        echo "<li style='color: red;'>No PNG overlays found!</li>";
    }
    
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Overlays directory does NOT exist!</p>";
}

// Test image creation
echo "<h2>Image Creation Test</h2>";

try {
    // Create a test image
    $width = 400;
    $height = 300;
    $testImage = imagecreatetruecolor($width, $height);
    
    if (!$testImage) {
        echo "<p style='color: red;'>Failed to create test image!</p>";
    } else {
        echo "<p style='color: green;'>Successfully created test image.</p>";
        
        // Fill with a color
        $background = imagecolorallocate($testImage, 255, 0, 0);
        imagefilledrectangle($testImage, 0, 0, $width, $height, $background);
        
        // Save the test image
        $testFilepath = $uploadsDir . 'test_image.png';
        
        if (imagepng($testImage, $testFilepath)) {
            echo "<p style='color: green;'>Successfully saved test image to: " . $testFilepath . "</p>";
            
            if (file_exists($testFilepath)) {
                echo "<p style='color: green;'>Test image file exists!</p>";
                echo "<p><img src='/uploads/test_image.png' alt='Test Image' style='max-width: 400px;'></p>";
            } else {
                echo "<p style='color: red;'>Test image file does NOT exist despite successful save operation!</p>";
            }
        } else {
            echo "<p style='color: red;'>Failed to save test image!</p>";
        }
        
        imagedestroy($testImage);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception occurred: " . $e->getMessage() . "</p>";
}

// Display PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "</pre>";

// Check server permissions
echo "<h2>Server Information</h2>";
echo "<pre>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Current User: " . get_current_user() . "\n";
echo "Current Process User ID: " . posix_getuid() . "\n";
echo "Current Process Group ID: " . posix_getgid() . "\n";
echo "</pre>";