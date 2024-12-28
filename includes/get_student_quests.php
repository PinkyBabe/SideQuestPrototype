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

    // Check if user is logged in and is a student
    checkUserRole(['student']);

    // Get student ID and status
    $student_id = $_SESSION['user_id'];
    $status = $_GET['status'] ?? 'pending';

    // Get database connection
    $conn = Database::getInstance();

    // Build the WHERE clause based on the requested status
    $status_condition = "";
    if ($status === 'pending') {
        $status_condition = "uq.status = 'accepted' AND q.status = 'in_progress'";
    } else if ($status === 'completed') {
        $status_condition = "uq.status = 'completed'";
    }

    // Get quests with faculty information using user_quests table
    $query = "
        SELECT 
            q.*,
            u.first_name as faculty_name,
            u.email as faculty_email,
            u.office_name as department,
            u.room_number,
            uq.accepted_at,
            uq.completed_at,
            uq.status as quest_status
        FROM user_quests uq
        JOIN quests q ON uq.quest_id = q.id
        JOIN users u ON q.faculty_id = u.id
        WHERE uq.student_id = ? 
        AND $status_condition
        ORDER BY uq.accepted_at DESC";

    error_log("Query: " . $query);
    error_log("Student ID: " . $student_id);
    error_log("Status condition: " . $status_condition);

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    $stmt->bind_param('i', $student_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $quests = [];

    while ($row = $result->fetch_assoc()) {
        // Format quest data
        $quest = [
            'id' => $row['id'],
            'description' => $row['description'],
            'status' => $status,
            'jobType' => $row['job_type'],
            'location' => $row['location'],
            'meetingTime' => $row['meeting_time'],
            'estimatedHours' => $row['estimated_hours'],
            'faculty_name' => $row['faculty_name'],
            'faculty_email' => $row['faculty_email'],
            'department' => $row['department'],
            'room_number' => $row['room_number'],
            'accepted_at' => $row['accepted_at'],
            'completed_at' => $row['completed_at'],
            'rewards' => [
                'cash' => $row['cash_reward'],
                'snack' => $row['snack_reward'] == 1
            ]
        ];
        
        $quests[] = $quest;
    }

    error_log("Found " . count($quests) . " quests");

    echo json_encode([
        'success' => true,
        'quests' => $quests
    ]);

} catch (Exception $e) {
    error_log("Error in get_student_quests.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch quests: ' . $e->getMessage(),
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
} 