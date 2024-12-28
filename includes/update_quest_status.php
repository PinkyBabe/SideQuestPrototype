<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Set JSON content type header
header('Content-Type: application/json');

try {
    // Ensure user is logged in and is faculty
    checkUserRole(['faculty']);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['quest_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    $quest_id = $data['quest_id'];
    $status = $data['status'];
    $faculty_id = $_SESSION['user_id'];

    // Validate status
    $valid_statuses = ['pending', 'active', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    $conn = Database::getInstance();

    // Verify that the faculty owns this quest
    $verify_query = "SELECT q.id 
                    FROM quests q 
                    WHERE q.id = ? AND q.faculty_id = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param('ii', $quest_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Quest not found or unauthorized');
    }

    // Update the quest status
    $update_query = "UPDATE user_quests 
                    SET status = ?, 
                        updated_at = NOW() 
                    WHERE quest_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $status, $quest_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update quest status');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Quest status updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in update_quest_status.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 