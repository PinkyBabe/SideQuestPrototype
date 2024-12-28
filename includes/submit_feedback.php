<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['faculty']);

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $submission_id = $data['submission_id'] ?? null;
    $feedback = $data['feedback'] ?? '';
    $status = $data['status'] ?? 'pending';
    $faculty_id = $_SESSION['user_id'];

    if (!$submission_id) {
        throw new Exception('Submission ID is required');
    }

    if (!in_array($status, ['approved', 'rejected'])) {
        throw new Exception('Invalid status');
    }

    $conn = Database::getInstance();
    
    // Verify faculty owns this submission
    $stmt = $conn->prepare("
        SELECT qs.*, q.faculty_id 
        FROM quest_submissions qs
        JOIN quests q ON qs.quest_id = q.id
        WHERE qs.id = ? AND q.faculty_id = ?
    ");
    $stmt->bind_param('ii', $submission_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Submission not found or unauthorized');
    }

    // Update submission with feedback
    $stmt = $conn->prepare("
        UPDATE quest_submissions 
        SET feedback = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param('ssi', $feedback, $status, $submission_id);
    
    if ($stmt->execute()) {
        // Update quest status based on feedback
        $submission = $result->fetch_assoc();
        $quest_status = $status === 'approved' ? 'completed' : 'active';
        
        $update = $conn->prepare("
            UPDATE quests 
            SET status = ? 
            WHERE id = ?
        ");
        $update->bind_param('si', $quest_status, $submission['quest_id']);
        $update->execute();

        // If rejected, reopen the user_quest
        if ($status === 'rejected') {
            $reopen = $conn->prepare("
                UPDATE user_quests 
                SET status = 'in_progress', completed_at = NULL
                WHERE quest_id = ? AND student_id = ?
            ");
            $reopen->bind_param('ii', $submission['quest_id'], $submission['student_id']);
            $reopen->execute();
        }

        $response['success'] = true;
        $response['message'] = 'Feedback submitted successfully';
    } else {
        throw new Exception('Failed to submit feedback');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 