<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

try {
    // Check if user is admin
    checkUserRole(['admin']);

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['student_id'])) {
        throw new Exception('Student ID is required');
    }

    $conn = Database::getInstance();
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete related records first
        $stmt = $conn->prepare("DELETE FROM user_quests WHERE student_id = ?");
        $stmt->bind_param('i', $data['student_id']);
        $stmt->execute();

        // Delete student
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param('i', $data['student_id']);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete student');
        }

        if ($stmt->affected_rows === 0) {
            throw new Exception('Student not found');
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 