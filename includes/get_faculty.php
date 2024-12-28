<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is admin
checkUserRole(['admin']);

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Faculty ID is required');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, room_number, office_name 
        FROM users 
        WHERE id = ? AND role = 'faculty'
    ");
    
    $stmt->bind_param('i', $_GET['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($faculty = $result->fetch_assoc()) {
        $response['success'] = true;
        $response['data'] = $faculty;
    } else {
        throw new Exception('Faculty not found');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 