<?php
// Turn off error display
ini_set('display_errors', 0);
error_reporting(0);

require_once 'session.php';
require_once 'config.php';
require_once 'functions.php';

// Check if user is admin
checkUserRole(['admin']);

// Clear any previous output
if (ob_get_length()) ob_clean();

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Student ID is required');
    }

    $conn = Database::getInstance();
    
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.course_id,
            c.code as course_code,
            c.name as course_name
        FROM users u
        LEFT JOIN courses c ON u.course_id = c.id
        WHERE u.id = ? AND u.role = 'student'
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_GET['id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Student not found');
    }
    
    $student = $result->fetch_assoc();
    
    // Format course display
    $course_display = '';
    if ($student['course_code']) {
        $course_display = $student['course_code'];
        if ($student['course_name']) {
            $course_display .= ' - ' . $student['course_name'];
        }
    }
    
    $response['data'] = [
        'id' => $student['id'],
        'first_name' => $student['first_name'],
        'last_name' => $student['last_name'],
        'email' => $student['email'],
        'course_id' => $student['course_id'],
        'course_display' => $course_display
    ];
    
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_student: " . $e->getMessage());
}

// Ensure clean output
header('Content-Type: application/json');
echo json_encode($response);
exit; 