<?php

// Add this at the top of the file to include the Database class
require_once __DIR__ . '/../config/database.php';

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
    
    /**
     * Update a comment
     * 
     * @param int $commentId Comment ID
     * @param int $userId User ID (for validation)
     * @param string $content New comment content
     * @return bool Whether the update was successful
     */
    public function update($commentId, $userId, $content) {
        // First check if the comment belongs to the user
        $sql = "SELECT COUNT(*) FROM comments WHERE id = ? AND user_id = ?";
        $count = $this->db->fetchColumn($sql, [$commentId, $userId]);
        
        if ($count === 0) {
            return false;
        }
        
        // Update the comment
        $sql = "UPDATE comments SET content = ? WHERE id = ?";
        $this->db->query($sql, [$content, $commentId]);
        return true;
    }
    
    /**
     * Delete a comment
     * 
     * @param int $commentId Comment ID
     * @param int $userId User ID (for validation)
     * @return bool Whether the deletion was successful
     */
    public function delete($commentId, $userId) {
        // First check if the comment belongs to the user
        $sql = "SELECT COUNT(*) FROM comments WHERE id = ? AND user_id = ?";
        $count = $this->db->fetchColumn($sql, [$commentId, $userId]);
        
        if ($count === 0) {
            return false;
        }
        
        // Delete the comment
        $sql = "DELETE FROM comments WHERE id = ?";
        $this->db->query($sql, [$commentId]);
        return true;
    }
    
    /**
     * Find a comment by ID
     * 
     * @param int $commentId Comment ID
     * @return array|bool Comment data or false if not found
     */
    public function findById($commentId) {
        $sql = "SELECT c.*, u.username 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?";
        return $this->db->fetch($sql, [$commentId]);
    }
}