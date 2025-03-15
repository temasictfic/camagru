<?php
$title = 'Forgot Password | Camagru';
ob_start();
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Forgot Password</h2>
        <p>Enter your email address and we'll send you a link to reset your password.</p>
        
        <form action="/forgot-password/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
            </div>
            
            <div class="form-links">
                <p>Remember your password? <a href="/login">Login</a></p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';