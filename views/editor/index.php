<?php
$title = 'Photo Editor | Camagru';
$extraJs = ['/js/editor.js'];
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
                    <input type="hidden" name="overlay" id="uploadOverlayInput">
                    
                    <div class="upload-preview">
                        <div id="uploadPlaceholder" class="upload-placeholder">
                            <i class="fas fa-image"></i>
                            <p>No image selected</p>
                        </div>
                        <img id="uploadPreview" src="" alt="Upload Preview" style="display: none;">
                    </div>
                    
                    <div class="form-group">
                        <label for="imageUpload" class="btn btn-primary btn-block">
                            <i class="fas fa-upload"></i> Choose Image
                        </label>
                        <input type="file" id="imageUpload" name="image" accept="image/jpeg, image/png" style="display: none;">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" id="uploadSubmit" class="btn btn-success btn-block" disabled>
                            <i class="fas fa-plus-circle"></i> Create Photo
                        </button>
                    </div>
                </form>
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
            
            <div class="capture-action">
                <form id="captureForm" action="/editor/capture" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="image_data" id="imageData">
                    <input type="hidden" name="overlay" id="overlayInput">
                    
                    <button type="submit" id="captureButton" class="btn btn-success btn-block" disabled>
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

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';