<?php

// Add this at the top of the file to include the Database class
require_once __DIR__ . '/../config/database.php';

class Image {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function create($userId, $filename) {
        $sql = "INSERT INTO images (user_id, filename) VALUES (?, ?)";
        $this->db->query($sql, [$userId, $filename]);
        return $this->db->lastInsertId();
    }
    
    public function findById($id) {
        $sql = "SELECT i.*, u.username 
                FROM images i
                JOIN users u ON i.user_id = u.id
                WHERE i.id = ?";
        return $this->db->fetch($sql, [$id]);
    }
    
    public function findByUserId($userId, $page = 1, $limit = 5) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM images WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$userId, $limit, $offset]);
    }
    
    public function findAll($page = 1, $limit = 5) {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT i.*, u.username,
                (SELECT COUNT(*) FROM likes WHERE image_id = i.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE image_id = i.id) as comments_count
                FROM images i
                JOIN users u ON i.user_id = u.id
                ORDER BY i.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$limit, $offset]);
    }
    
    public function delete($id, $userId) {
        // First check if the image belongs to the user
        $sql = "SELECT filename FROM images WHERE id = ? AND user_id = ?";
        $image = $this->db->fetch($sql, [$id, $userId]);
        
        if (!$image) {
            return false;
        }
        
        // Delete the image record
        $sql = "DELETE FROM images WHERE id = ?";
        $this->db->query($sql, [$id]);
        
        // Return the filename so we can delete the physical file
        return $image['filename'];
    }
    
    public function getCount() {
        $sql = "SELECT COUNT(*) FROM images";
        return $this->db->fetchColumn($sql);
    }
    
    public function getUserImagesCount($userId) {
        $sql = "SELECT COUNT(*) FROM images WHERE user_id = ?";
        return $this->db->fetchColumn($sql, [$userId]);
    }
    
    public function isLikedByUser($imageId, $userId) {
        $sql = "SELECT COUNT(*) FROM likes WHERE image_id = ? AND user_id = ?";
        $count = $this->db->fetchColumn($sql, [$imageId, $userId]);
        return $count > 0;
    }
    
    public function getLikesCount($imageId) {
        $sql = "SELECT COUNT(*) FROM likes WHERE image_id = ?";
        return $this->db->fetchColumn($sql, [$imageId]);
    }
    
    public function like($imageId, $userId) {
        // Check if already liked
        if ($this->isLikedByUser($imageId, $userId)) {
            // Unlike
            $sql = "DELETE FROM likes WHERE image_id = ? AND user_id = ?";
            $this->db->query($sql, [$imageId, $userId]);
            return false;
        } else {
            // Like
            $sql = "INSERT INTO likes (image_id, user_id) VALUES (?, ?)";
            $this->db->query($sql, [$imageId, $userId]);
            return true;
        }
    }
}