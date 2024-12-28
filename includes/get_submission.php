<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['faculty']);

$response = ['success' => false, 'submission' => null, 'message' => ''];

try {
    $submission_id = $_GET['id'] ?? null;
    $faculty_id = $_SESSION['user_id'];

    if (!$submission_id) {
        throw new Exception('Submission ID is required');
    }

    $conn = Database::getInstance();
    
    // Get submission with quest and student details
    $stmt = $conn->prepare("
        SELECT 
            qs.*,
            q.faculty_id,
            u.first_name as student_first_name,
            u.last_name as student_last_name,
            u.email as student_email
        FROM quest_submissions qs
        JOIN quests q ON qs.quest_id = q.id
        JOIN users u ON qs.student_id = u.id
        WHERE qs.id = ? AND q.faculty_id = ?
    ");
    
    $stmt->bind_param('ii', $submission_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Submission not found or unauthorized');
    }

    $submission = $result->fetch_assoc();
    
    // Format submission data
    $response['submission'] = [
        'id' => $submission['id'],
        'submission_text' => $submission['submission_text'],
        'file_path' => $submission['file_path'],
        'file_name' => $submission['file_name'],
        'status' => $submission['status'],
        'feedback' => $submission['feedback'],
        'submitted_at' => $submission['submitted_at'],
        'student_name' => $submission['student_first_name'] . ' ' . $submission['student_last_name'],
        'student_email' => $submission['student_email']
    ];
    
    $response['success'] = true;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 