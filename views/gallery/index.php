<?php
$title = 'Gallery | Camagru';
$extraJs = ['/js/gallery.js'];
ob_start();
?>

<div class="gallery-container">
    <h1>Photo Gallery</h1>
    
    <?php if ($singleImage): ?>
        <div class="modal" id="imageModal">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="close">&times;</span>
                    <h2>Photo by <?= $singleImage['username'] ?></h2>
                </div>
                <div class="modal-body">
                    <div class="image-details">
                        <img src="/uploads/<?= $singleImage['filename'] ?>" alt="Image by <?= $singleImage['username'] ?>">
                        
                        <div class="image-actions">
                            <?php if (isLoggedIn()): ?>
                                <form action="/gallery/like" method="POST" class="like-form">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="image_id" value="<?= $singleImage['id'] ?>">
                                    <button type="submit" class="btn-like <?= $this->imageModel->isLikedByUser($singleImage['id'], getCurrentUserId()) ? 'liked' : '' ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn-like disabled">
                                    <i class="fas fa-heart"></i>
                                </button>
                            <?php endif; ?>
                            <span class="likes-count"><?= $this->imageModel->getLikesCount($singleImage['id']) ?> likes</span>
                        </div>
                        
                        <div class="comments-section">
                                <h3>Comments</h3>
                                
                                <?php if (isLoggedIn()): ?>
                                    <form action="/gallery/comment" method="POST" class="comment-form">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="image_id" value="<?= $singleImage['id'] ?>">
                                        <div class="form-group">
                                            <textarea name="content" placeholder="Add a comment..." required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">Post</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <p><a href="/login">Log in</a> to leave a comment.</p>
                                <?php endif; ?>
                                
                                <div class="comments-list">
                                    <?php if (empty($comments)): ?>
                                        <p>No comments yet.</p>
                                    <?php else: ?>
                                        <?php foreach ($comments as $comment): ?>
                                            <div class="comment" data-id="<?= $comment['id'] ?>">
                                                <div class="comment-header">
                                                    <span class="comment-author"><?= $comment['username'] ?></span>
                                                    <span class="comment-date"><?= formatDate($comment['created_at']) ?></span>
                                                </div>
                                                
                                                <div class="comment-content-container">
                                                    <div class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                                    
                                                    <?php if (isLoggedIn() && getCurrentUserId() == $comment['user_id']): ?>
                                                        <div class="comment-actions">
                                                            <button class="btn-edit-comment btn-link" title="Edit"><i class="fas fa-edit"></i></button>
                                                            <button class="btn-delete-comment btn-link" title="Delete"><i class="fas fa-trash"></i></button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (isLoggedIn() && getCurrentUserId() == $comment['user_id']): ?>
                                                    <div class="comment-edit-form" style="display: none;">
                                                        <form action="/gallery/comment/update" method="POST" class="edit-comment-form">
                                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                            <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                            <input type="hidden" name="image_id" value="<?= $singleImage['id'] ?>">
                                                            <div class="form-group">
                                                                <textarea name="content" required><?= htmlspecialchars($comment['content']) ?></textarea>
                                                            </div>
                                                            <div class="form-group edit-buttons">
                                                                <button type="button" class="btn btn-secondary btn-cancel-edit">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    
                                                    <form action="/gallery/comment/delete" method="POST" class="delete-comment-form" style="display: none;">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                                        <input type="hidden" name="image_id" value="<?= $singleImage['id'] ?>">
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($images)): ?>
        <div class="no-images">
            <p>No images found.</p>
            <?php if (isLoggedIn()): ?>
                <p><a href="/editor" class="btn btn-primary">Create your first image</a></p>
            <?php else: ?>
                <p><a href="/login" class="btn btn-primary">Login to create images</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="image-grid">
            <?php foreach ($images as $image): ?>
                <div class="image-card">
                    <a href="/gallery?image=<?= $image['id'] ?>" class="image-link">
                        <img src="/uploads/<?= $image['filename'] ?>" alt="Image by <?= $image['username'] ?>">
                    </a>
                    <div class="image-info">
                        <div class="image-author">By <?= $image['username'] ?></div>
                        <div class="image-meta">
                            <span><i class="fas fa-heart"></i> <?= $image['likes_count'] ?></span>
                            <span><i class="fas fa-comment"></i> <?= $image['comments_count'] ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/gallery?page=<?= $page - 1 ?>" class="btn btn-secondary">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="/gallery?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="/gallery?page=<?= $page + 1 ?>" class="btn btn-secondary">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';