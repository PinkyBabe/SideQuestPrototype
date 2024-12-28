<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Check if user is admin
checkUserRole(['admin']);

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

try {
    // Debug: Log the incoming POST data
    error_log("POST data: " . print_r($_POST, true));

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['id', 'firstName', 'lastName', 'email', 'course'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("$field is required");
        }
    }

    $conn = Database::getInstance();

    // Debug: Log the database connection status
    error_log("Database connection established: " . ($conn ? "yes" : "no"));

    // Check if email exists for other users
    $stmt = $conn->prepare("
        SELECT id FROM users 
        WHERE email = ? AND id != ? AND role = 'student'
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("si", $_POST['email'], $_POST['id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists for another student');
    }

    // Prepare update query
    $update_query = "
        UPDATE users 
        SET first_name = ?,
            last_name = ?,
            email = ?,
            course_id = ?
    ";

    // Add password update if provided
    $params = [
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['course']
    ];
    $types = "sssi";

    if (!empty($_POST['password'])) {
        $update_query .= ", password = ?, actual_password = ?";
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $params[] = $hashed_password;
        $params[] = $_POST['password'];
        $types .= "ss";
    }

    $update_query .= " WHERE id = ? AND role = 'student'";
    $params[] = $_POST['id'];
    $types .= "i";

    // Debug: Log the query and parameters
    error_log("Update query: " . $update_query);
    error_log("Parameters: " . print_r($params, true));

    $stmt = $conn->prepare($update_query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No changes were made to student ID " . $_POST['id']);
    }

    $response['success'] = true;
    $response['message'] = 'Student updated successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in update_student.php: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response);
exit; 