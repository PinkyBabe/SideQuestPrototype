<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is admin
checkUserRole(['admin']);

$response = [
    'success' => false,
    'message' => ''
];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['faculty_id'])) {
        throw new Exception('Faculty ID is required');
    }

    $conn = Database::getInstance();
    
    // First check if faculty exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'faculty'");
    $check_stmt->bind_param('i', $data['faculty_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        throw new Exception('Faculty not found');
    }
    
    // Delete the faculty member
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'faculty'");
    $stmt->bind_param('i', $data['faculty_id']);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Faculty deleted successfully';
    } else {
        throw new Exception('Error deleting faculty: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 