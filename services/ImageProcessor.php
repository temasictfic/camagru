<?php

class ImageProcessor {
    private $uploadsDir;
    private $overlaysDir;
    private $allowedExtensions;
    private $maxFileSize;
    
    public function __construct() {
        $this->uploadsDir = BASE_PATH . '/public/uploads/';
        $this->overlaysDir = BASE_PATH . '/public/img/overlays/';
        $this->allowedExtensions = ['jpg', 'jpeg', 'png'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        
        // Ensure paths end with slash
        if (substr($this->uploadsDir, -1) !== '/') {
            $this->uploadsDir .= '/';
        }
        
        if (substr($this->overlaysDir, -1) !== '/') {
            $this->overlaysDir .= '/';
        }
    }
    
    /**
     * Process a webcam image with overlay
     */
    public function processWebcamImage($imageData, $overlayName) {
        try {
            // Remove data URI prefix and decode base64
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $decodedData = base64_decode($imageData);
            
            if ($decodedData === false) {
                return ['error' => 'Invalid image data'];
            }
            
            // Create image from string
            $image = @imagecreatefromstring($decodedData);
            if (!$image) {
                return ['error' => 'Failed to create image from data'];
            }
            
            // Apply overlay
            $result = $this->applyOverlay($image, $overlayName);
            if (isset($result['error'])) {
                imagedestroy($image);
                return $result;
            }
            
            // Generate unique filename
            $filename = uniqid() . '.png';
            $filepath = $this->uploadsDir . $filename;
            
            // Ensure uploads directory exists
            if (!is_dir($this->uploadsDir)) {
                if (!@mkdir($this->uploadsDir, 0777, true)) {
                    return ['error' => 'Failed to create uploads directory: ' . $this->uploadsDir];
                }
            }
            
            // Check if directory is writable
            if (!is_writable($this->uploadsDir)) {
                @chmod($this->uploadsDir, 0777);
                if (!is_writable($this->uploadsDir)) {
                    return ['error' => 'Uploads directory is not writable: ' . $this->uploadsDir];
                }
            }
            
            // Save image
            if (!@imagepng($image, $filepath)) {
                imagedestroy($image);
                return ['error' => 'Failed to save image to: ' . $filepath];
            }
            
            imagedestroy($image);
            
            // Check if the file was actually created
            if (!file_exists($filepath)) {
                return ['error' => 'File was not created at: ' . $filepath];
            }
            
            return ['filename' => $filename];
        } catch (Exception $e) {
            return ['error' => 'Exception processing image: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process an uploaded image with overlay
     */
    public function processUploadedImage($file, $overlayName) {
        try {
            // Validate file
            $validationResult = $this->validateUploadedFile($file);
            if (isset($validationResult['error'])) {
                return $validationResult;
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Create image from file
            $image = null;
            if ($extension === 'jpg' || $extension === 'jpeg') {
                $image = @imagecreatefromjpeg($file['tmp_name']);
            } else if ($extension === 'png') {
                $image = @imagecreatefrompng($file['tmp_name']);
            }
            
            if (!$image) {
                return ['error' => 'Failed to create image from uploaded file'];
            }
            
            // Apply overlay
            $result = $this->applyOverlay($image, $overlayName);
            if (isset($result['error'])) {
                imagedestroy($image);
                return $result;
            }
            
            // Generate unique filename
            $filename = uniqid() . '.png';
            $filepath = $this->uploadsDir . $filename;
            
            // Ensure uploads directory exists
            if (!is_dir($this->uploadsDir)) {
                if (!@mkdir($this->uploadsDir, 0777, true)) {
                    return ['error' => 'Failed to create uploads directory: ' . $this->uploadsDir];
                }
            }
            
            // Check if directory is writable
            if (!is_writable($this->uploadsDir)) {
                @chmod($this->uploadsDir, 0777);
                if (!is_writable($this->uploadsDir)) {
                    return ['error' => 'Uploads directory is not writable: ' . $this->uploadsDir];
                }
            }
            
            // Save image
            if (!@imagepng($image, $filepath)) {
                imagedestroy($image);
                return ['error' => 'Failed to save image to: ' . $filepath];
            }
            
            imagedestroy($image);
            
            // Check if the file was actually created
            if (!file_exists($filepath)) {
                return ['error' => 'File was not created at: ' . $filepath];
            }
            
            return ['filename' => $filename];
        } catch (Exception $e) {
            return ['error' => 'Exception processing image: ' . $e->getMessage()];
        }
    }
    
    /**
     * Apply overlay to an image
     */
    private function applyOverlay($image, $overlayName) {
        try {
            $overlayPath = $this->overlaysDir . $overlayName;
            
            if (!file_exists($overlayPath)) {
                return ['error' => 'Overlay not found: ' . $overlayName];
            }
            
            // Load overlay
            $overlay = @imagecreatefrompng($overlayPath);
            if (!$overlay) {
                return ['error' => 'Failed to load overlay: ' . $overlayName];
            }
            
            // Get dimensions
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);
            $overlayWidth = imagesx($overlay);
            $overlayHeight = imagesy($overlay);
            
            // Resize overlay to fit the image while maintaining aspect ratio
            if ($imageWidth < $overlayWidth || $imageHeight < $overlayHeight) {
                $ratio = min($imageWidth / $overlayWidth, $imageHeight / $overlayHeight);
                $newWidth = $overlayWidth * $ratio;
                $newHeight = $overlayHeight * $ratio;
                
                $resizedOverlay = imagecreatetruecolor($newWidth, $newHeight);
                
                // Preserve transparency
                imagealphablending($resizedOverlay, false);
                imagesavealpha($resizedOverlay, true);
                $transparent = imagecolorallocatealpha($resizedOverlay, 255, 255, 255, 127);
                imagefilledrectangle($resizedOverlay, 0, 0, $newWidth, $newHeight, $transparent);
                
                imagecopyresampled($resizedOverlay, $overlay, 0, 0, 0, 0, $newWidth, $newHeight, $overlayWidth, $overlayHeight);
                imagedestroy($overlay);
                $overlay = $resizedOverlay;
                $overlayWidth = $newWidth;
                $overlayHeight = $newHeight;
            }
            
            // Calculate position to center overlay
            $posX = ($imageWidth - $overlayWidth) / 2;
            $posY = ($imageHeight - $overlayHeight) / 2;
            
            // Copy overlay onto image while preserving transparency
            imagecopy($image, $overlay, $posX, $posY, 0, 0, $overlayWidth, $overlayHeight);
            imagedestroy($overlay);
            
            return ['success' => true];
        } catch (Exception $e) {
            return ['error' => 'Exception applying overlay: ' . $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateUploadedFile($file) {
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            return ['error' => $errors[$file['error']] ?? 'Unknown upload error'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['error' => 'File size exceeds maximum allowed size (5MB)'];
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['error' => 'File type not allowed. Only JPG, JPEG and PNG files are accepted'];
        }
        
        // Additional security check for image type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowedMimeTypes)) {
            return ['error' => 'Invalid file type. Only image files are accepted'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Get available overlays
     */
    public function getOverlays() {
        $overlays = [];
        
        // Check if directory exists
        if (!is_dir($this->overlaysDir)) {
            return $overlays;
        }
        
        $files = scandir($this->overlaysDir);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && !is_dir($this->overlaysDir . $file)) {
                $info = pathinfo($file);
                if (isset($info['extension']) && strtolower($info['extension']) === 'png') {
                    $overlays[] = $file;
                }
            }
        }
        
        return $overlays;
    }
    
    /**
     * Delete an image
     */
    public function deleteImage($filename) {
        $filepath = $this->uploadsDir . $filename;
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }
}