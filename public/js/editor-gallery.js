document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality for the editor page
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalImageId = document.getElementById('modalImageId');
    const commentImageId = document.getElementById('commentImageId');
    const likesCount = document.getElementById('likesCount');
    const likeButton = document.getElementById('likeButton');
    const commentsList = document.querySelector('.comments-list');
    
    // Get all image view buttons
    const viewImageBtns = document.querySelectorAll('.view-image-btn');
    const viewImages = document.querySelectorAll('.view-image');
    
    // Function to load image details for the modal
    function loadImageDetails(imageId) {
        // Update the image IDs in the forms
        if (modalImageId) modalImageId.value = imageId;
        if (commentImageId) commentImageId.value = imageId;
        
        // Fetch image details and comments via AJAX
        fetch(`/gallery?image=${imageId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Create a temporary element to parse the HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Extract information from the parsed HTML
            const imageElement = tempDiv.querySelector('.image-details img');
            const likesElement = tempDiv.querySelector('.likes-count');
            const commentsListElement = tempDiv.querySelector('.comments-list');
            const likeButtonElement = tempDiv.querySelector('.btn-like');
            
            // Update the modal with the extracted information
            if (imageElement && modalImage) {
                modalImage.src = imageElement.src;
            }
            
            if (likesElement && likesCount) {
                likesCount.textContent = likesElement.textContent;
            }
            
            if (commentsListElement && commentsList) {
                commentsList.innerHTML = commentsListElement.innerHTML;
                
                // Initialize any event listeners for the comments
                initializeCommentActions();
            }
            
            if (likeButtonElement && likeButton) {
                // Check if the like button has the 'liked' class
                if (likeButtonElement.classList.contains('liked')) {
                    likeButton.classList.add('liked');
                } else {
                    likeButton.classList.remove('liked');
                }
            }
            
            // Show the modal
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error loading image details:', error);
            alert('Failed to load image details. Please try again.');
        });
    }
    
    // Add click event to all view buttons
    viewImageBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const imageId = this.getAttribute('data-image-id');
            loadImageDetails(imageId);
        });
    });
    
    // Add click event to all images
    viewImages.forEach(img => {
        img.addEventListener('click', function() {
            const imageId = this.getAttribute('data-image-id');
            loadImageDetails(imageId);
        });
    });
    
    // Modal close button functionality
    const closeBtn = modal.querySelector('.close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            // Simply hide the modal instead of redirecting
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside of modal content
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Close modal when ESC key is pressed
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
        }
    });
    
    // AJAX Like functionality
    const likeForm = modal.querySelector('.like-form');
    if (likeForm) {
        likeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(likeForm);
            
            fetch('/gallery/like', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle liked class
                    if (data.liked) {
                        likeButton.classList.add('liked');
                    } else {
                        likeButton.classList.remove('liked');
                    }
                    
                    // Update like count
                    likesCount.textContent = data.likes_count + ' likes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // AJAX Comment functionality
    const commentForm = modal.querySelector('.comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const textarea = commentForm.querySelector('textarea');
            if (textarea.value.trim() === '') return;
            
            const formData = new FormData(commentForm);
            
            fetch('/gallery/comment', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.comment) {
                    // Create comment element
                    const commentElement = createCommentElement(data.comment);
                    
                    // Remove "No comments yet" message if it exists
                    const noCommentsMessage = commentsList.querySelector('p');
                    if (noCommentsMessage) {
                        commentsList.innerHTML = '';
                    }
                    
                    // Add comment to list
                    commentsList.insertBefore(commentElement, commentsList.firstChild);
                    
                    // Initialize event listeners for the new comment
                    initializeCommentActions();
                    
                    // Clear textarea
                    textarea.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Helper function to create comment element
    function createCommentElement(comment) {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment';
        commentElement.dataset.id = comment.id;
        
        // Create comment header
        const commentHeader = document.createElement('div');
        commentHeader.className = 'comment-header';
        
        const commentAuthor = document.createElement('span');
        commentAuthor.className = 'comment-author';
        commentAuthor.textContent = comment.username;
        
        const commentDate = document.createElement('span');
        commentDate.className = 'comment-date';
        
        // Format the date
        if (comment.created_at_iso) {
            commentDate.textContent = formatDateToLocalTime(comment.created_at_iso);
        } else {
            commentDate.textContent = formatDateToLocalTime(comment.created_at);
        }
        
        commentHeader.appendChild(commentAuthor);
        commentHeader.appendChild(commentDate);
        
        // Create comment content container
        const contentContainer = document.createElement('div');
        contentContainer.className = 'comment-content-container';
        
        const commentContent = document.createElement('div');
        commentContent.className = 'comment-content';
        commentContent.textContent = comment.content;
        contentContainer.appendChild(commentContent);
        
        // Add edit and delete buttons if user owns the comment
        if (comment.is_owner) {
            const commentActions = document.createElement('div');
            commentActions.className = 'comment-actions';
            
            const editButton = document.createElement('button');
            editButton.className = 'btn-edit-comment btn-link';
            editButton.title = 'Edit';
            editButton.innerHTML = '<i class="fas fa-edit"></i>';
            
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn-delete-comment btn-link';
            deleteButton.title = 'Delete';
            deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
            
            commentActions.appendChild(editButton);
            commentActions.appendChild(deleteButton);
            contentContainer.appendChild(commentActions);
        }
        
        commentElement.appendChild(commentHeader);
        commentElement.appendChild(contentContainer);
        
        // Add forms for edit and delete
        if (comment.is_owner) {
            // Get needed values
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const imageId = modalImageId.value;
            
            // Edit form
            const editFormContainer = document.createElement('div');
            editFormContainer.className = 'comment-edit-form';
            editFormContainer.style.display = 'none';
            
            editFormContainer.innerHTML = `
                <form action="/gallery/comment/update" method="POST" class="edit-comment-form">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="comment_id" value="${comment.id}">
                    <input type="hidden" name="image_id" value="${imageId}">
                    <div class="form-group">
                        <textarea name="content" required>${comment.content}</textarea>
                    </div>
                    <div class="form-group edit-buttons">
                        <button type="button" class="btn btn-secondary btn-cancel-edit">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            `;
            
            // Delete form
            const deleteForm = document.createElement('form');
            deleteForm.action = '/gallery/comment/delete';
            deleteForm.method = 'POST';
            deleteForm.className = 'delete-comment-form';
            deleteForm.style.display = 'none';
            
            deleteForm.innerHTML = `
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="comment_id" value="${comment.id}">
                <input type="hidden" name="image_id" value="${imageId}">
            `;
            
            commentElement.appendChild(editFormContainer);
            commentElement.appendChild(deleteForm);
        }
        
        return commentElement;
    }
    
    // Initialize comment actions (edit, delete)
    function initializeCommentActions() {
        const comments = document.querySelectorAll('.comment');
        
        comments.forEach(comment => {
            // Edit comment
            const editBtn = comment.querySelector('.btn-edit-comment');
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const editForm = comment.querySelector('.comment-edit-form');
                    
                    contentContainer.style.display = 'none';
                    editForm.style.display = 'block';
                });
            }
            
            // Cancel edit
            const cancelBtn = comment.querySelector('.btn-cancel-edit');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const editForm = comment.querySelector('.comment-edit-form');
                    
                    contentContainer.style.display = 'flex';
                    editForm.style.display = 'none';
                });
            }
            
            // Delete comment
            const deleteBtn = comment.querySelector('.btn-delete-comment');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this comment?')) {
                        const deleteForm = comment.querySelector('.delete-comment-form');
                        
                        // Create FormData object
                        const formData = new FormData(deleteForm);
                        
                        fetch('/gallery/comment/delete', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Animate removal
                                comment.classList.add('deleting');
                                setTimeout(() => {
                                    comment.remove();
                                    
                                    // If no more comments, show "No comments yet" message
                                    if (commentsList.children.length === 0) {
                                        commentsList.innerHTML = '<p>No comments yet.</p>';
                                    }
                                }, 500);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to delete comment. Please try again.');
                        });
                    }
                });
            }
            
            // Submit edit form
            const editForm = comment.querySelector('.edit-comment-form');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const contentElement = comment.querySelector('.comment-content');
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const editFormContainer = comment.querySelector('.comment-edit-form');
                    
                    const formData = new FormData(editForm);
                    
                    fetch('/gallery/comment/update', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.comment) {
                            // Update content
                            contentElement.textContent = data.comment.content;
                            
                            // Hide edit form, show content
                            editFormContainer.style.display = 'none';
                            contentContainer.style.display = 'flex';
                            
                            // Add update animation
                            comment.classList.add('updating');
                            setTimeout(() => {
                                comment.classList.remove('updating');
                            }, 500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to update comment. Please try again.');
                    });
                });
            }
        });
    }
    
    // Function to format date in local time with 24-hour format
    function formatDateToLocalTime(dateString) {
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false // Use 24-hour format instead of AM/PM
        };
        return date.toLocaleDateString(undefined, options);
    }
});