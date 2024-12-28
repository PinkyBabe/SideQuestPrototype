<?php
// Include functions first
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error handling - must be set before any output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Only set session parameters if session hasn't started
if (session_status() === PHP_SESSION_NONE) {
    // Strict session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.use_strict_mode', 1);

    // Custom session handler for better security
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Session security checks
function validateSession() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Check session timeout (30 minutes)
    if (time() - $_SESSION['last_activity'] > 1800) {
        return false;
    }
    
    // Validate IP and user agent
    if (!isset($_SESSION['ip']) || !isset($_SESSION['user_agent'])) {
        return false;
    }
    
    if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || 
        $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

// Validate session on each request
if (isset($_SESSION['user_id']) && !validateSession()) {
    session_destroy();
    if (isAjaxRequest()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }
    header('Location: ' . BASE_URL . '/login.php?session=expired');
    exit;
} 