<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

// Set JSON content type header
header('Content-Type: application/json');

try {
    // Ensure user is logged in and is faculty
    checkUserRole(['faculty']);

    // Get status parameter
    $valid_statuses = ['active', 'pending', 'completed', 'cancelled'];
    $status = isset($_GET['status']) ? $_GET['status'] : 'active';

    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    $faculty_id = $_SESSION['user_id'];
    $conn = Database::getInstance();
    
    $query = "SELECT 
                q.*,
                uq.status as quest_status,
                u.first_name,
                u.last_name,
                u.course_id,
                c.name as course_name,
                c.code as course_code
              FROM quests q 
              LEFT JOIN user_quests uq ON q.id = uq.quest_id 
              LEFT JOIN users u ON uq.student_id = u.id 
              LEFT JOIN courses c ON u.course_id = c.id
              WHERE q.faculty_id = ? AND 
              CASE 
                WHEN ? = 'active' THEN uq.id IS NULL
                WHEN ? = 'pending' THEN uq.status = 'accepted'
                WHEN ? = 'completed' THEN uq.status = 'completed'
                ELSE uq.status = ?
              END
              ORDER BY q.created_at DESC";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param('issss', $faculty_id, $status, $status, $status, $status);
    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $quests = [];
    while ($row = $result->fetch_assoc()) {
        $quests[] = [
            'id' => $row['id'],
            'description' => $row['description'],
            'student_name' => $row['first_name'] . ' ' . $row['last_name'],
            'course' => $row['course_code'] . ' - ' . $row['course_name'],
            'jobType' => $row['job_type'],
            'location' => $row['location'],
            'estimatedHours' => $row['estimated_hours'],
            'status' => $row['quest_status'],
            'created_at' => $row['created_at'],
            'rewards' => [
                'cash' => $row['cash_reward'],
                'snack' => $row['snack_reward'] == 1
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'quests' => $quests
    ]);

} catch (Exception $e) {
    error_log("Error in get_faculty_quests.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch quests',
        'error' => $e->getMessage()
    ]);
} 