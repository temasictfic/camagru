<?php

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    public function showProfile() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            setFlash('error', 'You must be logged in to view your profile');
            redirect('/login');
        }
        
        // Get user data
        $userId = getCurrentUserId();
        $user = $this->userModel->findById($userId);
        
        require_once BASE_PATH . '/views/user/profile.php';
    }
    
    public function updateProfile() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            setFlash('error', 'You must be logged in to update your profile');
            redirect('/login');
        }
        
        // Check if the request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/profile');
        }
        
        // Get form data
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $notificationEnabled = isset($_POST['notification_enabled']) ? 1 : 0;
        
        // Get current user ID
        $userId = getCurrentUserId();
        $currentUser = $this->userModel->findById($userId);
        
        // Validate inputs
        $errors = [];
        
        // Validate username (only if changed)
        if ($username !== $currentUser['username']) {
            $usernameCheck = Security::validateUsername($username);
            if (!$usernameCheck['valid']) {
                $errors = array_merge($errors, $usernameCheck['errors']);
            }
            
            // Check if username exists
            if ($this->userModel->checkUsernameExists($username)) {
                $errors[] = 'Username already exists';
            }
        }
        
        // Validate email (only if changed)
        if ($email !== $currentUser['email']) {
            if (!Security::validateEmail($email)) {
                $errors[] = 'Invalid email address';
            }
            
            // Check if email exists
            if ($this->userModel->checkEmailExists($email)) {
                $errors[] = 'Email already exists';
            }
        }
        
        // If there are errors, redirect back with error message
        if (count($errors) > 0) {
            $_SESSION['form_errors'] = $errors;
            redirect('/profile');
        }
        
        // Update profile
        $this->userModel->updateProfile($userId, $username, $email, $notificationEnabled);
        
        // Update session username if changed
        if ($username !== $currentUser['username']) {
            $_SESSION['username'] = $username;
        }
        
        // Set success message
        setFlash('success', 'Profile updated successfully');
        redirect('/profile');
    }
    
    public function updatePassword() {
        // Check if user is logged in
        if (!isLoggedIn()) {
            setFlash('error', 'You must be logged in to update your password');
            redirect('/login');
        }
        
        // Check if the request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/profile');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/profile');
        }
        
        // Get form data
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Get current user
        $userId = getCurrentUserId();
        $user = $this->userModel->findById($userId);
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $_SESSION['password_error'] = 'Current password is incorrect';
            redirect('/profile');
        }
        
        // Validate new password
        $errors = [];
        
        $passwordCheck = Security::validatePassword($newPassword);
        if (!$passwordCheck['valid']) {
            $errors = array_merge($errors, $passwordCheck['errors']);
        }
        
        // Check if passwords match
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If there are errors, redirect back with error message
        if (count($errors) > 0) {
            $_SESSION['password_errors'] = $errors;
            redirect('/profile');
        }
        
        // Update password
        $this->userModel->updatePassword($userId, $newPassword);
        
        // Set success message
        setFlash('success', 'Password updated successfully');
        redirect('/profile');
    }
}