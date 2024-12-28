<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

try {
    require_once 'config.php';
    $conn = Database::getInstance();
    
    // Get student ID (Melody's ID)
    $student_id = 6;  // Melody's ID from the session data
    
    // Check user_quests table
    $user_quests_query = "
        SELECT uq.*, q.description, q.status as quest_status
        FROM user_quests uq
        JOIN quests q ON uq.quest_id = q.id
        WHERE uq.student_id = ?";
        
    $stmt = $conn->prepare($user_quests_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user_quests = [];
    while ($row = $result->fetch_assoc()) {
        $user_quests[] = $row;
    }
    
    // Check quests table for any directly assigned quests
    $quests_query = "
        SELECT *
        FROM quests
        WHERE status IN ('active', 'in_progress')";
        
    $result = $conn->query($quests_query);
    $available_quests = [];
    while ($row = $result->fetch_assoc()) {
        $available_quests[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'user_quests' => $user_quests,
        'available_quests' => $available_quests,
        'student_id' => $student_id
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 