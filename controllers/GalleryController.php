<?php

class GalleryController {
    private $imageModel;
    private $commentModel;
    private $emailService;
    
    public function __construct() {
        $this->imageModel = new Image();
        $this->commentModel = new Comment();
        $this->emailService = new Email();
    }
    
    public function index() {
        // Get page number from query parameters
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        // Get number of images per page
        $limit = 5;
        
        // Get images
        $images = $this->imageModel->findAll($page, $limit);
        
        // Get total number of images
        $totalImages = $this->imageModel->getCount();
        
        // Calculate total number of pages
        $totalPages = ceil($totalImages / $limit);
        
        // Get specific image if requested
        $singleImage = null;
        $comments = [];
        
        if (isset($_GET['image']) && is_numeric($_GET['image'])) {
            $imageId = (int)$_GET['image'];
            $singleImage = $this->imageModel->findById($imageId);
            
            if ($singleImage) {
                $comments = $this->commentModel->findByImageId($imageId);
            }
        }
        
        require_once BASE_PATH . '/views/gallery/index.php';
    }
    
    public function like() {
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Check if the user is logged in
            if (!isLoggedIn()) {
                if (Security::isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'You must be logged in to like images']);
                    exit;
                } else {
                    setFlash('error', 'You must be logged in to like images');
                    redirect('/login');
                }
            }
            
            // Check if the request is POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                if (Security::isAjaxRequest()) {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    exit;
                } else {
                    redirect('/gallery');
                }
            }
            
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                } else {
                    setFlash('error', 'Invalid form submission');
                    redirect('/gallery');
                }
            }
            
            // Get image ID
            $imageId = isset($_POST['image_id']) && is_numeric($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
            
            if ($imageId <= 0) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid image ID']);
                    exit;
                } else {
                    setFlash('error', 'Invalid image ID');
                    redirect('/gallery');
                }
            }
            
            // Get user ID
            $userId = getCurrentUserId();
            
            // Toggle like
            $liked = $this->imageModel->like($imageId, $userId);
            
            // Get updated like count
            $likesCount = $this->imageModel->getLikesCount($imageId);
            
            if (Security::isAjaxRequest()) {
                echo json_encode([
                    'success' => true,
                    'liked' => $liked,
                    'likes_count' => $likesCount
                ]);
                exit;
            } else {
                redirect('/gallery?image=' . $imageId);
            }
        } catch (Exception $e) {
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred');
                redirect('/gallery');
            }
        } finally {
            // Restore original error reporting
            error_reporting($originalErrorReporting);
        }
    }
    
    public function comment() {
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Check if the user is logged in
            if (!isLoggedIn()) {
                if (Security::isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'You must be logged in to comment']);
                    exit;
                } else {
                    setFlash('error', 'You must be logged in to comment');
                    redirect('/login');
                }
            }
            
            // Check if the request is POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                if (Security::isAjaxRequest()) {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    exit;
                } else {
                    redirect('/gallery');
                }
            }
            
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                } else {
                    setFlash('error', 'Invalid form submission');
                    redirect('/gallery');
                }
            }
            
            // Get image ID and comment content
            $imageId = isset($_POST['image_id']) && is_numeric($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
            $content = sanitize($_POST['content'] ?? '');
            
            if ($imageId <= 0) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid image ID']);
                    exit;
                } else {
                    setFlash('error', 'Invalid image ID');
                    redirect('/gallery');
                }
            }
            
            if (empty($content)) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Comment cannot be empty']);
                    exit;
                } else {
                    setFlash('error', 'Comment cannot be empty');
                    redirect('/gallery?image=' . $imageId);
                }
            }
            
            // Get user ID
            $userId = getCurrentUserId();
            
            // Add comment
            $commentId = $this->commentModel->create($imageId, $userId, $content);
            
            // Send notification to image owner
            $this->sendCommentNotification($imageId, $userId);
            
            if (Security::isAjaxRequest()) {
                // Get new comment with username
                $comments = $this->commentModel->findByImageId($imageId);
                $newComment = null;
                
                foreach ($comments as $comment) {
                    if ($comment['id'] == $commentId) {
                        $newComment = $comment;
                        
                        // For AJAX responses, format the date as ISO 8601 to let JavaScript handle localization
                        $timestamp = strtotime($newComment['created_at']);
                        $newComment['created_at_iso'] = date('c', $timestamp);
                        $newComment['created_at_formatted'] = formatDate($newComment['created_at']);
                        
                        // Add current user ID to allow frontend to know if user can edit/delete
                        $newComment['is_owner'] = ($userId == $newComment['user_id']);
                        
                        break;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'comment' => $newComment
                ]);
                exit;
            } else {
                setFlash('success', 'Comment added successfully');
                redirect('/gallery?image=' . $imageId);
            }
        } catch (Exception $e) {
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred');
                redirect('/gallery');
            }
        } finally {
            // Restore original error reporting
            error_reporting($originalErrorReporting);
        }
    }
    
    /**
     * Update a comment
     */
    public function updateComment() {
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Check if the user is logged in
            if (!isLoggedIn()) {
                if (Security::isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'You must be logged in to update comments']);
                    exit;
                } else {
                    setFlash('error', 'You must be logged in to update comments');
                    redirect('/login');
                }
            }
            
            // Check if the request is POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                if (Security::isAjaxRequest()) {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    exit;
                } else {
                    redirect('/gallery');
                }
            }
            
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                } else {
                    setFlash('error', 'Invalid form submission');
                    redirect('/gallery');
                }
            }
            
            // Get comment ID and content
            $commentId = isset($_POST['comment_id']) && is_numeric($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $content = sanitize($_POST['content'] ?? '');
            
            if ($commentId <= 0) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid comment ID']);
                    exit;
                } else {
                    setFlash('error', 'Invalid comment ID');
                    redirect('/gallery');
                }
            }
            
            if (empty($content)) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Comment cannot be empty']);
                    exit;
                } else {
                    setFlash('error', 'Comment cannot be empty');
                    redirect('/gallery');
                }
            }
            
            // Get user ID
            $userId = getCurrentUserId();
            
            // Update comment
            $success = $this->commentModel->update($commentId, $userId, $content);
            
            if (!$success) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to update this comment']);
                    exit;
                } else {
                    setFlash('error', 'You do not have permission to update this comment');
                    redirect('/gallery');
                }
            }
            
            if (Security::isAjaxRequest()) {
                // Get updated comment
                $comment = $this->commentModel->findById($commentId);
                
                if ($comment) {
                    // Format date for AJAX response
                    $timestamp = strtotime($comment['created_at']);
                    $comment['created_at_iso'] = date('c', $timestamp);
                    $comment['created_at_formatted'] = formatDate($comment['created_at']);
                    $comment['is_owner'] = true; // Must be the owner to update
                }
                
                echo json_encode([
                    'success' => true,
                    'comment' => $comment
                ]);
                exit;
            } else {
                setFlash('success', 'Comment updated successfully');
                redirect('/gallery?image=' . $_POST['image_id'] ?? '');
            }
        } catch (Exception $e) {
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred');
                redirect('/gallery');
            }
        } finally {
            // Restore original error reporting
            error_reporting($originalErrorReporting);
        }
    }
    
    /**
     * Delete a comment
     */
    public function deleteComment() {
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Check if the user is logged in
            if (!isLoggedIn()) {
                if (Security::isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['error' => 'You must be logged in to delete comments']);
                    exit;
                } else {
                    setFlash('error', 'You must be logged in to delete comments');
                    redirect('/login');
                }
            }
            
            // Check if the request is POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                if (Security::isAjaxRequest()) {
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    exit;
                } else {
                    redirect('/gallery');
                }
            }
            
            // Validate CSRF token
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                } else {
                    setFlash('error', 'Invalid form submission');
                    redirect('/gallery');
                }
            }
            
            // Get comment ID
            $commentId = isset($_POST['comment_id']) && is_numeric($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            
            if ($commentId <= 0) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid comment ID']);
                    exit;
                } else {
                    setFlash('error', 'Invalid comment ID');
                    redirect('/gallery');
                }
            }
            
            // Get user ID
            $userId = getCurrentUserId();
            
            // Delete comment
            $success = $this->commentModel->delete($commentId, $userId);
            
            if (!$success) {
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'You do not have permission to delete this comment']);
                    exit;
                } else {
                    setFlash('error', 'You do not have permission to delete this comment');
                    redirect('/gallery');
                }
            }
            
            if (Security::isAjaxRequest()) {
                echo json_encode([
                    'success' => true,
                    'comment_id' => $commentId
                ]);
                exit;
            } else {
                setFlash('success', 'Comment deleted successfully');
                redirect('/gallery?image=' . $_POST['image_id'] ?? '');
            }
        } catch (Exception $e) {
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred');
                redirect('/gallery');
            }
        } finally {
            // Restore original error reporting
            error_reporting($originalErrorReporting);
        }
    }
    
    private function sendCommentNotification($imageId, $commenterId) {
        // Get image owner
        $owner = $this->commentModel->getImageOwner($imageId);
        
        if (!$owner || $owner['id'] == $commenterId || !$owner['notification_enabled']) {
            return;
        }
        
        // Get commenter's username
        $commenterUsername = $_SESSION['username'];
        
        // Send notification email
        $this->emailService->sendCommentNotification(
            $owner['email'],
            $owner['username'],
            $imageId,
            $commenterUsername
        );
    }
}