<?php
$title = 'Photo Editor | Camagru';
$extraCss = ['/css/editor.css']; // Add the editor CSS
ob_start();
?>

<div class="editor-container">
    <h1>Photo Editor</h1>
    
    <div class="editor-layout">
        <div class="editor-main">
            <div class="editor-actions">
                <button id="startCamera" class="btn btn-primary"><i class="fas fa-camera"></i> Start Camera</button>
                <button id="switchToUpload" class="btn btn-secondary"><i class="fas fa-upload"></i> Switch to Upload</button>
                <!-- Moved the capture/create button here -->
                <button type="submit" id="captureButton" class="btn btn-success">
                    <i class="fas fa-camera"></i> Capture Photo
                </button>
            </div>
            
            <div class="camera-container">
                <div class="editor-workspace">
                    <video id="camera" autoplay playsinline></video>
                    <div id="overlay-container"></div>
                    <canvas id="canvas" style="display: none;"></canvas>
                    <div id="cameraPlaceholder" class="camera-placeholder">
                        <i class="fas fa-camera"></i>
                        <p>Camera will appear here</p>
                    </div>
                </div>

            </div>
            
            <div class="upload-container" style="display: none;">
                <div class="editor-workspace upload-preview">
                    <div class="upload-placeholder" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; text-align: center;">
                        <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No image selected</p>
                    </div>
                    <img id="previewImage" src="" alt="Upload Preview" style="display: none; max-height: 100%; max-width: 100%; margin: 0 auto;">
                    <div id="overlay-container-upload"></div>
                </div>
            </div>

            <!-- Overlay Controls -->
            <div class="overlay-controls" id="overlayControls">
                <div class="control-group">
                    <label for="scaleSlider">Scale: <span id="scaleValue">100%</span></label>
                    <input type="range" id="scaleSlider" min="10" max="200" value="100" class="slider">
                </div>
                <div class="control-group">
                    <label for="rotateSlider">Rotate: <span id="rotateValue">0°</span></label>
                    <input type="range" id="rotateSlider" min="-180" max="180" value="0" class="slider">
                </div>
                <div class="control-group">
                    <button id="moveLeftBtn" class="btn btn-small btn-secondary"><i class="fas fa-arrow-left"></i></button>
                    <button id="moveRightBtn" class="btn btn-small btn-secondary"><i class="fas fa-arrow-right"></i></button>
                    <button id="moveUpBtn" class="btn btn-small btn-secondary"><i class="fas fa-arrow-up"></i></button>
                    <button id="moveDownBtn" class="btn btn-small btn-secondary"><i class="fas fa-arrow-down"></i></button>
                    <button id="resetOverlayBtn" class="btn btn-small btn-secondary"><i class="fas fa-sync-alt"></i> Reset</button>
                    <!-- Moved the Clear Overlay button here -->
                    <button id="clearOverlayBtn" class="btn btn-small btn-secondary"><i class="fas fa-times"></i> Clear</button>
                </div>
            </div>
            
            <div class="overlays-container">
                <h3>Select an Overlay</h3>
                <div class="overlays-grid">
                    <?php foreach ($overlays as $overlay): ?>
                        <div class="overlay-item" data-overlay="<?= $overlay ?>">
                            <img src="/img/overlays/<?= $overlay ?>" alt="<?= $overlay ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Hidden form for submitting the image data -->
            <form id="captureForm" action="/editor/capture" method="POST" style="display: none;">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="image_data" id="imageData">
                <input type="hidden" name="overlay" id="overlayInput" value="">
                <input type="hidden" name="overlay_data" id="overlayDataInput" value="">
                <input type="file" id="fileInput" name="image" accept="image/jpeg, image/png" style="display: none;">
            </form>
        </div>
        
        <div class="editor-sidebar">
            <h3>Your Photos</h3>
            
            <?php if (empty($userImages)): ?>
                <div class="no-images">
                    <p>You haven't created any photos yet.</p>
                </div>
            <?php else: ?>
                <div class="user-images">
                    <?php foreach ($userImages as $image): ?>
                        <div class="user-image">
                            <img src="/uploads/<?= $image['filename'] ?>" alt="Your photo">
                            <div class="user-image-actions">
                                <a href="/gallery?image=<?= $image['id'] ?>" class="btn btn-small btn-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="/editor/delete" method="POST" class="delete-form">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="image_id" value="<?= $image['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="/editor?page=<?= $page - 1 ?>" class="btn btn-small btn-secondary">&laquo;</a>
                        <?php endif; ?>
                        
                        <span class="page-info"><?= $page ?> / <?= $totalPages ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="/editor?page=<?= $page + 1 ?>" class="btn btn-small btn-secondary">&raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inline JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const startCameraBtn = document.getElementById('startCamera');
    const switchToUploadBtn = document.getElementById('switchToUpload');
    const clearOverlayBtn = document.getElementById('clearOverlayBtn');
    const camera = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const uploadContainer = document.querySelector('.upload-container');
    const cameraContainer = document.querySelector('.camera-container');
    const captureButton = document.getElementById('captureButton');
    const overlayItems = document.querySelectorAll('.overlay-item');
    const overlayInput = document.getElementById('overlayInput');
    const imageData = document.getElementById('imageData');
    const captureForm = document.getElementById('captureForm');
    const fileInput = document.getElementById('fileInput');
    const previewImage = document.getElementById('previewImage');
    const uploadPlaceholder = document.querySelector('.upload-placeholder');
    
    // Overlay manipulation elements
    const overlayContainer = document.getElementById('overlay-container');
    const overlayContainerUpload = document.getElementById('overlay-container-upload');
    const overlayControls = document.getElementById('overlayControls');
    
    // Overlay controls
    const scaleSlider = document.getElementById('scaleSlider');
    const rotateSlider = document.getElementById('rotateSlider');
    const scaleValue = document.getElementById('scaleValue');
    const rotateValue = document.getElementById('rotateValue');
    const moveLeftBtn = document.getElementById('moveLeftBtn');
    const moveRightBtn = document.getElementById('moveRightBtn');
    const moveUpBtn = document.getElementById('moveUpBtn');
    const moveDownBtn = document.getElementById('moveDownBtn');
    const resetOverlayBtn = document.getElementById('resetOverlayBtn');
    const overlayDataInput = document.getElementById('overlayDataInput');
    
    // Variables
    let stream = null;
    let selectedOverlay = null;
    let currentOverlayImg = null;
    let uploadOverlayImg = null;
    let isUploadMode = false;
    
    // Overlay position/transformation data
    let overlayData = {
        scale: 1,
        rotation: 0,
        x: 0,
        y: 0
    };
    
    // Event listeners
    if (startCameraBtn) {
        startCameraBtn.addEventListener('click', startCamera);
    }
    
    if (switchToUploadBtn) {
        switchToUploadBtn.addEventListener('click', switchToUpload);
    }
    
    if (clearOverlayBtn) {
        clearOverlayBtn.addEventListener('click', clearOverlaySelection);
    }
    
    overlayItems.forEach(item => {
        item.addEventListener('click', function() {
            selectOverlay(item);
        });
    });
    
    if (captureButton) {
        captureButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (isUploadMode) {
                uploadImage();
            } else {
                captureImage();
            }
        });
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            previewSelectedImage(this);
        });
    }
    
    // Overlay control event listeners
    if (scaleSlider) {
        scaleSlider.addEventListener('input', function() {
            const scale = this.value / 100;
            scaleValue.textContent = this.value + '%';
            overlayData.scale = scale;
            updateOverlayTransform();
        });
    }
    
    if (rotateSlider) {
        rotateSlider.addEventListener('input', function() {
            const rotation = parseInt(this.value);
            rotateValue.textContent = rotation + '°';
            overlayData.rotation = rotation;
            updateOverlayTransform();
        });
    }
    
    if (moveLeftBtn) {
        moveLeftBtn.addEventListener('click', function() {
            overlayData.x -= 10;
            updateOverlayTransform();
        });
    }
    
    if (moveRightBtn) {
        moveRightBtn.addEventListener('click', function() {
            overlayData.x += 10;
            updateOverlayTransform();
        });
    }
    
    if (moveUpBtn) {
        moveUpBtn.addEventListener('click', function() {
            overlayData.y -= 10;
            updateOverlayTransform();
        });
    }
    
    if (moveDownBtn) {
        moveDownBtn.addEventListener('click', function() {
            overlayData.y += 10;
            updateOverlayTransform();
        });
    }
    
    if (resetOverlayBtn) {
        resetOverlayBtn.addEventListener('click', function() {
            resetOverlay();
        });
    }
    
    // Make overlay draggable
    function makeOverlayDraggable(overlayImg) {
        let isDragging = false;
        let startX, startY;
        let originalX, originalY;
        
        overlayImg.addEventListener('mousedown', startDrag);
        overlayImg.addEventListener('touchstart', startDrag, { passive: false });
        
        function startDrag(e) {
            e.preventDefault();
            isDragging = true;
            
            // Get either touch or mouse position
            if (e.type === 'touchstart') {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            } else {
                startX = e.clientX;
                startY = e.clientY;
            }
            
            // Store original position
            originalX = overlayData.x;
            originalY = overlayData.y;
            
            // Add move and end event listeners
            document.addEventListener('mousemove', dragMove);
            document.addEventListener('touchmove', dragMove, { passive: false });
            document.addEventListener('mouseup', dragEnd);
            document.addEventListener('touchend', dragEnd);
            
            // Add dragging class
            overlayImg.classList.add('dragging');
        }
        
        function dragMove(e) {
            if (!isDragging) return;
            e.preventDefault();
            
            let clientX, clientY;
            
            // Get either touch or mouse position
            if (e.type === 'touchmove') {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            
            // Calculate the distance moved
            const deltaX = clientX - startX;
            const deltaY = clientY - startY;
            
            // Update overlay position
            overlayData.x = originalX + deltaX;
            overlayData.y = originalY + deltaY;
            updateOverlayTransform();
        }
        
        function dragEnd() {
            if (!isDragging) return;
            isDragging = false;
            
            // Remove event listeners
            document.removeEventListener('mousemove', dragMove);
            document.removeEventListener('touchmove', dragMove);
            document.removeEventListener('mouseup', dragEnd);
            document.removeEventListener('touchend', dragEnd);
            
            // Remove dragging class
            overlayImg.classList.remove('dragging');
        }
    }
    
    // Functions
    function selectOverlay(item) {
        // Log overlay selection
        console.log('Overlay selected:', item.dataset.overlay);
        
        // Remove selected class from all overlays
        overlayItems.forEach(overlay => {
            overlay.classList.remove('selected');
        });
        
        // Add selected class to clicked overlay
        item.classList.add('selected');
        
        // Set selected overlay
        selectedOverlay = item.dataset.overlay;
        
        // Update hidden input
        overlayInput.value = selectedOverlay;
        
        // Reset overlay transform data
        resetOverlay();
        
        // Determine which container to use
        const container = isUploadMode ? overlayContainerUpload : overlayContainer;
        
        // Clear existing overlay
        if (container) {
            container.innerHTML = '';
        }
        
        // Create new overlay image
        const overlayImg = document.createElement('img');
        overlayImg.src = '/img/overlays/' + selectedOverlay;
        overlayImg.className = 'editable-overlay';
        overlayImg.style.position = 'absolute';
        overlayImg.style.pointerEvents = 'auto';
        overlayImg.style.cursor = 'move';
        
        // Add the overlay to the container
        if (container) {
            container.appendChild(overlayImg);
            
            // Track the current overlay image
            if (isUploadMode) {
                uploadOverlayImg = overlayImg;
            } else {
                currentOverlayImg = overlayImg;
            }
            
            // Make the overlay draggable
            makeOverlayDraggable(overlayImg);
            
            // Show overlay controls
            if (overlayControls) {
                overlayControls.style.display = 'block';
            }
            
            // Apply initial transform
            updateOverlayTransform();
        }
    }
    
    function updateOverlayTransform() {
        // Determine which overlay image to update
        const overlayImg = isUploadMode ? uploadOverlayImg : currentOverlayImg;
        
        if (!overlayImg) return;
        
        // Apply transformation 
        overlayImg.style.transform = `translate(${overlayData.x}px, ${overlayData.y}px) scale(${overlayData.scale}) rotate(${overlayData.rotation}deg)`;
        
        // Center the overlay
        overlayImg.style.transformOrigin = 'center center';
        
        // Update the hidden input with overlay data - include viewport dimensions for accurate scaling
        if (overlayDataInput) {
            const container = isUploadMode ? 
                overlayContainerUpload.getBoundingClientRect() : 
                overlayContainer.getBoundingClientRect();
                
            const enhancedData = {
                ...overlayData,
                containerWidth: container.width,
                containerHeight: container.height
            };
            
            overlayDataInput.value = JSON.stringify(enhancedData);
        }
    }

    function resetOverlay() {
        // Reset transform data
        overlayData = {
            scale: 1,
            rotation: 0,
            x: 0,
            y: 0
        };
        
        // Reset controls
        if (scaleSlider) scaleSlider.value = 100;
        if (rotateSlider) rotateSlider.value = 0; // Reset to center (0 degrees)
        if (scaleValue) scaleValue.textContent = '100%';
        if (rotateValue) rotateValue.textContent = '0°';
        
        // Apply reset transform
        updateOverlayTransform();
    }
    
    function clearOverlaySelection() {
        // Remove selected class from all overlays
        overlayItems.forEach(overlay => {
            overlay.classList.remove('selected');
        });
        
        // Clear selected overlay
        selectedOverlay = null;
        
        // Update hidden input
        overlayInput.value = '';
        
        // Clear overlays
        if (overlayContainer) {
            overlayContainer.innerHTML = '';
            currentOverlayImg = null;
        }
        
        if (overlayContainerUpload) {
            overlayContainerUpload.innerHTML = '';
            uploadOverlayImg = null;
        }
        
        // Hide controls
        if (overlayControls) {
            overlayControls.style.display = 'none';
        }
    }
    
    function startCamera() {
        console.log('Starting camera');
        
        // Set mode
        isUploadMode = false;
        
        // Update button text
        captureButton.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
        
        // Switch UI
        cameraContainer.style.display = 'block';
        uploadContainer.style.display = 'none';
        startCameraBtn.classList.add('btn-primary');
        startCameraBtn.classList.remove('btn-secondary');
        switchToUploadBtn.classList.add('btn-secondary');
        switchToUploadBtn.classList.remove('btn-primary');
        
        // Check if camera is already running
        if (stream) return;
        
        // Get user media
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(mediaStream) {
                    stream = mediaStream;
                    camera.srcObject = stream;
                    camera.style.display = 'block';
                    cameraPlaceholder.style.display = 'none';
                    
                    // Enable capture button
                    captureButton.disabled = false;
                    
                    // If there's a selected overlay, reapply it
                    if (selectedOverlay) {
                        // Find the corresponding overlay item
                        overlayItems.forEach(item => {
                            if (item.dataset.overlay === selectedOverlay) {
                                selectOverlay(item);
                            }
                        });
                    }
                })
                .catch(function(error) {
                    console.error('Error accessing camera:', error);
                    alert('Could not access the camera. Please make sure you have a camera connected and have given permission to use it.');
                });
        } else {
            alert('Your browser does not support camera access. Please try a different browser.');
        }
    }
    
    function stopCamera() {
        console.log('Stopping camera');
        
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            camera.srcObject = null;
            camera.style.display = 'none';
            cameraPlaceholder.style.display = 'flex';
        }
    }
    
    function switchToUpload() {
        console.log('Switching to upload mode');
        
        // Set mode
        isUploadMode = true;
        
        // Update button text
        captureButton.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
        
        // Open file dialog directly
        fileInput.click();
        
        // Stop camera if running
        stopCamera();
        
        // Switch UI
        cameraContainer.style.display = 'none';
        uploadContainer.style.display = 'block';
        startCameraBtn.classList.remove('btn-primary');
        startCameraBtn.classList.add('btn-secondary');
        switchToUploadBtn.classList.remove('btn-secondary');
        switchToUploadBtn.classList.add('btn-primary');
    }
    
    function previewSelectedImage(input) {
        console.log('Previewing selected image');
        
        if (input.files && input.files[0]) {
            // Check file size
            const maxSize = 1 * 1024 * 1024; // 1MB
            if (input.files[0].size > maxSize) {
                alert('File is too large. Please select an image smaller than 1MB.');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
                
                // If there's a selected overlay, apply it to the upload preview
                if (selectedOverlay) {
                    // Find the corresponding overlay item
                    overlayItems.forEach(item => {
                        if (item.dataset.overlay === selectedOverlay) {
                            selectOverlay(item);
                        }
                    });
                }
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            previewImage.style.display = 'none';
            uploadPlaceholder.style.display = 'flex';
            
            // Clear overlay container
            if (overlayContainerUpload) {
                overlayContainerUpload.innerHTML = '';
                uploadOverlayImg = null;
            }
            
            // Hide controls
            if (overlayControls) {
                overlayControls.style.display = 'none';
            }
        }
    }
    
    function captureImage() {
        console.log('Capturing image');
        
        if (!stream) return;
        
        // Set canvas dimensions
        canvas.width = camera.videoWidth;
        canvas.height = camera.videoHeight;
        
        // Draw video frame to canvas
        const context = canvas.getContext('2d');
        context.drawImage(camera, 0, 0, canvas.width, canvas.height);
        
        // Get image data
        const data = canvas.toDataURL('image/png');
        imageData.value = data;
        
        // Make sure we always have a value for overlay
        if (!overlayInput.value) {
            overlayInput.value = '';
        }
        
        // Add overlay data to form if an overlay is selected
        if (currentOverlayImg && overlayDataInput) {
            // Get actual preview and target dimensions
            const container = overlayContainer.getBoundingClientRect();
            
            // Enhance overlay data with preview and target dimensions
            const enhancedData = {
                ...overlayData,
                containerWidth: container.width,
                containerHeight: container.height
            };
            
            overlayDataInput.value = JSON.stringify(enhancedData);
            console.log('Overlay data:', overlayDataInput.value);
        } else {
            overlayDataInput.value = '';
        }
        
        console.log('Submitting capture form with overlay:', overlayInput.value);
        
        // Submit form with AJAX
        const formData = new FormData(captureForm);
        
        // Show loading state
        captureButton.disabled = true;
        captureButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/editor/capture', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response is not valid JSON:', text);
                    throw new Error('Server returned invalid JSON. Check server logs for PHP errors.');
                }
            });
        })
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else if (data.error) {
                alert('Error: ' + data.error);
                captureButton.disabled = false;
                captureButton.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred: ' + error.message);
            captureButton.disabled = false;
            captureButton.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
        });
    }
    
    function uploadImage() {
        console.log('Uploading image');
        
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
            alert('Please select an image first.');
            switchToUpload(); // Open file dialog again
            return;
        }
        
        // Add overlay data to form if an overlay is selected
        if (uploadOverlayImg && overlayDataInput) {
            // Get container dimensions
            const container = overlayContainerUpload.getBoundingClientRect();
            
            // Create enhanced data object with all necessary information
            const enhancedData = {
                ...overlayData,
                containerWidth: container.width,
                containerHeight: container.height
            };
            
            overlayDataInput.value = JSON.stringify(enhancedData);
            console.log('Upload overlay data:', overlayDataInput.value);
        } else {
            overlayDataInput.value = '';
            console.log('No overlay selected for upload or overlay element not found');
        }
        
        // Show loading state
        captureButton.disabled = true;
        captureButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Create form data for AJAX request
        const formData = new FormData();
        formData.append('csrf_token', captureForm.querySelector('[name="csrf_token"]').value);
        formData.append('overlay', overlayInput.value);
        formData.append('overlay_data', overlayDataInput.value);
        formData.append('image', fileInput.files[0]);
        
        // Log form data for debugging
        console.log('Uploading with overlay:', overlayInput.value);
        console.log('File size:', fileInput.files[0].size);
        
        fetch('/editor/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 413) {
                    throw new Error('The image is too large.');
                }
                return response.text().then(text => {
                    console.log('Server error response:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Server error: ' + text);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                captureButton.disabled = false;
                captureButton.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Error: ' + error.message);
            captureButton.disabled = false;
            captureButton.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
        });
    }

    
    // Initialize camera mode by default
    startCamera();
});
</script>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';
?>