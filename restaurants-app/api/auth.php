<?php

/**
 * Auth API Endpoints
 */

$identifier = ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ':' . ($_POST['email'] ?? '');

if (!RateLimiter::check($identifier)) {
    jsonResponse(false, null, 'Too many attempts. Please wait.', 429);
}

switch ($requestMethod) {
    case 'POST':
        if ($action === 'login') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                RateLimiter::hit($identifier);
                jsonResponse(false, null, t('required'));
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                RateLimiter::hit($identifier);
                jsonResponse(false, null, t('invalid_email'));
            }
            
            if (Auth::login($email, $password)) {
                jsonResponse(true, [
                    'user' => Auth::user(),
                    'redirect' => '/restaurants-app/'
                ]);
            }
            
            RateLimiter::hit($identifier);
            jsonResponse(false, null, 'Invalid credentials');
        }
        
        if ($action === 'register') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($email) || empty($password)) {
                jsonResponse(false, null, t('required'));
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, null, t('invalid_email'));
            }
            
            if (strlen($password) < 6) {
                jsonResponse(false, null, t('password_short'));
            }
            
            if ($password !== $confirmPassword) {
                jsonResponse(false, null, t('passwords_mismatch'));
            }
            
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                jsonResponse(false, null, 'Email already registered');
            }
            
            if (Auth::register($email, $password)) {
                jsonResponse(true, null, 'Registration successful');
            }
            
            jsonResponse(false, null, t('error'));
        }
        break;
        
    case 'GET':
        if ($action === 'csrf') {
            jsonResponse(true, ['token' => csrfToken()]);
        }
        
        if ($action === 'check') {
            jsonResponse(true, [
                'authenticated' => Auth::check(),
                'user' => Auth::user()
            ]);
        }
        break;
        
    case 'DELETE':
        if ($action === 'logout') {
            Auth::logout();
            jsonResponse(true, null, 'Logged out');
        }
        break;
}

jsonResponse(false, null, 'Not found', 404);