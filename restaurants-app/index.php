<?php

/**
 * Front Controller - Index.php
 * Routes all requests and loads appropriate controllers
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load configurations
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/rate_limiter.php';

// Initialize database
$db = Database::getConnection();

// Initialize auth
Auth::init($db);

// Initialize rate limiter
RateLimiter::init($db);

// Set default timezone
date_default_timezone_set('Africa/Tunis');

// Parse request
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$basePath = '/restaurants-app/';
$path = str_replace($basePath, '', $requestUri);
$path = trim($path, '/');

// API Routes
if (strpos($path, 'api/') === 0) {
    $apiPath = str_replace('api/', '', $path);
    $segments = explode('/', $apiPath);
    
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;
    $action = $segments[2] ?? '';
    
    require_once __DIR__ . '/api/' . $resource . '.php';
    exit;
}

// Public Routes
switch ($path) {
    case '':
    case '/':
        require_once __DIR__ . '/public/home.php';
        break;
    case 'login':
        require_once __DIR__ . '/public/login.php';
        break;
    case 'register':
        require_once __DIR__ . '/public/register.php';
        break;
    case 'logout':
        Auth::logout();
        redirect('/restaurants-app/');
        break;
    case 'restaurants':
        require_once __DIR__ . '/public/restaurants.php';
        break;
    case 'restaurant':
        require_once __DIR__ . '/public/restaurant.php';
        break;
    case 'about':
        require_once __DIR__ . '/public/about.php';
        break;
    default:
        http_response_code(404);
        require_once __DIR__ . '/public/404.php';
        break;
}