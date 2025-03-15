<?php
$title = 'Page Not Found | Camagru';
ob_start();
?>

<div class="error-container">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page you are looking for doesn't exist or has been moved.</p>
    <a href="/" class="btn btn-primary">Go to Homepage</a>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';