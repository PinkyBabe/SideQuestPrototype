<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is faculty
checkUserRole(['faculty']);

try {
    $faculty_id = $_SESSION['user_id'];
    $conn = Database::getInstance();
    
    // Get total quests
    $total_query = "SELECT COUNT(*) as total FROM quests WHERE faculty_id = ?";
    $stmt = $conn->prepare($total_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $total_result = $stmt->get_result()->fetch_assoc();
    
    // Get active quests (not yet accepted by any student)
    $active_query = "SELECT COUNT(*) as active FROM quests q 
                    LEFT JOIN user_quests uq ON q.id = uq.quest_id 
                    WHERE q.faculty_id = ? AND uq.id IS NULL";
    $stmt = $conn->prepare($active_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $active_result = $stmt->get_result()->fetch_assoc();
    
    // Get pending quests (accepted by students but not completed)
    $pending_query = "SELECT COUNT(*) as pending FROM quests q 
                     JOIN user_quests uq ON q.id = uq.quest_id 
                     WHERE q.faculty_id = ? AND uq.status = 'pending'";
    $stmt = $conn->prepare($pending_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $pending_result = $stmt->get_result()->fetch_assoc();
    
    // Get completed quests
    $completed_query = "SELECT COUNT(*) as completed FROM quests q 
                       JOIN user_quests uq ON q.id = uq.quest_id 
                       WHERE q.faculty_id = ? AND uq.status = 'completed'";
    $stmt = $conn->prepare($completed_query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $completed_result = $stmt->get_result()->fetch_assoc();
    
    $stats = [
        'total_quests' => (int)$total_result['total'],
        'active_quests' => (int)$active_result['active'],
        'pending_quests' => (int)$pending_result['pending'],
        'completed_quests' => (int)$completed_result['completed']
    ];
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
} catch (Exception $e) {
    error_log("Error getting faculty stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch faculty statistics'
    ]);
} 