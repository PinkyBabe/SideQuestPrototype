<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Validate required fields
    $required = ['firstName', 'lastName', 'email', 'password', 'confirmPassword', 'course'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("$field is required");
        }
    }

    // Validate password match
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        throw new Exception('Passwords do not match');
    }

    $conn = Database::getInstance();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $_POST['email']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Insert new student
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
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

    $stmt->bind_param(
        "sssssi",
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $hashed_password,
        $_POST['password'],
        $_POST['course']
    );

    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        throw new Exception('Error creating account: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully'
    ]);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 