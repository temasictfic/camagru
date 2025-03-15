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
    const imageData = document.getElementById('imageData');
    const captureForm = document.getElementById('captureForm');
    
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
    
    // Add Clear Overlay button
    addClearOverlayButton();
    
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
    
    // Functions
    function addClearOverlayButton() {
        const overlaysContainer = document.querySelector('.overlays-container');
        if (!overlaysContainer) return;
        
        // Check if button already exists
        let clearBtn = document.getElementById('clearOverlayBtn');
        if (clearBtn) return;
        
        // Create new button
        clearBtn = document.createElement('button');
        clearBtn.id = 'clearOverlayBtn';
        clearBtn.className = 'btn btn-secondary';
        clearBtn.style.marginBottom = '10px';
        clearBtn.innerHTML = '<i class="fas fa-times"></i> Clear Overlay Selection';
        clearBtn.addEventListener('click', clearOverlaySelection);
        
        // Find where to insert it
        const overlaysTitle = overlaysContainer.querySelector('h3');
        if (overlaysTitle) {
            overlaysContainer.insertBefore(clearBtn, overlaysTitle);
        } else {
            overlaysContainer.prepend(clearBtn);
        }
    }
    
    function startCamera() {
        // Switch UI
        cameraContainer.style.display = 'block';
        if (uploadContainer) uploadContainer.style.display = 'none';
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
                    
                    // Enable capture button when camera is active
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
        
        // Prompt user for manual actions
        //alert("Please click the 'Choose Image' button and then 'Create Photo' after selecting an image.");
        
        // Switch UI
        cameraContainer.style.display = 'none';
        if (uploadContainer) uploadContainer.style.display = 'block';
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
    }
    
    function clearOverlaySelection() {
        // Remove selected class from all overlays
        overlayItems.forEach(overlay => {
            overlay.classList.remove('selected');
        });
        
        // Clear selected overlay
        selectedOverlay = null;
        overlayInput.value = '';
    }
    
    function captureImage() {
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
        
        // Make sure we always have a value for overlay (even if empty)
        if (!overlayInput.value) {
            overlayInput.value = '';
        }
        
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
});