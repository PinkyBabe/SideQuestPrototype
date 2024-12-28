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
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    // Include required files
    require_once 'config.php';
    require_once 'functions.php';
    require_once 'auth_middleware.php';

    // Check if user is logged in and is a student
    checkUserRole(['student']);

    $student_id = $_SESSION['user_id'];
    $conn = Database::getInstance();

    // First get raw counts for debugging
    $debug_query = "
        SELECT status, COUNT(*) as count
        FROM user_quests
        WHERE student_id = ?
        GROUP BY status";
    
    $stmt = $conn->prepare($debug_query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $debug_result = $stmt->get_result();
    
    $debug_counts = [];
    while ($row = $debug_result->fetch_assoc()) {
        $debug_counts[$row['status']] = $row['count'];
    }
    
    error_log("Debug counts: " . print_r($debug_counts, true));

    // Get all stats in a single query
    $query = "SELECT 
        (
            SELECT COUNT(*) 
            FROM user_quests uq 
            JOIN quests q ON uq.quest_id = q.id 
            WHERE uq.student_id = ? 
            AND uq.status = 'accepted' 
            AND q.status = 'in_progress'
        ) as pending_quests,
        (
            SELECT COUNT(*) 
            FROM user_quests uq 
            WHERE uq.student_id = ? 
            AND uq.status = 'completed'
        ) as completed_quests,
        COALESCE((
            SELECT SUM(q.cash_reward) 
            FROM user_quests uq 
            JOIN quests q ON uq.quest_id = q.id 
            WHERE uq.student_id = ? 
            AND uq.status = 'completed'
        ), 0) as total_earnings";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->bind_param('iii', $student_id, $student_id, $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    $stats = $result->fetch_assoc();
    if (!$stats) {
        $stats = [
            'pending_quests' => 0,
            'completed_quests' => 0,
            'total_earnings' => 0
        ];
    }

    // Format total earnings to 2 decimal places
    $stats['total_earnings'] = number_format((float)$stats['total_earnings'], 2, '.', '');

    // Convert stats to integers
    $stats['pending_quests'] = (int)$stats['pending_quests'];
    $stats['completed_quests'] = (int)$stats['completed_quests'];

    error_log("Final stats: " . print_r($stats, true));

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'debug_info' => [
            'raw_counts' => $debug_counts,
            'student_id' => $student_id
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_student_stats.php: " . $e->getMessage());
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