<?php
$title = 'Register | Camagru';
ob_start();

// Get form data from session if it exists
$formData = $_SESSION['form_data'] ?? null;
$formErrors = $_SESSION['form_errors'] ?? [];

// Clear form data and errors from session
unset($_SESSION['form_data']);
unset($_SESSION['form_errors']);
?>

<div class="auth-container">
    <div class="auth-card">
        <h2>Register</h2>
        
        <?php if (!empty($formErrors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($formErrors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="/register/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?= $formData['username'] ?? '' ?>" required autofocus>
                <small>Username must be between 3 and 20 characters and can only contain letters, numbers, underscores and hyphens.</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= $formData['email'] ?? '' ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <small>Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number and one special character.</small>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </div>
            
            <div class="form-links">
                <p>Already have an account? <a href="/login">Login</a></p>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';