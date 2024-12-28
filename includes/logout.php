<?php
require_once 'config.php';
require_once 'helpers.php';

ob_start();

try {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    ob_end_clean();
    sendJsonResponse([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);

} catch (Exception $e) {
    ob_end_clean();
    error_log("Logout error: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error during logout'
    ], 500);
}