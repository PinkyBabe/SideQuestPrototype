<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    $conn = Database::getInstance();
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    // Different queries for faculty and students
    if ($role === 'faculty') {
        // Faculty can only see their own posts
        $query = "
            SELECT 
                q.*,
                CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                u.office_name,
                u.room_number,
                (SELECT COUNT(*) FROM user_quests WHERE quest_id = q.id) as application_count
            FROM quests q
            JOIN users u ON q.faculty_id = u.id
            WHERE q.faculty_id = ?
            ORDER BY q.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    } else {
        // Students can see all active posts
        $query = "
            SELECT 
                q.*,
                CONCAT(u.first_name, ' ', u.last_name) as faculty_name,
                u.office_name,
                u.room_number,
                CASE 
                    WHEN uq.student_id IS NOT NULL THEN 1
                    ELSE 0
                END as has_applied
            FROM quests q
            JOIN users u ON q.faculty_id = u.id
            LEFT JOIN user_quests uq ON q.id = uq.quest_id AND uq.student_id = ?
            WHERE q.status = 'active'
            ORDER BY q.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
    }
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Query failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $posts = [];
    
    while ($row = $result->fetch_assoc()) {
        $post = [
            'id' => $row['id'],
            'description' => $row['description'],
            'jobType' => $row['job_type'],
            'location' => $row['location'],
            'meetingTime' => $row['meeting_time'],
            'estimatedHours' => $row['estimated_hours'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'faculty_name' => $row['faculty_name'],
            'department' => $row['office_name'],
            'room_number' => $row['room_number'] ?? '',
            'rewards' => [
                'cash' => $row['cash_reward'],
                'snack' => $row['snack_reward'] == 1
            ]
        ];

        // Add role-specific data
        if ($role === 'faculty') {
            $post['application_count'] = $row['application_count'];
        } else {
            $post['has_applied'] = $row['has_applied'] == 1;
        }

        $posts[] = $post;
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_faculty_posts.php: " . $e->getMessage());
    
    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 