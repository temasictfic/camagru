<?php

class Comment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($imageId, $userId, $content) {
        $sql = "INSERT INTO comments (image_id, user_id, content) VALUES (?, ?, ?)";
        $this->db->query($sql, [$imageId, $userId, $content]);
        return $this->db->lastInsertId();
    }
    
    public function findByImageId($imageId) {
        $sql = "SELECT c.*, u.username 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.image_id = ?
                ORDER BY c.created_at DESC";
        return $this->db->fetchAll($sql, [$imageId]);
    }
    
    public function getImageOwner($imageId) {
        $sql = "SELECT u.id, u.email, u.username, u.notification_enabled 
                FROM users u
                JOIN images i ON u.id = i.user_id
                WHERE i.id = ?";
        return $this->db->fetch($sql, [$imageId]);
    }
}