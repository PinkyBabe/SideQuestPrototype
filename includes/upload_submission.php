<?php
require_once 'config.php';
require_once 'auth_middleware.php';

checkUserRole(['student']);

$response = ['success' => false, 'message' => ''];

try {
    $quest_id = $_POST['quest_id'] ?? null;
    $submission_text = $_POST['submission_text'] ?? '';
    $student_id = $_SESSION['user_id'];

    if (!$quest_id) {
        throw new Exception('Quest ID is required');
    }

    $conn = Database::getInstance();
    
    // Get faculty_id from quest
    $stmt = $conn->prepare("SELECT faculty_id FROM quests WHERE id = ?");
    $stmt->bind_param('i', $quest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quest = $result->fetch_assoc();
    
    if (!$quest) {
        throw new Exception('Quest not found');
    }

    $faculty_id = $quest['faculty_id'];
    $file_path = null;
    $file_name = null;
    $file_type = null;

    // Handle file upload if present
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/submissions/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['submission_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];

        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_extensions));
        }

        $file_name = $file['name'];
        $file_type = $file['type'];
        $unique_filename = uniqid() . '_' . $file_name;
        $file_path = $upload_dir . $unique_filename;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload file');
        }

        // Store relative path
        $file_path = 'uploads/submissions/' . $unique_filename;
    }

    // Insert submission
    $stmt = $conn->prepare("
        INSERT INTO quest_submissions 
        (quest_id, student_id, faculty_id, submission_text, file_path, file_name, file_type) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param('iiissss', 
        $quest_id, 
        $student_id, 
        $faculty_id, 
        $submission_text,
        $file_path,
        $file_name,
        $file_type
    );

    if ($stmt->execute()) {
        // Update quest status
        $update = $conn->prepare("
            UPDATE user_quests 
            SET status = 'completed', completed_at = NOW() 
            WHERE quest_id = ? AND student_id = ?
        ");
        $update->bind_param('ii', $quest_id, $student_id);
        $update->execute();

        $response['success'] = true;
        $response['message'] = 'Submission uploaded successfully';
    } else {
        throw new Exception('Failed to save submission');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 