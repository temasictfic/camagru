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
    const imageUpload = document.getElementById('imageUpload');
    const uploadPreview = document.getElementById('uploadPreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const uploadSubmit = document.getElementById('uploadSubmit');
    
    // Variables
    let stream = null;
    let selectedOverlay = null;
    
    // Event listeners
    startCameraBtn.addEventListener('click', startCamera);
    switchToUploadBtn.addEventListener('click', switchToUpload);
    
    overlayItems.forEach(item => {
        item.addEventListener('click', function() {
            selectOverlay(item);
        });
    });
    
    captureForm.addEventListener('submit', function(e) {
        e.preventDefault();
        captureImage();
    });
    
    imageUpload.addEventListener('change', function() {
        previewImage();
    });
    
    // Functions
    function startCamera() {
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
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
            camera.srcObject = null;
            camera.style.display = 'none';
            cameraPlaceholder.style.display = 'flex';
        }
    }
    
    function switchToUpload() {
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
    
    function selectOverlay(item) {
        // Remove selected class from all overlays
        overlayItems.forEach(overlay => {
            overlay.classList.remove('selected');
        });
        
        // Add selected class to clicked overlay
        item.classList.add('selected');
        
        // Set selected overlay
        selectedOverlay = item.dataset.overlay;
        overlayInput.value = selectedOverlay;
        uploadOverlayInput.value = selectedOverlay;
        
        // Enable capture button
        captureButton.disabled = !selectedOverlay;
        uploadSubmit.disabled = !(selectedOverlay && imageUpload.files.length > 0);
    }
    
    function captureImage() {
        if (!stream || !selectedOverlay) return;
        
        // Set canvas dimensions
        canvas.width = camera.videoWidth;
        canvas.height = camera.videoHeight;
        
        // Draw video frame to canvas
        const context = canvas.getContext('2d');
        context.drawImage(camera, 0, 0, canvas.width, canvas.height);
        
        // Get image data
        const data = canvas.toDataURL('image/png');
        imageData.value = data;
        
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
                    // Try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    // If parsing fails, log the raw response and throw error
                    console.error('Server response is not valid JSON:', text);
                    throw new Error('Server returned invalid JSON. Check server logs for PHP errors.');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Refresh the page to show the new image
                window.location.reload();
            } else if (data.error) {
                alert('Error: ' + data.error);
                // Reset button
                captureButton.disabled = false;
                captureButton.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred: ' + error.message);
            
            // Reset button
            captureButton.disabled = false;
            captureButton.innerHTML = '<i class="fas fa-camera"></i> Capture Photo';
        });
    }
    
    function previewImage() {
        if (imageUpload.files && imageUpload.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                uploadPreview.src = e.target.result;
                uploadPreview.style.display = 'block';
                uploadPlaceholder.style.display = 'none';
                
                // Enable submit button if overlay is selected
                uploadSubmit.disabled = !selectedOverlay;
            }
            
            reader.readAsDataURL(imageUpload.files[0]);
        } else {
            uploadPreview.style.display = 'none';
            uploadPlaceholder.style.display = 'flex';
            uploadSubmit.disabled = true;
        }
    }
    
    // AJAX form submission for image upload
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!selectedOverlay || !imageUpload.files.length) {
            alert('Please select an overlay and upload an image.');
            return;
        }
        
        const formData = new FormData(uploadForm);
        
        // Show loading state
        uploadSubmit.disabled = true;
        uploadSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('/editor/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // First check if the response is ok
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON first
                        return Promise.reject(JSON.parse(text));
                    } catch (e) {
                        // If not JSON, return the text
                        return Promise.reject({ error: text || 'Server error' });
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Refresh the page to show the new image
                window.location.reload();
            } else if (data.error) {
                alert('Error: ' + data.error);
                // Reset button
                uploadSubmit.disabled = false;
                uploadSubmit.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            let errorMsg = 'An error occurred while uploading the image. Please try again.';
            
            if (error && error.error) {
                errorMsg = 'Error: ' + error.error;
            }
            
            alert(errorMsg);
            
            // Reset button
            uploadSubmit.disabled = false;
            uploadSubmit.innerHTML = '<i class="fas fa-plus-circle"></i> Create Photo';
        });
    });
});