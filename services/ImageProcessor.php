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
    public function processWebcamImage($imageData, $overlayName, $overlayData = null) {
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
            
            // Apply overlay if provided
            if (!empty($overlayName)) {
                $result = $this->applyOverlay($image, $overlayName, $overlayData);
                if (isset($result['error'])) {
                    imagedestroy($image);
                    return $result;
                }
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
    public function processUploadedImage($file, $overlayName, $overlayData = null) {
        try {
            // For debugging
            error_log("Processing uploaded image with overlay: " . $overlayName);
            
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
            
            // Preserve transparency in the main image
            imagealphablending($image, true);
            imagesavealpha($image, true);
            
            // Apply overlay if provided
            if (!empty($overlayName)) {
                $result = $this->applyOverlay($image, $overlayName, $overlayData);
                if (isset($result['error'])) {
                    imagedestroy($image);
                    return $result;
                }
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
            
            // Save image - ensure PNG quality is high
            if (!@imagepng($image, $filepath, 0)) { // 0 = no compression for highest quality
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
            error_log("Exception in processUploadedImage: " . $e->getMessage());
            return ['error' => 'Exception processing image: ' . $e->getMessage()];
        }
    }
    
    /**
     * Apply overlay to an image with custom positioning and transformations
     */
    private function applyOverlay($image, $overlayName, $overlayData = null) {
        try {
            // Load overlay
            $overlayPath = $this->overlaysDir . $overlayName;
            
            if (!file_exists($overlayPath)) {
                error_log("Overlay not found: " . $overlayPath);
                return ['error' => 'Overlay not found: ' . $overlayName];
            }
            
            // Load overlay
            $overlay = @imagecreatefrompng($overlayPath);
            if (!$overlay) {
                error_log("Failed to load overlay: " . $overlayPath);
                return ['error' => 'Failed to load overlay: ' . $overlayName];
            }
            
            // Enable alpha blending and save alpha for the overlay
            imagealphablending($overlay, true);
            imagesavealpha($overlay, true);
            
            // Get dimensions
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);
            $overlayWidth = imagesx($overlay);
            $overlayHeight = imagesy($overlay);
            
            error_log("Image dimensions: {$imageWidth}x{$imageHeight}");
            error_log("Overlay dimensions: {$overlayWidth}x{$overlayHeight}");
            
            // Default values
            $scale = 0.4; // Default scale if none provided
            $rotation = 0;
            $x = 0;
            $y = 0;
            
            // Parse overlay data if provided
            if ($overlayData) {
                $data = json_decode($overlayData, true);
                if ($data) {
                    error_log("Overlay data: " . json_encode($data));
                    
                    // Get scale from data
                    $scale = isset($data['scale']) ? (float)$data['scale'] : 0.4;
                    
                    // Get rotation
                    $rotation = isset($data['rotation']) ? (float)$data['rotation'] : 0;
                    
                    // Get proportional coordinates
                    $containerWidth = isset($data['containerWidth']) ? (float)$data['containerWidth'] : $imageWidth;
                    $containerHeight = isset($data['containerHeight']) ? (float)$data['containerHeight'] : $imageHeight;
                    
                    // Calculate position based on proportions, not absolute pixels
                    // This ensures the relative position in the preview is maintained
                    if (isset($data['x']) && isset($data['y'])) {
                        // Convert the x,y offset to percentages of preview dimensions
                        $xPercent = ($data['x'] + $containerWidth/2) / $containerWidth;
                        $yPercent = ($data['y'] + $containerHeight/2) / $containerHeight;
                        
                        // Apply these percentages to the actual image dimensions
                        // Subtract half the overlay width/height to center it properly
                        $x = ($xPercent * $imageWidth);
                        $y = ($yPercent * $imageHeight);
                        
                        error_log("Position calculation: X%: {$xPercent}, Y%: {$yPercent}");
                        error_log("Final position: X: {$x}, Y: {$y}");
                    }
                }
            }
            
            
            // Calculate new dimensions after scaling
            // We need to scale based on the image dimensions ratio compared to the preview
            $scaleRatio = min($imageWidth / $overlayWidth, $imageHeight / $overlayHeight);
            $baseScale = $scaleRatio * 0.5; // Base scale to make overlay reasonable size relative to image
            $finalScale = $baseScale * $scale; // Apply user scale factor
            
            $newWidth = $overlayWidth * $finalScale;
            $newHeight = $overlayHeight * $finalScale;
            
            error_log("Scale ratio: {$scaleRatio}, Base scale: {$baseScale}, Final scale: {$finalScale}");
            error_log("New overlay dimensions: {$newWidth}x{$newHeight}");
            
            // Create a new image for the scaled overlay
            $scaledOverlay = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency
            imagealphablending($scaledOverlay, false);
            imagesavealpha($scaledOverlay, true);
            $transparent = imagecolorallocatealpha($scaledOverlay, 255, 255, 255, 127);
            imagefilledrectangle($scaledOverlay, 0, 0, $newWidth, $newHeight, $transparent);
            
            // Scale the overlay
            imagecopyresampled($scaledOverlay, $overlay, 0, 0, 0, 0, $newWidth, $newHeight, $overlayWidth, $overlayHeight);
            imagedestroy($overlay);
            
            // If rotation is needed, create another image for the rotated overlay
            if ($rotation != 0) {
                $rotatedOverlay = imagerotate($scaledOverlay, -$rotation, $transparent);
                imagedestroy($scaledOverlay);
                $scaledOverlay = $rotatedOverlay;
                
                // Update dimensions after rotation
                $newWidth = imagesx($scaledOverlay);
                $newHeight = imagesy($scaledOverlay);
            }
            
            // Calculate final position
            // If x and y are not set, center the overlay on the image
            $posX = isset($x) ? $x : ($imageWidth - $newWidth) / 2;
            $posY = isset($y) ? $y : ($imageHeight - $newHeight) / 2;
            
            error_log("Final overlay position: {$posX},{$posY}");
            
            // Enable alpha blending on the destination image before copying
            imagealphablending($image, true);
            
            // Copy overlay onto image while preserving transparency
            imagecopy($image, $scaledOverlay, $posX, $posY, 0, 0, $newWidth, $newHeight);
            imagedestroy($scaledOverlay);
            
            // Make sure we're saving the alpha channel
            imagesavealpha($image, true);
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("Exception applying overlay: " . $e->getMessage());
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