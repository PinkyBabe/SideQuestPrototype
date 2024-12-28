<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    checkUserRole(['admin']);
    $conn = Database::getInstance();

    $result = $conn->query("
        SELECT 
            id,
            first_name,
            last_name,
            email,
            role,
            is_active,
            office_name,
            room_number,
            course_id
        FROM users 
        WHERE role IN ('faculty', 'student')
        ORDER BY role, last_name, first_name
    ");

    if (!$result) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    $faculty = [];
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] === 'faculty') {
            $faculty[] = $row;
        } else {
            $students[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'faculty' => $faculty,
            'students' => $students
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_users.php: " . $e->getMessage());
    
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