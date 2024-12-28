<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    checkUserRole(['admin']);
    $conn = Database::getInstance();

    // Get stats from existing dashboard variables
    $stats = [
        'faculty_count' => getFacultyCount(),
        'student_count' => getStudentCount(),
        'activeQuests' => getActivePostsCount(),
        'completedQuests' => getCompletedTasksCount(),
        'totalEarnings' => 0
    ];

    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 