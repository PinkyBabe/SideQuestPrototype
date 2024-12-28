<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth_middleware.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    switch($action) {
        case 'get_stats':
            if ($role === 'faculty') {
                echo json_encode([
                    'success' => true,
                    'stats' => getFacultyWorkspaceStats($user_id)
                ]);
            } else if ($role === 'student') {
                echo json_encode([
                    'success' => true,
                    'stats' => getStudentWorkspaceStats($user_id)
                ]);
            }
            break;
            
        case 'get_quests':
            $status = $_GET['status'] ?? 'active';
            if ($role === 'faculty') {
                echo json_encode([
                    'success' => true,
                    'quests' => getFacultyQuests($user_id, $status)
                ]);
            } else if ($role === 'student') {
                echo json_encode([
                    'success' => true,
                    'quests' => getStudentQuests($user_id, $status)
                ]);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 