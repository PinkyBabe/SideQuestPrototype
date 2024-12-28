<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Ensure proper content type
header('Content-Type: application/json');

// Ensure no output before headers
ob_start();

try {
    // Check if user is admin
    checkUserRole(['admin']);

    // Validate required fields
    $required = ['firstName', 'lastName', 'email', 'password', 'officeName'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("$field is required");
        }
    }

    // Validate password match
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        throw new Exception('Passwords do not match');
    }

    $conn = Database::getInstance();

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param("s", $_POST['email']);
    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Insert new faculty
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("
        INSERT INTO users (
            first_name, 
            last_name, 
            email, 
            password,
            actual_password, 
            role, 
            room_number,
            office_name,
            is_active
        ) VALUES (?, ?, ?, ?, ?, 'faculty', ?, ?, 1)
    ");

    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }

    $stmt->bind_param(
        "sssssss",
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $hashed_password,
        $_POST['password'],
        $_POST['roomNumber'],
        $_POST['officeName']
    );

    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }

    $faculty_id = $conn->insert_id;

    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Return success response with faculty data
    echo json_encode([
        'success' => true,
        'message' => 'Faculty added successfully',
        'faculty' => [
            'id' => $faculty_id,
            'first_name' => $_POST['firstName'],
            'last_name' => $_POST['lastName'],
            'email' => $_POST['email'],
            'room_number' => $_POST['roomNumber'],
            'office_name' => $_POST['officeName'],
            'is_active' => true
        ]
    ]);

} catch (Exception $e) {
    // Log the error with more details
    error_log("Error in add_faculty.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => [
            'type' => get_class($e),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
} 