<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Set JSON content type header
header('Content-Type: application/json');

try {
    // Debug logging
    error_log("Starting create_quest.php");
    error_log("Session data: " . print_r($_SESSION, true));

    // Ensure user is logged in and is faculty
    checkUserRole(['faculty']);
    $faculty_id = $_SESSION['user_id'];
    error_log("User ID: " . $faculty_id);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    error_log("Received data: " . print_r($data, true));

    // Validate required fields
    if (!isset($data['description']) || !isset($data['jobType']) || 
        !isset($data['location']) || !isset($data['meetingTime']) || 
        !isset($data['estimatedHours']) || !isset($data['rewards'])) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    $conn = Database::getInstance();
    $conn->begin_transaction();

    // Prepare query
    $query = "
        INSERT INTO quests (
            faculty_id,
            description,
            job_type,
            location,
            meeting_time,
            estimated_hours,
            cash_reward,
            snack_reward,
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ";
    error_log("Preparing query: " . $query);

    // Prepare statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    error_log("Binding parameters...");
    
    // Set parameters
    $cash_reward = isset($data['rewards']['cash']) ? $data['rewards']['cash'] : 0;
    $snack_reward = isset($data['snack_reward']) ? $data['snack_reward'] : 0;

    $stmt->bind_param('issssiis', 
        $faculty_id,
        $data['description'],
        $data['jobType'],
        $data['location'],
        $data['meetingTime'],
        $data['estimatedHours'],
        $cash_reward,
        $snack_reward
    );

    error_log("Executing statement...");
    if (!$stmt->execute()) {
        $conn->rollback();
        error_log("Transaction rolled back: Execute failed: " . $stmt->error);
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    // Get the inserted quest ID
    $quest_id = $stmt->insert_id;

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quest created successfully',
        'quest_id' => $quest_id
    ]);

} catch (Exception $e) {
    error_log("Error in create_quest.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 