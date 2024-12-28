<?php
require_once 'db_connect.php';
require_once 'auth_check.php';

// Ensure user is logged in and is a faculty member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['quest_id'])) {
    echo json_encode(['success' => false, 'message' => 'Quest ID is required']);
    exit;
}

try {
    $faculty_id = $_SESSION['user_id'];
    $quest_id = $data['quest_id'];
    
    // Check if quest belongs to faculty and is active
    $check_query = "SELECT q.id 
                   FROM quests q 
                   JOIN quest_assignments qa ON q.id = qa.quest_id 
                   WHERE q.faculty_id = ? AND q.id = ? AND qa.status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $faculty_id, $quest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Quest not found or not active']);
        exit;
    }
    
    // Update quest status to pending (allowing student to try again)
    $update_query = "UPDATE quest_assignments 
                    SET status = 'pending', rejected_at = NOW() 
                    WHERE quest_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('i', $quest_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update quest status');
    }
    
    echo json_encode(['success' => true, 'message' => 'Work rejected successfully']);
} catch (Exception $e) {
    error_log("Error in reject_work.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to reject work']);
}

$conn->close(); 