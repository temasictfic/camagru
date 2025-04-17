document.addEventListener('DOMContentLoaded', function() {
    // Initialize localization of dates when page loads
    localizeAllDates();
    
    // Modal functionality
    const modal = document.getElementById('imageModal');
    if (modal) {
        const closeBtn = modal.querySelector('.close');
        
        // Get the current page from the hidden input or close button data attribute
        const currentPage = document.getElementById('current-page')?.value || 
                           closeBtn?.getAttribute('data-page') || 
                           1;
        
        // Function to close modal and redirect to gallery with page parameter
        function closeModal() {
            // Hide the modal
            modal.style.display = 'none';
            
            // Build the redirect URL with page parameter
            const redirectUrl = `/gallery?page=${currentPage}`;
            
            // Update browser history and navigate
            window.location.href = redirectUrl;
        }
        
        // Close modal when clicking the X
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal();
            });
        }
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        // Close modal when pressing ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display !== 'none') {
                closeModal();
            }
        });
    }
    
    // AJAX Like functionality
    const likeForm = document.querySelector('.like-form');
    if (likeForm) {
        const likeBtn = likeForm.querySelector('.btn-like');
        const likesCountDisplay = document.querySelector('.likes-count');
        
        // Add a data attribute to track if the form is already being processed
        likeForm.setAttribute('data-processing', 'false');
        
        likeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Don't do anything if button is disabled or already processing
            if (likeBtn.classList.contains('disabled') || likeForm.getAttribute('data-processing') === 'true') return;
            
            // Set processing flag to true
            likeForm.setAttribute('data-processing', 'true');
            
            const formData = new FormData(likeForm);
            
            fetch('/gallery/like', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok.');
            })
            .then(data => {
                if (data.success) {
                    // Toggle liked class
                    if (data.liked) {
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.classList.remove('liked');
                    }
                    
                    // Update like count
                    likesCountDisplay.textContent = data.likes_count + ' likes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset processing flag
                likeForm.setAttribute('data-processing', 'false');
            });
        });
    }
    
    // AJAX Comment functionality
    const commentForm = document.querySelector('.comment-form');
    if (commentForm) {
        const commentTextarea = commentForm.querySelector('textarea');
        const commentsList = document.querySelector('.comments-list');
        
        // Add a data attribute to track if the form is already being processed
        commentForm.setAttribute('data-processing', 'false');
        
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Don't submit if empty or already processing
            if (commentTextarea.value.trim() === '' || commentForm.getAttribute('data-processing') === 'true') return;
            
            // Set processing flag to true
            commentForm.setAttribute('data-processing', 'true');
            
            const formData = new FormData(commentForm);
            
            fetch('/gallery/comment', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Network response was not ok.');
            })
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
                    initializeCommentActions(commentElement);
                    
                    // Clear textarea
                    commentTextarea.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                // Reset processing flag
                commentForm.setAttribute('data-processing', 'false');
            });
        });
    }
    
    // Comment edit and delete functionality - initialize for all existing comments
    initializeCommentActions();
    
    // Helper function to create comment element
    function createCommentElement(comment) {
        const commentElement = document.createElement('div');
        commentElement.className = 'comment';
        commentElement.dataset.id = comment.id;
        
        // Create header with author name and date
        const commentHeader = document.createElement('div');
        commentHeader.className = 'comment-header';
        
        // Create author element
        const commentAuthor = document.createElement('span');
        commentAuthor.className = 'comment-author';
        commentAuthor.textContent = comment.username;
        commentHeader.appendChild(commentAuthor);
        
        // Create date element - on same line as author
        const commentDate = document.createElement('span');
        commentDate.className = 'comment-date';
        
        // Format the date
        if (comment.created_at_iso) {
            commentDate.textContent = formatDateToLocalTime(comment.created_at_iso);
        } 
        else if (comment.created_at_formatted && comment.created_at_formatted.includes('data-utc')) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = comment.created_at_formatted;
            
            const dateElement = tempDiv.querySelector('.date-to-localize');
            if (dateElement && dateElement.getAttribute('data-utc')) {
                commentDate.textContent = formatDateToLocalTime(dateElement.getAttribute('data-utc'));
            } else {
                commentDate.textContent = formatDateToLocalTime(comment.created_at);
            }
        } 
        else {
            commentDate.textContent = formatDateToLocalTime(comment.created_at);
        }
        
        // Add date to header
        commentHeader.appendChild(commentDate);
        
        // Create content container with actions
        const contentContainer = document.createElement('div');
        contentContainer.className = 'comment-content-container';
        
        // Create comment content
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
            
            // Add actions to content container
            contentContainer.appendChild(commentActions);
        }
        
        // Add header and content container to comment
        commentElement.appendChild(commentHeader);
        commentElement.appendChild(contentContainer);
        
        // Add edit form if the user is the comment owner
        if (comment.is_owner) {
            // Create edit form
            const editFormContainer = document.createElement('div');
            editFormContainer.className = 'comment-edit-form';
            editFormContainer.style.display = 'none';
            
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const imageId = document.querySelector('input[name="image_id"]').value;
            const currentPage = document.getElementById('current-page')?.value || 1;
            
            editFormContainer.innerHTML = `
                <form action="/gallery/comment/update" method="POST" class="edit-comment-form">
                    <input type="hidden" name="csrf_token" value="${csrfToken}">
                    <input type="hidden" name="comment_id" value="${comment.id}">
                    <input type="hidden" name="image_id" value="${imageId}">
                    <input type="hidden" name="page" value="${currentPage}">
                    <div class="form-group">
                        <textarea name="content" required>${comment.content}</textarea>
                    </div>
                    <div class="form-group edit-buttons">
                        <button type="button" class="btn btn-secondary btn-cancel-edit">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            `;
            
            // Create delete form
            const deleteForm = document.createElement('form');
            deleteForm.action = '/gallery/comment/delete';
            deleteForm.method = 'POST';
            deleteForm.className = 'delete-comment-form';
            deleteForm.style.display = 'none';
            
            deleteForm.innerHTML = `
                <input type="hidden" name="csrf_token" value="${csrfToken}">
                <input type="hidden" name="comment_id" value="${comment.id}">
                <input type="hidden" name="image_id" value="${imageId}">
                <input type="hidden" name="page" value="${currentPage}">
            `;
            
            commentElement.appendChild(editFormContainer);
            commentElement.appendChild(deleteForm);
        }
        
        return commentElement;
    }
    
    // Function to initialize comment edit and delete buttons
    // Accepts optional parameter for a specific comment element
    function initializeCommentActions(specificComment = null) {
        // If a specific comment is provided, only initialize actions for that comment
        // Otherwise, initialize for all comments
        const comments = specificComment ? [specificComment] : document.querySelectorAll('.comment');
        
        comments.forEach(comment => {
            // Edit comment buttons
            const editBtn = comment.querySelector('.btn-edit-comment');
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    const content = comment.querySelector('.comment-content');
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const editForm = comment.querySelector('.comment-edit-form');
                    
                    // Hide the content container (which includes both content and action buttons)
                    contentContainer.style.display = 'none';
                    // Show the edit form
                    editForm.style.display = 'block';
                });
            }
            
            // Cancel edit buttons
            const cancelBtn = comment.querySelector('.btn-cancel-edit');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const editForm = comment.querySelector('.comment-edit-form');
                    
                    // Show the content container (which makes content and buttons visible again)
                    contentContainer.style.display = 'flex';
                    // Hide the edit form
                    editForm.style.display = 'none';
                });
            }
            
            // Delete comment buttons
            const deleteBtn = comment.querySelector('.btn-delete-comment');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this comment?')) {
                        const deleteForm = comment.querySelector('.delete-comment-form');
                        deleteComment(deleteForm, comment);
                    }
                });
            }
            
            // Edit comment forms
            const editForm = comment.querySelector('.edit-comment-form');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const contentContainer = comment.querySelector('.comment-content-container');
                    const content = comment.querySelector('.comment-content');
                    const editFormContainer = comment.querySelector('.comment-edit-form');
                    
                    updateComment(editForm, comment, content, contentContainer, editFormContainer);
                });
            }
        });
    }
    
    // Function to update a comment
    function updateComment(form, commentElement, contentElement, contentContainer, editFormElement) {
        const formData = new FormData(form);
        const commentId = formData.get('comment_id');
        
        // Don't submit if empty
        const textarea = form.querySelector('textarea');
        if (textarea.value.trim() === '') return;
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        
        fetch('/gallery/comment/update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok.');
        })
        .then(data => {
            if (data.success && data.comment) {
                // Update comment content
                contentElement.textContent = data.comment.content;
                
                // Hide edit form and show content container
                editFormElement.style.display = 'none';
                contentContainer.style.display = 'flex'; // Use flex to maintain layout
                
                // Add update animation
                commentElement.classList.add('updating');
                setTimeout(() => {
                    commentElement.classList.remove('updating');
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the comment.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    }
    
    // Function to delete a comment
    function deleteComment(form, commentElement) {
        const formData = new FormData(form);
        const commentId = formData.get('comment_id');
        
        // Show loading animation on the comment
        commentElement.classList.add('deleting');
        
        fetch('/gallery/comment/delete', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok.');
        })
        .then(data => {
            if (data.success) {
                // Remove the comment from the DOM after animation completes
                setTimeout(() => {
                    commentElement.remove();
                    
                    // If no more comments, show "No comments yet" message
                    const commentsList = document.querySelector('.comments-list');
                    if (commentsList && commentsList.children.length === 0) {
                        commentsList.innerHTML = '<p>No comments yet.</p>';
                    }
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the comment.');
            // Remove animation if there was an error
            commentElement.classList.remove('deleting');
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
    
    // Function to localize all dates on the page
    function localizeAllDates() {
        const dateElements = document.querySelectorAll('.date-to-localize');
        dateElements.forEach(element => {
            const utcDate = element.getAttribute('data-utc');
            if (utcDate) {
                element.textContent = formatDateToLocalTime(utcDate);
            }
        });
    }
});