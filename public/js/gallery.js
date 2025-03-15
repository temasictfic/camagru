document.addEventListener('DOMContentLoaded', function() {
    // Modal functionality
    const modal = document.getElementById('imageModal');
    if (modal) {
        const closeBtn = modal.querySelector('.close');
        
        // Close modal when clicking the X
        closeBtn.addEventListener('click', function() {
            window.history.pushState({}, '', window.location.pathname);
            modal.style.display = 'none';
        });
        
        // Close modal when clicking outside the modal content
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                window.history.pushState({}, '', window.location.pathname);
                modal.style.display = 'none';
            }
        });
        
        // Close modal when pressing ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                window.history.pushState({}, '', window.location.pathname);
                modal.style.display = 'none';
            }
        });
    }
    
    // AJAX Like functionality
    const likeForm = document.querySelector('.like-form');
    if (likeForm) {
        const likeBtn = likeForm.querySelector('.btn-like');
        const likesCountDisplay = document.querySelector('.likes-count');
        
        likeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Don't do anything if button is disabled
            if (likeBtn.classList.contains('disabled')) return;
            
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
            });
        });
    }
    
    // AJAX Comment functionality
    const commentForm = document.querySelector('.comment-form');
    if (commentForm) {
        const commentTextarea = commentForm.querySelector('textarea');
        const commentsList = document.querySelector('.comments-list');
        
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Don't submit if empty
            if (commentTextarea.value.trim() === '') return;
            
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
                    
                    // Clear textarea
                    commentTextarea.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        
        // Helper function to create comment element
        function createCommentElement(comment) {
            const commentElement = document.createElement('div');
            commentElement.className = 'comment';
            
            const commentHeader = document.createElement('div');
            commentHeader.className = 'comment-header';
            
            const commentAuthor = document.createElement('span');
            commentAuthor.className = 'comment-author';
            commentAuthor.textContent = comment.username;
            
            const commentDate = document.createElement('span');
            commentDate.className = 'comment-date';
            commentDate.textContent = formatDate(comment.created_at);
            
            const commentContent = document.createElement('div');
            commentContent.className = 'comment-content';
            commentContent.textContent = comment.content;
            
            commentHeader.appendChild(commentAuthor);
            commentHeader.appendChild(commentDate);
            
            commentElement.appendChild(commentHeader);
            commentElement.appendChild(commentContent);
            
            return commentElement;
        }
        
        // Helper function to format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        }
    }
    
    // Load more images for infinite scrolling (bonus)
    // This would be implemented here if doing the infinite scrolling bonus
});