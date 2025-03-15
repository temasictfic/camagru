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
            
            // Get image data
            $imageData = $_POST['image_data'] ?? '';
            if (empty($imageData)) {
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing image data']);
                    exit;
                } else {
                    setFlash('error', 'Missing image data');
                    redirect('/editor');
                }
            }
            
            // Get overlay (may be empty string if no overlay selected)
            $overlayName = isset($_POST['overlay']) ? $_POST['overlay'] : '';
            
            // Clear the output buffer before processing image
            ob_end_clean();
            
            // Process image
            $userId = getCurrentUserId();
            $result = null;
            
            if (!empty($overlayName)) {
                // Process with overlay
                $result = $this->imageProcessor->processWebcamImage($imageData, $overlayName);
            } else {
                // Process without overlay
                $result = $this->imageProcessor->processWebcamImageWithoutOverlay($imageData);
            }
            
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
        // Turn off error reporting for this method to prevent HTML in JSON
        $originalErrorReporting = error_reporting();
        error_reporting(0);
        
        try {
            // Enable detailed logging
            ini_set('log_errors', 1);
            ini_set('error_log', BASE_PATH . '/upload_debug.log');
            
            // Set content type header for AJAX requests
            if (Security::isAjaxRequest()) {
                header('Content-Type: application/json');
            }
            
            // Log the request details
            error_log("Upload request: " . json_encode($_POST));
            error_log("Files: " . json_encode($_FILES));
            
            // Check if user is logged in
            if (!isLoggedIn()) {
                error_log("User not logged in");
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
                error_log("Method not POST");
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
                error_log("Invalid CSRF token");
                if (Security::isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Invalid CSRF token']);
                    exit;
                } else {
                    setFlash('error', 'Invalid form submission');
                    redirect('/editor');
                }
            }
            
            // Get overlay (may be empty string if no overlay selected)
            $overlayName = isset($_POST['overlay']) ? $_POST['overlay'] : '';
            error_log("Overlay from POST: " . $overlayName);
            
            // Check if file was uploaded
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'No image uploaded';
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
                    ];
                    $errorMessage = $errorMessages[$_FILES['image']['error']] ?? 'Unknown upload error';
                }
                
                error_log("Upload error: " . $errorMessage);
                
                if (Security::isAjaxRequest()) {
                    http_response_code(400);
                    echo json_encode(['error' => $errorMessage]);
                    exit;
                } else {
                    setFlash('error', $errorMessage);
                    redirect('/editor');
                }
            }
            
            // Process image
            $userId = getCurrentUserId();
            error_log("Processing image for user ID: " . $userId);
            
            // Always use processUploadedImage with the overlay
            $result = $this->imageProcessor->processUploadedImage($_FILES['image'], $overlayName);
            error_log("Process result: " . json_encode($result));
            
            if (isset($result['error'])) {
                error_log("Processing error: " . $result['error']);
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
            error_log("Image saved to database with ID: " . $imageId);
            
            if (Security::isAjaxRequest()) {
                echo json_encode([
                    'success' => true,
                    'image_id' => $imageId,
                    'filename' => $result['filename']
                ]);
                exit;
            } else {
                setFlash('success', 'Image uploaded successfully');
                redirect('/editor');
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
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