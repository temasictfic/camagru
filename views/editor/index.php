<?php
$title = 'Photo Editor | Camagru';
$extraJs = [];  // Removing external JS to use inline JS only
ob_start();
?>

<div class="editor-container">
    <h1>Photo Editor</h1>
    
    <div class="editor-layout">
        <div class="editor-main">
            <div class="editor-actions">
                <button id="startCamera" class="btn btn-primary"><i class="fas fa-camera"></i> Start Camera</button>
                <button id="switchToUpload" class="btn btn-secondary"><i class="fas fa-upload"></i> Switch to Upload</button>
            </div>
            
            <div class="camera-container">
                <video id="camera" autoplay playsinline></video>
                <canvas id="canvas" style="display: none;"></canvas>
                <div id="cameraPlaceholder" class="camera-placeholder">
                    <i class="fas fa-camera"></i>
                    <p>Camera will appear here</p>
                </div>
            </div>
            
            <div class="upload-container" style="display: none;">
                <form id="uploadForm" action="/editor/upload" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="overlay" id="uploadOverlayInput" value="">
                    
                    <div class="upload-preview" style="margin-bottom: 15px; height: 450px; position: relative; background-color: #000; border-radius: 8px; overflow: hidden;">
                        <div class="upload-placeholder" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; text-align: center;">
                            <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No image selected</p>
                        </div>
                        <img id="previewImage" src="" alt="Upload Preview" style="display: none; max-height: 450px; max-width: 100%; margin: 0 auto;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <button type="button" id="chooseImageBtn" class="btn btn-primary btn-block" style="display: block; width: 100%;">
                            <i class="fas fa-upload"></i> Choose Image
                        </button>
                        <input type="file" id="fileInput" name="image" accept="image/jpeg, image/png" style="display: none;">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" id="createPhotoBtn" class="btn btn-success btn-block" style="display: block; width: 100%;">
                            <i class="fas fa-plus-circle"></i> Create Photo
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="overlays-container">
                <button id="clearOverlayBtn" class="btn btn-secondary" style="margin-bottom: 10px;">
                    <i class="fas fa-times"></i> Clear Overlay Selection
                </button>
                <h3>Select an Overlay</h3>
                <div class="overlays-grid">
                    <?php foreach ($overlays as $overlay): ?>
                        <div class="overlay-item" data-overlay="<?= $overlay ?>">
                            <img src="/img/overlays/<?= $overlay ?>" alt="<?= $overlay ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="capture-action">
                <form id="captureForm" action="/editor/capture" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="image_data" id="imageData">
                    <input type="hidden" name="overlay" id="overlayInput" value="">
                    
                    <button type="submit" id="captureButton" class="btn btn-success btn-block">
                        <i class="fas fa-camera"></i> Capture Photo
                    </button>
                </form>
            </div>
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
    const camera = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const cameraPlaceholder = document.getElementById('cameraPlaceholder');
    const uploadContainer = document.querySelector('.upload-container');
    const cameraContainer = document.querySelector('.camera-container');
    const captureButton = document.getElementById('captureButton');
    const overlayItems = document.querySelectorAll('.overlay-item');
    const overlayInput = document.getElementById('overlayInput');
    const uploadOverlayInput = document.getElementById('uploadOverlayInput');
    const imageData = document.getElementById('imageData');
    const captureForm = document.getElementById('captureForm');
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const chooseImageBtn = document.getElementById('chooseImageBtn');
    const createPhotoBtn = document.getElementById('createPhotoBtn');
    const previewImage = document.getElementById('previewImage');
    const uploadPlaceholder = document.querySelector('.upload-placeholder');
    const clearOverlayBtn = document.getElementById('clearOverlayBtn');
    
    // Debug logging
    console.log('DOM elements initialized');
    
    // Variables
    let stream = null;
    let selectedOverlay = null;
    
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
    
    if (captureForm) {
        captureForm.addEventListener('submit', function(e) {
            e.preventDefault();
            captureImage();
        });
    }
    
    if (chooseImageBtn) {
        chooseImageBtn.addEventListener('click', function() {
            if (fileInput) {
                fileInput.click();
            }
        });
    }
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            previewSelectedImage(this);
        });
    }
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            uploadImage();
        });
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
        
        // Update hidden inputs
        overlayInput.value = selectedOverlay;
        if (uploadOverlayInput) {
            uploadOverlayInput.value = selectedOverlay;
            console.log('Upload overlay input set to:', uploadOverlayInput.value);
        }
    }
    
    function clearOverlaySelection() {
        console.log('Clearing overlay selection');
        
        // Remove selected class from all overlays
        overlayItems.forEach(overlay => {
            overlay.classList.remove('selected');
        });
        
        // Clear selected overlay
        selectedOverlay = null;
        overlayInput.value = '';
        if (uploadOverlayInput) {
            uploadOverlayInput.value = '';
        }
    }
    
    function startCamera() {
        console.log('Starting camera');
        
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
        
        // Stop camera if running
        stopCamera();
        
        // Reset upload form
        if (uploadForm) uploadForm.reset();
        if (previewImage) previewImage.style.display = 'none';
        if (uploadPlaceholder) uploadPlaceholder.style.display = 'flex';
        
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
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            previewImage.style.display = 'none';
            uploadPlaceholder.style.display = 'flex';
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
            return;
        }
        
        // Make sure overlay value is set
        const currentOverlay = selectedOverlay || '';
        uploadOverlayInput.value = currentOverlay;
        
        console.log('Uploading with overlay:', uploadOverlayInput.value);
        
        // Submit form with AJAX
        const formData = new FormData(uploadForm);
        
        // Log form data for debugging
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        // Show loading state
        createPhotoBtn.disabled = true;
        createPhotoBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
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
                createPhotoBtn.disabled = false;
                createPhotoBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            alert('Error: ' + error.message);
            createPhotoBtn.disabled = false;
            createPhotoBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';
?>