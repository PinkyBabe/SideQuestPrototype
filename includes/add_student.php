<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Ensure proper content type
header('Content-Type: application/json');

// Ensure no output before headers
ob_start();

try {
    // Check if user is admin
    checkUserRole(['admin']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'email', 'password', 'course'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception($field . ' is required');
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    $conn = Database::getInstance();

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $email = sanitize($_POST['email']);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Validate course exists
    $course_stmt = $conn->prepare("SELECT id, code, name FROM courses WHERE id = ?");
    if (!$course_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $course_id = (int)$_POST['course'];
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    
    if ($course_result->num_rows === 0) {
        throw new Exception('Invalid course selected');
    }
    
    $course_data = $course_result->fetch_assoc();

    // Hash password
    $password = sanitize($_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert new student
        $stmt = $conn->prepare("
            INSERT INTO users (
                first_name,
                last_name,
                email,
                password,
                actual_password,
                role,
                course_id,
                is_active
            ) VALUES (?, ?, ?, ?, ?, 'student', ?, 1)
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $firstName = sanitize($_POST['firstName']);
        $lastName = sanitize($_POST['lastName']);

        // Debug log
        error_log("Binding parameters: firstName=$firstName, lastName=$lastName, email=$email, password=*****, course_id=$course_id");

        $stmt->bind_param(
            "sssssi",  // 6 parameters: 5 strings + 1 integer
            $firstName,
            $lastName,
            $email,
            $hashed_password,
            $password,
            $course_id
        );

        if (!$stmt->execute()) {
            throw new Exception('Error adding student: ' . $stmt->error);
        }

        $student_id = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();

        // Clear any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => 'Student added successfully',
            'student' => [
                'id' => $student_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'course_id' => $course_id,
                'course_display' => $course_data['code'] . ' - ' . $course_data['name'],
                'is_active' => true
            ]
        ]);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    // Log the error with more details
    error_log("Error in add_student.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => [
            'type' => get_class($e),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
?> 