<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is admin
checkUserRole(['admin']);

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Student ID is required');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        SELECT first_name, last_name, email, actual_password 
        FROM users 
        WHERE id = ? AND role = 'student'
    ");
    
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Student not found');
    }
    
    $student = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'student_name' => $student['first_name'] . ' ' . $student['last_name'],
        'email' => $student['email'],
        'password' => $student['actual_password']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 