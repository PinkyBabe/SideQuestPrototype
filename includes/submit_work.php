<?php
require_once 'db_connect.php';
require_once 'auth_check.php';

// Ensure user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
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
    $student_id = $_SESSION['user_id'];
    $quest_id = $data['quest_id'];
    
    // Check if quest exists and student is assigned to it
    $check_query = "SELECT qa.id, qa.status 
                   FROM quest_assignments qa 
                   WHERE qa.student_id = ? AND qa.quest_id = ? AND qa.status = 'active'";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $student_id, $quest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Quest not found or not active']);
        exit;
    }
    
    // Update quest status to completed
    $update_query = "UPDATE quest_assignments 
                    SET status = 'completed', completed_at = NOW() 
                    WHERE student_id = ? AND quest_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $student_id, $quest_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update quest status');
    }
    
    // Get quest rewards
    $rewards_query = "SELECT cash_reward, snack_reward 
                     FROM quests 
                     WHERE id = ?";
    $stmt = $conn->prepare($rewards_query);
    $stmt->bind_param('i', $quest_id);
    $stmt->execute();
    $rewards = $stmt->get_result()->fetch_assoc();
    
    // Update student rewards
    $update_rewards = "UPDATE students 
                      SET total_cash = total_cash + ?, total_snacks = total_snacks + ? 
                      WHERE user_id = ?";
    $stmt = $conn->prepare($update_rewards);
    $snack_reward = $rewards['snack_reward'] ? 1 : 0;
    $stmt->bind_param('dii', $rewards['cash_reward'], $snack_reward, $student_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update rewards');
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Work submitted successfully',
        'rewards' => [
            'cash' => $rewards['cash_reward'],
            'snack' => $rewards['snack_reward'] == 1
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in submit_work.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to submit work']);
}

$conn->close(); 