<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth_middleware.php';

// Add rate limiting for API calls
rateLimit('api_' . $_SERVER['REMOTE_ADDR'], 60); // 60 calls per minute

try {
    // Check if user is admin
    checkUserRole(['admin']);

    // Get database connection
    $conn = Database::getInstance();

    // Initialize stats array
    $stats = [
        'faculty_count' => 0,
        'student_count' => 0,
        'active_posts' => 0,
        'completed_tasks' => 0
    ];

    // Get student count
    $student_query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
    $result = $conn->query($student_query);
    if ($result) {
        $stats['student_count'] = $result->fetch_assoc()['count'];
    }

    // Get faculty count
    $faculty_query = "SELECT COUNT(*) as count FROM users WHERE role = 'faculty'";
    $result = $conn->query($faculty_query);
    if ($result) {
        $stats['faculty_count'] = $result->fetch_assoc()['count'];
    }

    // Get active posts count
    $posts_query = "SELECT COUNT(*) as count FROM posts WHERE status = 'active'";
    $result = $conn->query($posts_query);
    if ($result) {
        $stats['active_posts'] = $result->fetch_assoc()['count'];
    }

    // Get completed tasks count
    $tasks_query = "SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'";
    $result = $conn->query($tasks_query);
    if ($result) {
        $stats['completed_tasks'] = $result->fetch_assoc()['count'];
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($stats); 
} catch (Exception $e) {
    error_log('API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing the request']);
} 