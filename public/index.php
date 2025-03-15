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

// Create controller instance
$controller = new $controllerName();

// Call method
$controller->$methodName();