document.addEventListener('DOMContentLoaded', function() {
    // Initialize localization of dates when page loads
    localizeAllDates();
    
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
            
            // If the server sent an ISO date, use it for localization
            if (comment.created_at_iso) {
                commentDate.textContent = formatDateToLocalTime(comment.created_at_iso);
            } 
            // If the server sent pre-formatted HTML with data attribute
            else if (comment.created_at_formatted && comment.created_at_formatted.includes('data-utc')) {
                // Create a temporary div to parse the HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = comment.created_at_formatted;
                
                // Extract the UTC timestamp from the data attribute
                const dateElement = tempDiv.querySelector('.date-to-localize');
                if (dateElement && dateElement.getAttribute('data-utc')) {
                    commentDate.textContent = formatDateToLocalTime(dateElement.getAttribute('data-utc'));
                } else {
                    // Fallback if no data attribute
                    commentDate.textContent = formatDateToLocalTime(comment.created_at);
                }
            } 
            // Fallback to direct formatting
            else {
                commentDate.textContent = formatDateToLocalTime(comment.created_at);
            }
            
            const commentContent = document.createElement('div');
            commentContent.className = 'comment-content';
            commentContent.textContent = comment.content;
            
            commentHeader.appendChild(commentAuthor);
            commentHeader.appendChild(commentDate);
            
            commentElement.appendChild(commentHeader);
            commentElement.appendChild(commentContent);
            
            return commentElement;
        }
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