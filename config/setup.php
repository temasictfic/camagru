<?php

// Load environment variables from .env file
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load environment variables
loadEnv();

// Set error reporting based on environment
if (getenv('APP_ENV') === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING);
}

// Set default timezone
date_default_timezone_set('UTC');

// Constants
define('BASE_PATH', realpath(__DIR__ . '/..'));
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

// Include helpers
require_once BASE_PATH . '/helpers/functions.php';

// Autoload classes
spl_autoload_register(function ($className) {
    // Convert namespace to file path
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    
    // List of potential directories where classes might be located
    $directories = [
        BASE_PATH . '/models/',
        BASE_PATH . '/controllers/',
        BASE_PATH . '/services/',
        BASE_PATH . '/config/'
    ];
    
    // Check each directory
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});