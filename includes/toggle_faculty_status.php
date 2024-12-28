<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

header('Content-Type: application/json');

// Check if user is admin
checkUserRole(['admin']);

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Get and validate input data
    $input = file_get_contents('php://input');
    error_log("Received input: " . $input);
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    if (!isset($data['faculty_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields: faculty_id and status');
    }

    $faculty_id = (int)$data['faculty_id'];
    $status = $data['status'] ? 1 : 0;

    $conn = Database::getInstance();
    
    // First check if faculty exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'faculty'");
    if (!$check_stmt) {
        throw new Exception("Prepare error: " . $conn->error);
    }
    
    $check_stmt->bind_param('i', $faculty_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Execute error: " . $check_stmt->error);
    }
    
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Faculty not found');
    }
    
    // Update faculty status
    $stmt = $conn->prepare("
        UPDATE users 
        SET is_active = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND role = 'faculty'
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare error: " . $conn->error);
    }
    
    $stmt->bind_param('ii', $status, $faculty_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute error: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('No changes made to faculty status');
    }
    
    $response['success'] = true;
    $response['message'] = 'Faculty status updated successfully';
    
} catch (Exception $e) {
    error_log("Error in toggle_faculty_status: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit; 