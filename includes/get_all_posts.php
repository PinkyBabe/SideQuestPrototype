<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    checkUserRole(['admin']);
    
    $conn = Database::getInstance();
    
    $query = "
        SELECT 
            q.*,
            CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
            CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM quests q
        JOIN users f ON q.faculty_id = f.id
        LEFT JOIN user_quests uq ON q.id = uq.quest_id
        LEFT JOIN users s ON uq.student_id = s.id
        ORDER BY q.created_at DESC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed");
    }
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            'id' => $row['id'],
            'faculty_name' => $row['faculty_name'],
            'description' => $row['description'],
            'job_type' => $row['job_type'],
            'location' => $row['location'],
            'meeting_time' => $row['meeting_time'],
            'student_name' => $row['student_name'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 