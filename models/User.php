<?php

// Add this at the top of the file to include the Database class
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($username, $email, $password) {
        $token = generateRandomString();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, token) VALUES (?, ?, ?, ?)";
        $this->db->query($sql, [$username, $email, $hashedPassword, $token]);
        
        return [
            'id' => $this->db->lastInsertId(),
            'token' => $token
        ];
    }
    
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->fetch($sql, [$email]);
    }
    
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        return $this->db->fetch($sql, [$username]);
    }
    
    public function findByToken($token) {
        $sql = "SELECT * FROM users WHERE token = ?";
        return $this->db->fetch($sql, [$token]);
    }
    
    public function verify($id) {
        $sql = "UPDATE users SET verified = TRUE, token = NULL WHERE id = ?";
        $this->db->query($sql, [$id]);
        return true;
    }
    
    public function updateToken($id, $token) {
        $sql = "UPDATE users SET token = ? WHERE id = ?";
        $this->db->query($sql, [$token, $id]);
        return true;
    }
    
    public function updatePassword($id, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ?, token = NULL WHERE id = ?";
        $this->db->query($sql, [$hashedPassword, $id]);
        return true;
    }
    
    public function updateProfile($id, $username, $email, $notification_enabled) {
        $sql = "UPDATE users SET username = ?, email = ?, notification_enabled = ? WHERE id = ?";
        $this->db->query($sql, [$username, $email, $notification_enabled, $id]);
        return true;
    }
    
    public function toggleNotifications($id, $enabled) {
        $sql = "UPDATE users SET notification_enabled = ? WHERE id = ?";
        $this->db->query($sql, [$enabled, $id]);
        return true;
    }
    
    public function validateLogin($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            if (!$user['verified']) {
                return 'not_verified';
            }
            return $user;
        }
        
        return false;
    }
    
    public function checkEmailExists($email) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $count = $this->db->fetchColumn($sql, [$email]);
        return $count > 0;
    }
    
    public function checkUsernameExists($username) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $count = $this->db->fetchColumn($sql, [$username]);
        return $count > 0;
    }
}