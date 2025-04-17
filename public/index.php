<?php

// Include config and setup
require_once __DIR__ . '/../config/setup.php';

// Start session
Security::startSession();

// Router function
function route($uri, $controller, $method) {
    return [
        'uri' => $uri,
        'controller' => $controller,
        'method' => $method
    ];
}

// Define routes
$routes = [
    // Auth routes
    route('/register', 'AuthController', 'showRegister'),
    route('/register/submit', 'AuthController', 'register'),
    route('/login', 'AuthController', 'showLogin'),
    route('/login/submit', 'AuthController', 'login'),
    route('/logout', 'AuthController', 'logout'),
    route('/verify', 'AuthController', 'verify'),
    route('/forgot-password', 'AuthController', 'showForgotPassword'),
    route('/forgot-password/submit', 'AuthController', 'forgotPassword'),
    route('/reset-password', 'AuthController', 'showResetPassword'),
    route('/reset-password/submit', 'AuthController', 'resetPassword'),
    
    // Gallery routes
    route('/', 'GalleryController', 'index'),
    route('/gallery', 'GalleryController', 'index'),
    route('/gallery/like', 'GalleryController', 'like'),
    route('/gallery/comment', 'GalleryController', 'comment'),
    route('/gallery/comment/update', 'GalleryController', 'updateComment'),
    route('/gallery/comment/delete', 'GalleryController', 'deleteComment'),
    
    // Editor routes
    route('/editor', 'ImageController', 'showEditor'),
    route('/editor/capture', 'ImageController', 'captureImage'),
    route('/editor/upload', 'ImageController', 'uploadImage'),
    route('/editor/delete', 'ImageController', 'deleteImage'),
    
    // User routes
    route('/profile', 'UserController', 'showProfile'),
    route('/profile/update', 'UserController', 'updateProfile'),
    route('/profile/update-password', 'UserController', 'updatePassword')
];

// Get current URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path
$basePath = parse_url(APP_URL, PHP_URL_PATH) ?: '';
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

// Match route
$matchedRoute = null;
foreach ($routes as $route) {
    if ($route['uri'] === $uri) {
        $matchedRoute = $route;
        break;
    }
}

// If no route matched, show 404
if (!$matchedRoute) {
    http_response_code(404);
    require_once BASE_PATH . '/views/errors/404.php';
    exit;
}

// Get controller and method
$controllerName = $matchedRoute['controller'];
$methodName = $matchedRoute['method'];

// For AJAX endpoints, set the proper content type
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    if (in_array($uri, ['/editor/capture', '/editor/upload', '/gallery/like', '/gallery/comment', '/gallery/comment/update', '/gallery/comment/delete'])) {
        header('Content-Type: application/json');
    }
}

// Create controller instance
$controller = new $controllerName();

// For AJAX methods, suppress PHP warnings and notices
if (in_array($uri, ['/editor/capture', '/editor/upload', '/gallery/like', '/gallery/comment', '/gallery/comment/update', '/gallery/comment/delete'])) {
    // Save current error reporting level
    $originalErrorReporting = error_reporting();
    // Turn off warnings and notices
    error_reporting(E_ERROR | E_PARSE);
    
    // Start output buffering to catch any warnings/notices
    ob_start();
    
    try {
        // Call method
        $controller->$methodName();
    } catch (Exception $e) {
        // Clean the output buffer
        ob_end_clean();
        
        // Return JSON error response
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    
    // Restore original error reporting
    error_reporting($originalErrorReporting);
} else {
    // Call method normally for non-AJAX routes
    $controller->$methodName();
}