<?php
require_once 'config.php';

// Set JSON header for AJAX requests
if (isAjaxRequest()) {
    header('Content-Type: application/json');
}

// Only define these functions if they don't already exist
if (!function_exists('checkUserLogin')) {
    function checkUserLogin() {
        if (!isset($_SESSION['user_id'])) {
            if (isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                exit;
            }
            header("Location: index.php");
            exit();
        }
    }
}

if (!function_exists('checkUserRole')) {
    function checkUserRole($allowed_roles) {
        if (!isset($_SESSION['user_id'])) {
            if (isAjaxRequest()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login.php');
            exit();
        }

        try {
            $conn = Database::getInstance();
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("i", $_SESSION['user_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Query failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                session_destroy();
                if (isAjaxRequest()) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                    exit;
                }
                header('Location: ' . BASE_URL . '/login.php');
                exit();
            }
            
            $user = $result->fetch_assoc();
            
            if (!in_array($user['role'], $allowed_roles)) {
                if (isAjaxRequest()) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                    exit;
                }
                header('Location: ' . BASE_URL . '/login.php');
                exit();
            }
            
        } catch (Exception $e) {
            error_log("Auth middleware error: " . $e->getMessage());
            if (isAjaxRequest()) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login.php');
            exit();
        }
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Only check login for non-AJAX requests
if (!isAjaxRequest()) {
    checkUserLogin();
}
?> 