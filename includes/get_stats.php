<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is admin
checkUserRole(['admin']);

// Initialize stats array
$stats = [
    'faculty_count' => 0,
    'student_count' => 0,
    'active_posts' => 0,
    'completed_tasks' => 0
];

// Get database connection
$conn = Database::getInstance();

// Get faculty count
$faculty_query = "SELECT COUNT(*) as count FROM users WHERE role = 'faculty'";
$result = $conn->query($faculty_query);
if ($result) {
    $stats['faculty_count'] = $result->fetch_assoc()['count'];
}

// Get student count
$student_query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
$result = $conn->query($student_query);
if ($result) {
    $stats['student_count'] = $result->fetch_assoc()['count'];
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

// Send JSON response
header('Content-Type: application/json');
echo json_encode($stats);
?> 