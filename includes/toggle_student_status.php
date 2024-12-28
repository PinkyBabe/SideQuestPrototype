<?php
// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

require_once 'session.php';
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
checkUserRole(['admin']);

// Clear any previous output
if (ob_get_length()) ob_clean();

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['student_id']) || !isset($input['status'])) {
        throw new Exception('Missing required parameters');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET is_active = ? 
        WHERE id = ? AND role = 'student'
    ");
    
    $status = $input['status'] ? 1 : 0;
    $stmt->bind_param("ii", $status, $input['student_id']);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Status updated successfully';
        } else {
            throw new Exception('Student not found');
        }
    } else {
        throw new Exception('Error updating status: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in toggle_student_status: " . $e->getMessage());
}

// Ensure clean output
header('Content-Type: application/json');
echo json_encode($response);
exit; 