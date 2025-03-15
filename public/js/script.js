document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const menu = document.querySelector('.menu');
    
    if (hamburger && menu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            menu.classList.toggle('active');
        });
    }
    
    // Close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
    
    // Image modal
    const imageModal = document.getElementById('imageModal');
    if (imageModal) {
        const closeBtn = imageModal.querySelector('.close');
        
        closeBtn.addEventListener('click', function() {
            window.location.href = window.location.pathname;
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === imageModal) {
                window.location.href = window.location.pathname;
            }
        });
    }
    
    // AJAX form submissions
    const likeForm = document.querySelector('.like-form');
    if (likeForm) {
        likeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(likeForm);
            const button = likeForm.querySelector('.btn-like');
            const likesCount = document.querySelector('.likes-count');
            
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
                    if (data.liked) {
                        button.classList.add('liked');
                    } else {
                        button.classList.remove('liked');
                    }
                    
                    likesCount.textContent = data.likes_count + ' likes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    const commentForm = document.querySelector('.comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(commentForm);
            const textarea = commentForm.querySelector('textarea');
            const commentsList = document.querySelector('.comments-list');
            
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
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    
                    const commentHeader = document.createElement('div');
                    commentHeader.className = 'comment-header';
                    
                    const commentAuthor = document.createElement('span');
                    commentAuthor.className = 'comment-author';
                    commentAuthor.textContent = data.comment.username;
                    
                    const commentDate = document.createElement('span');
                    commentDate.className = 'comment-date';
                    
                    // Format date
                    const date = new Date(data.comment.created_at);
                    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric' };
                    commentDate.textContent = date.toLocaleDateString('en-US', options);
                    
                    const commentContent = document.createElement('div');
                    commentContent.className = 'comment-content';
                    commentContent.textContent = data.comment.content;
                    
                    commentHeader.appendChild(commentAuthor);
                    commentHeader.appendChild(commentDate);
                    
                    newComment.appendChild(commentHeader);
                    newComment.appendChild(commentContent);
                    
                    // Clear "No comments yet" message if it exists
                    if (commentsList.querySelector('p')) {
                        commentsList.innerHTML = '';
                    }
                    
                    // Add the new comment at the top
                    commentsList.insertBefore(newComment, commentsList.firstChild);
                    
                    // Clear textarea
                    textarea.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Delete image confirmation
    const deleteForms = document.querySelectorAll('.delete-form');
    if (deleteForms.length > 0) {
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    }
});