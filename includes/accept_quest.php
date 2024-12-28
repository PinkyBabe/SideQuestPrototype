<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

try {
    // Include required files
    require_once 'config.php';
    require_once 'functions.php';
    require_once 'auth_middleware.php';

    // Check if user is student
    checkUserRole(['student']);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['quest_id'])) {
        throw new Exception('Quest ID is required');
    }

    $student_id = $_SESSION['user_id'];
    $quest_id = $data['quest_id'];
    $conn = Database::getInstance();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if quest exists and is active
        $check_query = "SELECT id, status FROM quests WHERE id = ? AND status = 'active'";
        $stmt = $conn->prepare($check_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare check query: ' . $conn->error);
        }

        $stmt->bind_param('i', $quest_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute check query: ' . $stmt->error);
        }

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception('Quest is not available');
        }

        // Check if student has already accepted this quest
        $check_existing = "SELECT id FROM user_quests WHERE quest_id = ? AND student_id = ?";
        $stmt = $conn->prepare($check_existing);
        if (!$stmt) {
            throw new Exception('Failed to prepare existing check query: ' . $conn->error);
        }

        $stmt->bind_param('ii', $quest_id, $student_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute existing check query: ' . $stmt->error);
        }

        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('You have already accepted this quest');
        }

        // Insert into user_quests
        $insert_query = "INSERT INTO user_quests (quest_id, student_id, status, accepted_at) VALUES (?, ?, 'accepted', CURRENT_TIMESTAMP)";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare insert query: ' . $conn->error);
        }

        $stmt->bind_param('ii', $quest_id, $student_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute insert query: ' . $stmt->error);
        }

        // Update quest status to in_progress
        $update_query = "UPDATE quests SET status = 'in_progress' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        if (!$stmt) {
            throw new Exception('Failed to prepare update query: ' . $conn->error);
        }

        $stmt->bind_param('i', $quest_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute update query: ' . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Quest accepted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in accept_quest.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?> 