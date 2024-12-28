<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    checkUserRole(['admin']);

    $conn = Database::getInstance();
    
    $query = "
        SELECT 
            id,
            first_name,
            last_name,
            email,
            office_name,
            room_number,
            is_active
        FROM users 
        WHERE role = 'faculty'
        ORDER BY last_name, first_name
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $faculty = [];
    while ($row = $result->fetch_assoc()) {
        $faculty[] = [
            'id' => $row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'office_name' => $row['office_name'],
            'room_number' => $row['room_number'],
            'is_active' => (bool)$row['is_active']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $faculty
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_faculty_list.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 