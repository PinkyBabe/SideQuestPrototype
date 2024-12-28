<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Check session variables
$session_info = [
    'session_status' => session_status(),
    'session_id' => session_id(),
    'session_variables' => $_SESSION,
];

// Check required files
$required_files = [
    'config.php',
    'functions.php',
    'auth_middleware.php'
];

$files_status = [];
foreach ($required_files as $file) {
    $files_status[$file] = [
        'exists' => file_exists(__DIR__ . '/' . $file),
        'path' => __DIR__ . '/' . $file
    ];
}

// Return all information
echo json_encode([
    'session_info' => $session_info,
    'files_status' => $files_status,
    'server_info' => [
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'script_filename' => $_SERVER['SCRIPT_FILENAME'],
        'current_dir' => __DIR__
    ]
], JSON_PRETTY_PRINT);
?> 