<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['student']);

$response = ['success' => false, 'quests' => [], 'message' => ''];

try {
    $student_id = $_SESSION['user_id'];
    $conn = Database::getInstance();
    
    // Get available quests that:
    // 1. Are active
    // 2. Haven't been accepted by this student
    // 3. Haven't been completed
    $query = "
        SELECT 
            q.*,
            u.first_name,
            u.last_name,
            u.email as faculty_email,
            u.office_name,
            u.room_number
        FROM quests q
        JOIN users u ON q.faculty_id = u.id
        WHERE q.status = 'active'
        AND q.id NOT IN (
            SELECT quest_id 
            FROM user_quests 
            WHERE student_id = ?
        )
        ORDER BY q.created_at DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        $quests = [];
        while ($row = $result->fetch_assoc()) {
            $quests[] = [
                'id' => $row['id'],
                'faculty_name' => $row['first_name'] . ' ' . $row['last_name'],
                'faculty_email' => $row['faculty_email'],
                'office_name' => $row['office_name'],
                'room_number' => $row['room_number'],
                'description' => $row['description'],
                'job_type' => $row['job_type'],
                'location' => $row['location'],
                'meeting_time' => $row['meeting_time'],
                'estimated_hours' => $row['estimated_hours'],
                'cash_reward' => $row['cash_reward'],
                'snack_reward' => $row['snack_reward'],
                'created_at' => $row['created_at']
            ];
        }
        
        $response['success'] = true;
        $response['quests'] = $quests;
    } else {
        throw new Exception("Error fetching quests");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 