<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is faculty
    checkUserRole(['faculty']);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['quest_id'])) {
        throw new Exception('Quest ID is required');
    }

    $quest_id = (int)$data['quest_id'];
    $faculty_id = $_SESSION['user_id'];

    $conn = Database::getInstance();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Verify the quest belongs to this faculty
        $stmt = $conn->prepare("
            SELECT status 
            FROM quests 
            WHERE id = ? AND faculty_id = ? AND status = 'in_progress'
        ");
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("ii", $quest_id, $faculty_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Quest not found or not in progress');
        }

        // Update quest status to completed
        $stmt = $conn->prepare("
            UPDATE quests 
            SET status = 'completed' 
            WHERE id = ? AND faculty_id = ?
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("ii", $quest_id, $faculty_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update quest status');
        }

        // Update user_quests status
        $stmt = $conn->prepare("
            UPDATE user_quests 
            SET status = 'completed', 
                completed_at = CURRENT_TIMESTAMP 
            WHERE quest_id = ?
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param("i", $quest_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update user_quests status');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Quest marked as completed successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 