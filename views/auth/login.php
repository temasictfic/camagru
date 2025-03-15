<?php
$title = 'Login | Camagru';
ob_start();
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Login</h2>
        <form action="/login/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-links">
                <a href="/forgot-password">Forgot password?</a>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </div>
            
            <div class="form-links">
                <p>Don't have an account? <a href="/register">Register</a></p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';