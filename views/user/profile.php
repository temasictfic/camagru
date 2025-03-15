<?php
$title = 'Profile | Camagru';
ob_start();

// Get form errors from session if they exist
$formErrors = $_SESSION['form_errors'] ?? [];
$passwordErrors = $_SESSION['password_errors'] ?? [];
$passwordError = $_SESSION['password_error'] ?? null;

// Clear form errors from session
unset($_SESSION['form_errors']);
unset($_SESSION['password_errors']);
unset($_SESSION['password_error']);
?>

<div class="profile-container">
    <h1>Your Profile</h1>
    
    <div class="profile-sections">
        <div class="profile-section">
            <h2>Update Profile</h2>
            
            <?php if (!empty($formErrors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($formErrors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="/profile/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= $user['username'] ?>" required>
                    <small>Username must be between 3 and 20 characters and can only contain letters, numbers, underscores and hyphens.</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= $user['email'] ?>" required>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="notification_enabled" name="notification_enabled" <?= $user['notification_enabled'] ? 'checked' : '' ?>>
                    <label for="notification_enabled">Enable email notifications for comments</label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
        
        <div class="profile-section">
            <h2>Change Password</h2>
            
            <?php if (!empty($passwordErrors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($passwordErrors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($passwordError): ?>
                <div class="alert alert-error">
                    <p><?= $passwordError ?></p>
                </div>
            <?php endif; ?>
            
            <form action="/profile/update-password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number and one special character.</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once BASE_PATH . '/views/templates/layout.php';