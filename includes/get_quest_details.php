<?php
require_once 'db_connect.php';
require_once 'auth_check.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get quest ID from query parameters
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Quest ID is required']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $quest_id = $_GET['id'];
    $role = $_SESSION['role'];
    
    // Build query based on user role
    if ($role === 'faculty') {
        $query = "SELECT q.*, qa.status as assignment_status, qa.created_at as assigned_at, 
                         qa.completed_at, qa.rejected_at, u.name as student_name,
                         u.email as student_email, u.department as student_department
                  FROM quests q 
                  LEFT JOIN quest_assignments qa ON q.id = qa.quest_id 
                  LEFT JOIN users u ON qa.student_id = u.id 
                  WHERE q.faculty_id = ? AND q.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $quest_id);
    } else {
        $query = "SELECT q.*, qa.status as assignment_status, qa.created_at as assigned_at, 
                         qa.completed_at, qa.rejected_at, u.name as faculty_name,
                         u.email as faculty_email, u.department as faculty_department
                  FROM quests q 
                  JOIN quest_assignments qa ON q.id = qa.quest_id 
                  JOIN users u ON q.faculty_id = u.id 
                  WHERE qa.student_id = ? AND q.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $user_id, $quest_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Quest not found']);
        exit;
    }
    
    $row = $result->fetch_assoc();
    
    // Format quest data
    $quest = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'jobType' => $row['job_type'],
        'estimatedHours' => $row['estimated_hours'],
        'location' => $row['location'],
        'status' => $row['assignment_status'],
        'created_at' => $row['created_at'],
        'assigned_at' => $row['assigned_at'],
        'completed_at' => $row['completed_at'],
        'rejected_at' => $row['rejected_at'],
        'rewards' => [
            'cash' => $row['cash_reward'],
            'snack' => $row['snack_reward'] == 1
        ]
    ];
    
    // Add role-specific information
    if ($role === 'faculty') {
        $quest['student'] = [
            'name' => $row['student_name'],
            'email' => $row['student_email'],
            'department' => $row['student_department']
        ];
    } else {
        $quest['faculty'] = [
            'name' => $row['faculty_name'],
            'email' => $row['faculty_email'],
            'department' => $row['faculty_department']
        ];
    }
    
    echo json_encode(['success' => true, 'quest' => $quest]);
} catch (Exception $e) {
    error_log("Error in get_quest_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch quest details']);
}

$conn->close();
?> 