<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    checkUserRole(['admin']);
    $conn = Database::getInstance();

    $quests = [];
    
    $result = $conn->query("
        SELECT q.*, 
               f.first_name as faculty_first_name, 
               f.last_name as faculty_last_name,
               s.first_name as student_first_name, 
               s.last_name as student_last_name
        FROM quests q
        LEFT JOIN users f ON q.faculty_id = f.id
        LEFT JOIN users s ON q.student_id = s.id
        ORDER BY q.created_at DESC
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $quests[] = [
                'id' => $row['id'],
                'faculty_name' => $row['faculty_first_name'] . ' ' . $row['faculty_last_name'],
                'student_name' => $row['student_first_name'] ? $row['student_first_name'] . ' ' . $row['student_last_name'] : null,
                'description' => $row['description'],
                'status' => $row['status'],
                'created_at' => $row['created_at']
            ];
        }
    }

    echo json_encode(['success' => true, 'quests' => $quests]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 