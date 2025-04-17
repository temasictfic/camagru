document.addEventListener('DOMContentLoaded', function() {
    // Localize all dates when page loads
    localizeAllDates();
    
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
    
    // NOTE: Comment and like form handlers moved to gallery.js to prevent duplicate event handling
    
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