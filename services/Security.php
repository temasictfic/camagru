<?php

class Security {
    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return array Validation result
     */
    public static function validatePassword($password) {
        $errors = [];
        
        // Check length
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        // Check complexity
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        if (count($errors) > 0) {
            return ['valid' => false, 'errors' => $errors];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validate email
     * 
     * @param string $email Email to validate
     * @return bool Whether email is valid
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username
     * 
     * @param string $username Username to validate
     * @return array Validation result
     */
    public static function validateUsername($username) {
        $errors = [];
        
        // Check length
        if (strlen($username) < 3 || strlen($username) > 20) {
            $errors[] = 'Username must be between 3 and 20 characters long';
        }
        
        // Check allowed characters
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, underscores and hyphens';
        }
        
        if (count($errors) > 0) {
            return ['valid' => false, 'errors' => $errors];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Secure an input against XSS
     * 
     * @param string $input Input to secure
     * @return string Secured input
     */
    public static function secureInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Start session securely
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            // Start session
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } else if (time() - $_SESSION['last_regeneration'] > 1800) {
                // Regenerate session ID every 30 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Check if a request is AJAX
     * 
     * @return bool Whether request is AJAX
     */
    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}