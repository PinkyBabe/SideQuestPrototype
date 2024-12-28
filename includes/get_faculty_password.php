<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    checkUserRole(['admin']);
    
    if (!isset($_GET['id'])) {
        throw new Exception('Faculty ID is required');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        SELECT first_name, last_name, email, actual_password 
        FROM users 
        WHERE id = ? AND role = 'faculty'
    ");
    
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Faculty not found');
    }
    
    $faculty = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'faculty_name' => $faculty['first_name'] . ' ' . $faculty['last_name'],
        'email' => $faculty['email'],
        'password' => $faculty['actual_password']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 