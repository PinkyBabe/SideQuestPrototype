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
    
    // Test each part of the stats query separately
    
    // 1. Test pending quests query
    $pending_query = "
        SELECT uq.*, q.status as quest_status, q.description
        FROM user_quests uq 
        JOIN quests q ON uq.quest_id = q.id 
        WHERE uq.student_id = ? 
        AND uq.status = 'accepted' 
        AND q.status = 'in_progress'";
    
    $stmt = $conn->prepare($pending_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $pending_result = $stmt->get_result();
    
    $pending_quests = [];
    while ($row = $pending_result->fetch_assoc()) {
        $pending_quests[] = $row;
    }
    
    // 2. Test completed quests query
    $completed_query = "
        SELECT uq.*, q.description
        FROM user_quests uq 
        JOIN quests q ON uq.quest_id = q.id
        WHERE uq.student_id = ? 
        AND uq.status = 'completed'";
    
    $stmt = $conn->prepare($completed_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $completed_result = $stmt->get_result();
    
    $completed_quests = [];
    while ($row = $completed_result->fetch_assoc()) {
        $completed_quests[] = $row;
    }
    
    // 3. Test earnings query
    $earnings_query = "
        SELECT q.cash_reward, q.description
        FROM user_quests uq 
        JOIN quests q ON uq.quest_id = q.id 
        WHERE uq.student_id = ? 
        AND uq.status = 'completed'";
    
    $stmt = $conn->prepare($earnings_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $earnings_result = $stmt->get_result();
    
    $completed_with_earnings = [];
    while ($row = $earnings_result->fetch_assoc()) {
        $completed_with_earnings[] = $row;
    }
    
    // 4. Get raw counts
    $counts_query = "
        SELECT 
            status,
            COUNT(*) as count
        FROM user_quests
        WHERE student_id = ?
        GROUP BY status";
    
    $stmt = $conn->prepare($counts_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $counts_result = $stmt->get_result();
    
    $status_counts = [];
    while ($row = $counts_result->fetch_assoc()) {
        $status_counts[$row['status']] = $row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'debug_info' => [
            'pending_quests' => $pending_quests,
            'completed_quests' => $completed_quests,
            'completed_with_earnings' => $completed_with_earnings,
            'status_counts' => $status_counts
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 