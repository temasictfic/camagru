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
    
    /**
     * Process a captured image
     */
    public function captureImage() {
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Set content type header for AJAX requests
            if (Security::isAjaxRequest()) {
                header('Content-Type: application/json');
            }
            
            // Buffer output to catch any warnings/notices
            ob_start();
            
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
            
            // Get image data and overlay
            $imageData = $_POST['image_data'] ?? '';
            $overlayName = $_POST['overlay'] ?? '';
            
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
            
            // Clear the output buffer before processing image
            ob_end_clean();
            
            // Process image
            $userId = getCurrentUserId();
            $result = $this->imageProcessor->processWebcamImage($imageData, $overlayName);
            
            if (isset($result['error'])) {
                if (Security::isAjaxRequest()) {
                    http_response_code(500);
                    echo json_encode(['error' => $result['error']]);
                    exit;
                } else {
                    setFlash('error', 'Failed to process image: ' . $result['error']);
                    redirect('/editor');
                }
            }
            
            // Save image to database
            $imageId = $this->imageModel->create($userId, $result['filename']);
            
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
        } catch (Exception $e) {
            // Catch any uncaught exceptions and return as JSON for AJAX requests
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
                redirect('/editor');
            }
        } finally {
            // Restore original error reporting
            error_reporting($originalErrorReporting);
        }
    }
    
    /**
     * Process an uploaded image
     */
    public function uploadImage() {
        try {
            // Set content type header for AJAX requests
            if (Security::isAjaxRequest()) {
                header('Content-Type: application/json');
            }
            
            // Rest of the method...
        } catch (Exception $e) {
            // Catch any uncaught exceptions and return as JSON for AJAX requests
            if (Security::isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
                exit;
            } else {
                setFlash('error', 'An unexpected error occurred: ' . $e->getMessage());
                redirect('/editor');
            }
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