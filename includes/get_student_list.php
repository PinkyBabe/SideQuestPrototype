<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    error_log("Starting get_student_list.php");
    error_log("Session data: " . print_r($_SESSION, true));

    // Verify admin access
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access. Role: ' . ($_SESSION['role'] ?? 'none'));
    }

    $conn = Database::getInstance();
    error_log("Database connection successful");

    // First verify the tables exist
    $tables_check = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tables_check->num_rows === 0) {
        throw new Exception("Users table does not exist");
    }

    $tables_check = $conn->query("SHOW TABLES LIKE 'courses'");
    if ($tables_check->num_rows === 0) {
        throw new Exception("Courses table does not exist");
    }

    // Simple query first to verify basic functionality
    $query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Count query failed: " . $conn->error);
    }
    $count = $result->fetch_assoc()['count'];
    error_log("Found {$count} students in total");

    // Now get the full student list with only existing columns
    $query = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.is_active,
            u.course_id,
            COALESCE(c.code, 'N/A') as course_code,
            COALESCE(c.name, 'Not Set') as course_name
        FROM users u
        LEFT JOIN courses c ON u.course_id = c.id
        WHERE u.role = 'student'
        ORDER BY u.last_name, u.first_name
    ";

    error_log("Executing main query: " . $query);
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Main query failed: " . $conn->error);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        error_log("Processing student row: " . print_r($row, true));
        $students[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'is_active' => (bool)$row['is_active'],
            'course_id' => $row['course_id'],
            'course_display' => $row['course_code'] !== 'N/A' ? 
                "{$row['course_code']} - {$row['course_name']}" : 
                'Not set'
        ];
    }

    error_log("Successfully processed " . count($students) . " students");

    $response = [
        'success' => true,
        'data' => $students,
        'count' => count($students)
    ];

    error_log("Sending response: " . json_encode($response));
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log("Error in get_student_list.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'session' => $_SESSION,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    
    http_response_code(500);
    error_log("Sending error response: " . json_encode($error_response));
    echo json_encode($error_response);
    exit;
}
?> 