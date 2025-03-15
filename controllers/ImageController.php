<?php

class ImageController {
    private $imageModel;
    private $imageProcessor;
    
    public function __construct() {
        $this->imageModel = new Image();
        $this->imageProcessor = new ImageProcessor();
    }
    
    public function showEditor() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            setFlash('error', 'You must be logged in to access the editor');
            redirect('/login');
        }
        
        // Get all overlays
        $overlays = $this->imageProcessor->getOverlays();
        
        // Get user images
        $userId = getCurrentUserId();
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        
        $limit = 5;
        $userImages = $this->imageModel->findByUserId($userId, $page, $limit);
        $totalImages = $this->imageModel->getUserImagesCount($userId);
        $totalPages = ceil($totalImages / $limit);
        
        require_once BASE_PATH . '/views/editor/index.php';
    }
    
    public function captureImage() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            if (Security::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'You must be logged in to capture images']);
                exit;
            } else {
                setFlash('error', 'You must be logged in to capture images');
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
                redirect('/editor');
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
                redirect('/editor');
            }
        }
        
        // Get image data and overlay name
        $imageData = $_POST['image_data'] ?? '';
        $overlayName = sanitize($_POST['overlay'] ?? '');
        
        if (empty($imageData) || empty($overlayName)) {
            if (Security::isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing image data or overlay']);
                exit;
            } else {
                setFlash('error', 'Missing image data or overlay');
                redirect('/editor');
            }
        }
        
        // Process image
        $result = $this->imageProcessor->processWebcamImage($imageData, $overlayName);
        
        if (isset($result['error'])) {
            if (Security::isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
                exit;
            } else {
                setFlash('error', $result['error']);
                redirect('/editor');
            }
        }
        
        // Save image in database
        $userId = getCurrentUserId();
        $imageId = $this->imageModel->create($userId, $result['filename']);
        
        // Return success response
        if (Security::isAjaxRequest()) {
            echo json_encode([
                'success' => true,
                'image_id' => $imageId,
                'filename' => $result['filename']
            ]);
            exit;
        } else {
            setFlash('success', 'Image captured successfully');
            redirect('/editor');
        }
    }
    
    public function uploadImage() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            if (Security::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'You must be logged in to upload images']);
                exit;
            } else {
                setFlash('error', 'You must be logged in to upload images');
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
                redirect('/editor');
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
                redirect('/editor');
            }
        }
        
        // Check if file is uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            if (Security::isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                exit;
            } else {
                setFlash('error', 'No file uploaded');
                redirect('/editor');
            }
        }
        
        // Get overlay name
        $overlayName = sanitize($_POST['overlay'] ?? '');
        
        if (empty($overlayName)) {
            if (Security::isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['error' => 'No overlay selected']);
                exit;
            } else {
                setFlash('error', 'No overlay selected');
                redirect('/editor');
            }
        }
        
        // Process uploaded image
        $result = $this->imageProcessor->processUploadedImage($_FILES['image'], $overlayName);
        
        if (isset($result['error'])) {
            if (Security::isAjaxRequest()) {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
                exit;
            } else {
                setFlash('error', $result['error']);
                redirect('/editor');
            }
        }
        
        // Save image in database
        $userId = getCurrentUserId();
        $imageId = $this->imageModel->create($userId, $result['filename']);
        
        // Return success response
        if (Security::isAjaxRequest()) {
            echo json_encode([
                'success' => true,
                'image_id' => $imageId,
                'filename' => $result['filename']
            ]);
            exit;
        } else {
            setFlash('success', 'Image uploaded and processed successfully');
            redirect('/editor');
        }
    }
    
    public function deleteImage() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            if (Security::isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['error' => 'You must be logged in to delete images']);
                exit;
            } else {
                setFlash('error', 'You must be logged in to delete images');
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
                redirect('/editor');
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
                redirect('/editor');
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
                redirect('/editor');
            }
        }
        
        // Get user ID
        $userId = getCurrentUserId();
        
        // Delete image
        $filename = $this->imageModel->delete($imageId, $userId);
        
        if (!$filename) {
            if (Security::isAjaxRequest()) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to delete this image']);
                exit;
            } else {
                setFlash('error', 'You do not have permission to delete this image');
                redirect('/editor');
            }
        }
        
        // Delete physical file
        $this->imageProcessor->deleteImage($filename);
        
        // Return success response
        if (Security::isAjaxRequest()) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            setFlash('success', 'Image deleted successfully');
            redirect('/editor');
        }
    }
}