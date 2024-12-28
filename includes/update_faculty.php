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
    'message' => ''
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['facultyId', 'firstName', 'lastName', 'email'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception($field . ' is required');
        }
    }

    $conn = Database::getInstance();

    // Check if email exists for other users
    $stmt = $conn->prepare("
        SELECT id FROM users 
        WHERE email = ? AND id != ? AND role = 'faculty'
    ");
    $stmt->bind_param("si", $_POST['email'], $_POST['facultyId']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }

    // Update query parts
    $updateFields = [
        "first_name = ?",
        "last_name = ?",
        "email = ?",
        "room_number = ?",
        "office_name = ?"
    ];
    $params = [
        $_POST['firstName'],
        $_POST['lastName'],
        $_POST['email'],
        $_POST['roomNumber'],
        $_POST['officeName']
    ];
    $types = "sssss";

    // Add password update if provided
    if (!empty($_POST['password'])) {
        $updateFields[] = "password = ?";
        $updateFields[] = "actual_password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $params[] = $_POST['password'];
        $types .= "ss";
    }

    // Add faculty ID to params
    $params[] = $_POST['facultyId'];
    $types .= "i";

    // Prepare and execute update query
    $query = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE id = ? AND role = 'faculty'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Faculty updated successfully';
    } else {
        throw new Exception('Error updating faculty: ' . $stmt->error);
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in update_faculty: " . $e->getMessage());
}

// Ensure clean output
header('Content-Type: application/json');
echo json_encode($response);
exit; 