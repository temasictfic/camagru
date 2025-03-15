<?php
$title = 'Reset Password | Camagru';
ob_start();

// Get form errors from session if they exist
$formErrors = $_SESSION['form_errors'] ?? [];

// Clear form errors from session
unset($_SESSION['form_errors']);

// Get token from URL
$token = sanitize($_GET['token'] ?? '');
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Reset Password</h2>
        
        <?php if (!empty($formErrors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="/reset-password/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="token" value="<?= $token ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required autofocus>
                <small>Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number and one special character.</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
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