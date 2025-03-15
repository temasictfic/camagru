<?php

class AuthController {
    private $userModel;
    private $emailService;
    
    public function __construct() {
        $this->userModel = new User();
        $this->emailService = new Email();
    }
    
    public function showRegister() {
        require_once BASE_PATH . '/views/auth/register.php';
    }
    
    public function register() {
        // Check if the form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/register');
        }
        
        // Get form data
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        $errors = [];
        
        // Validate username
        $usernameCheck = Security::validateUsername($username);
        if (!$usernameCheck['valid']) {
            $errors = array_merge($errors, $usernameCheck['errors']);
        }
        
        // Check if username exists
        if ($this->userModel->checkUsernameExists($username)) {
            $errors[] = 'Username already exists';
        }
        
        // Validate email
        if (!Security::validateEmail($email)) {
            $errors[] = 'Invalid email address';
        }
        
        // Check if email exists
        if ($this->userModel->checkEmailExists($email)) {
            $errors[] = 'Email already exists';
        }
        
        // Validate password
        $passwordCheck = Security::validatePassword($password);
        if (!$passwordCheck['valid']) {
            $errors = array_merge($errors, $passwordCheck['errors']);
        }
        
        // Check if passwords match
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If there are errors, redirect back with error message
        if (count($errors) > 0) {
            $_SESSION['form_data'] = [
                'username' => $username,
                'email' => $email
            ];
            $_SESSION['form_errors'] = $errors;
            redirect('/register');
        }
        
        // Create user
        $result = $this->userModel->create($username, $email, $password);
        
        // Send verification email
        $this->emailService->sendVerificationEmail($email, $username, $result['token']);
        
        // Set success message and redirect to login page
        setFlash('success', 'Registration successful! Please check your email to verify your account.');
        redirect('/login');
    }
    
    public function showLogin() {
        require_once BASE_PATH . '/views/auth/login.php';
    }
    
    public function login() {
        // Check if the form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/login');
        }
        
        // Get form data
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate user
        $user = $this->userModel->validateLogin($username, $password);
        
        if ($user === 'not_verified') {
            setFlash('error', 'Your account is not verified. Please check your email for verification link.');
            redirect('/login');
        } else if (!$user) {
            setFlash('error', 'Invalid username or password');
            redirect('/login');
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to home page
        redirect('/');
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        redirect('/login');
    }
    
    public function verify() {
        // Get token from URL
        $token = sanitize($_GET['token'] ?? '');
        
        if (empty($token)) {
            setFlash('error', 'Invalid verification token');
            redirect('/login');
        }
        
        // Find user by token
        $user = $this->userModel->findByToken($token);
        
        if (!$user) {
            setFlash('error', 'Invalid verification token or account already verified');
            redirect('/login');
        }
        
        // Verify user
        $this->userModel->verify($user['id']);
        
        // Set success message
        setFlash('success', 'Account verified successfully. You can now log in.');
        redirect('/login');
    }
    
    public function showForgotPassword() {
        require_once BASE_PATH . '/views/auth/forgot-password.php';
    }
    
    public function forgotPassword() {
        // Check if the form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/forgot-password');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/forgot-password');
        }
        
        // Get form data
        $email = sanitize($_POST['email'] ?? '');
        
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            // Don't reveal if email exists for security reasons
            setFlash('success', 'If your email exists in our database, you will receive a password reset link.');
            redirect('/login');
        }
        
        // Generate token
        $token = generateRandomString();
        $this->userModel->updateToken($user['id'], $token);
        
        // Send password reset email
        $this->emailService->sendPasswordResetEmail($email, $user['username'], $token);
        
        // Set success message
        setFlash('success', 'Password reset link has been sent to your email.');
        redirect('/login');
    }
    
    public function showResetPassword() {
        // Get token from URL
        $token = sanitize($_GET['token'] ?? '');
        
        if (empty($token)) {
            setFlash('error', 'Invalid password reset token');
            redirect('/login');
        }
        
        // Find user by token
        $user = $this->userModel->findByToken($token);
        
        if (!$user) {
            setFlash('error', 'Invalid password reset token or token expired');
            redirect('/login');
        }
        
        require_once BASE_PATH . '/views/auth/reset-password.php';
    }
    
    public function resetPassword() {
        // Check if the form was submitted
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
        }
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid form submission');
            redirect('/login');
        }
        
        // Get form data
        $token = sanitize($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Find user by token
        $user = $this->userModel->findByToken($token);
        
        if (!$user) {
            setFlash('error', 'Invalid password reset token or token expired');
            redirect('/login');
        }
        
        // Validate password
        $errors = [];
        
        $passwordCheck = Security::validatePassword($password);
        if (!$passwordCheck['valid']) {
            $errors = array_merge($errors, $passwordCheck['errors']);
        }
        
        // Check if passwords match
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // If there are errors, redirect back with error message
        if (count($errors) > 0) {
            $_SESSION['form_errors'] = $errors;
            redirect('/reset-password?token=' . $token);
        }
        
        // Update password
        $this->userModel->updatePassword($user['id'], $password);
        
        // Set success message
        setFlash('success', 'Password reset successful. You can now log in with your new password.');
        redirect('/login');
    }
}