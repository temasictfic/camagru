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
    }
    
    public function comment() {
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