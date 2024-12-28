<?php
// Turn off error display in production
ini_set('display_errors', 0);
error_reporting(0);

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRFToken() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Add this to all forms in JavaScript
function addCSRFTokenToForms() {
    $token = generateCSRFToken();
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (!form.querySelector('input[name=\"csrf_token\"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'csrf_token';
                    input.value = '{$token}';
                    form.appendChild(input);
                }
            });
            
            // Add CSRF token to AJAX requests
            const originalXHR = window.XMLHttpRequest;
            function newXHR() {
                const xhr = new originalXHR();
                const send = xhr.send;
                xhr.send = function() {
                    this.setRequestHeader('X-CSRF-TOKEN', '{$token}');
                    return send.apply(this, arguments);
                }
                return xhr;
            }
            window.XMLHttpRequest = newXHR;
        });
    </script>";
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        return date('M d, Y h:i A', strtotime($date));
    }
}

if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
               (!empty($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
}

if (!function_exists('handleError')) {
    function handleError($message, $error_code = 500) {
        error_log("Error handled: " . $message);
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($error_code);
            echo json_encode([
                'success' => false,
                'message' => ENVIRONMENT === 'production' ? 'An error occurred' : $message
            ]);
        }
        exit;
    }
}

if (!function_exists('checkRequiredTables')) {
    function checkRequiredTables() {
        try {
            $conn = Database::getInstance();
            $required_tables = ['users', 'quests', 'quest_rewards'];
            
            foreach ($required_tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    throw new Exception("Required table '$table' does not exist");
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Database table check failed: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('initializeDatabase')) {
    function initializeDatabase() {
        try {
            if (!checkRequiredTables()) {
                $sql_file = file_get_contents(__DIR__ . '/../sidequest_db.sql');
                if ($sql_file === false) {
                    throw new Exception("Could not read SQL file");
                }
                
                $conn = Database::getInstance();
                if ($conn->multi_query($sql_file)) {
                    do {
                        while ($conn->more_results() && $conn->next_result()) {;}
                    } while ($conn->more_results());
                }
                
                if ($conn->error) {
                    throw new Exception("Error initializing database: " . $conn->error);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Database initialization failed: " . $e->getMessage());
            return false;
        }
    }
}

// Stats functions for admin dashboard
if (!function_exists('getFacultyCount')) {
    function getFacultyCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM users WHERE role = 'faculty'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting faculty count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getStudentCount')) {
    function getStudentCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting student count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getActivePostsCount')) {
    function getActivePostsCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM quests WHERE status = 'active'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting active posts count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getCompletedTasksCount')) {
    function getCompletedTasksCount() {
        try {
            $conn = Database::getInstance();
            $query = "SELECT COUNT(*) as count FROM quests WHERE status = 'completed'";
            $result = $conn->query($query);
            if ($result) {
                return $result->fetch_assoc()['count'];
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting completed tasks count: " . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('getFacultyList')) {
    function getFacultyList() {
        try {
            $conn = Database::getInstance();
            $query = "
                SELECT 
                    id, 
                    first_name, 
                    last_name, 
                    email, 
                    office_name,
                    room_number,
                    is_active
                FROM users 
                WHERE role = 'faculty'
                ORDER BY last_name, first_name
            ";
            $result = $conn->query($query);
            
            $faculty = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $faculty[] = $row;
                }
            }
            return $faculty;
        } catch (Exception $e) {
            error_log("Error getting faculty list: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getStudentList')) {
    function getStudentList() {
        try {
            $conn = Database::getInstance();
            $query = "
                SELECT 
                    id, 
                    first_name, 
                    last_name, 
                    email, 
                    year_level,
                    is_active
                FROM users 
                WHERE role = 'student'
                ORDER BY last_name, first_name
            ";
            $result = $conn->query($query);
            
            $students = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $students[] = $row;
                }
            }
            return $students;
        } catch (Exception $e) {
            error_log("Error getting student list: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getDefaultAvatar')) {
    function getDefaultAvatar() {
        return 'https://tse2.mm.bing.net/th?id=OIP.yYUwl3GDU07Q5J5ttyW9fQHaHa&pid=Api&P=0&h=220';
    }
}

function getFacultyWorkspaceStats($faculty_id) {
    $conn = Database::getInstance();
    
    $stats = [
        'active_quests' => 0,
        'pending_quests' => 0,
        'completed_quests' => 0
    ];
    
    $query = "SELECT 
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_quests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_quests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_quests
        FROM quests 
        WHERE faculty_id = ?";
        
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $stats = array_merge($stats, $result);
    }
    
    return $stats;
}

function getStudentWorkspaceStats($student_id) {
    $conn = Database::getInstance();
    
    $stats = [
        'active_quests' => 0,
        'completed_quests' => 0,
        'total_earnings' => 0
    ];
    
    $query = "SELECT 
        SUM(CASE WHEN status IN ('active', 'in_progress') THEN 1 ELSE 0 END) as active_quests,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_quests,
        SUM(CASE WHEN status = 'completed' THEN cash_reward ELSE 0 END) as total_earnings
        FROM quests 
        WHERE student_id = ?";
        
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $stats = array_merge($stats, $result);
    }
    
    return $stats;
}

function getQuestStatusLabel($status) {
    $labels = [
        'active' => 'Active',
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    return $labels[$status] ?? $status;
}

function canUpdateQuestStatus($current_status, $new_status, $role) {
    $allowed_transitions = [
        'faculty' => [
            'active' => ['pending', 'cancelled'],
            'pending' => ['active', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => ['active']
        ],
        'student' => [
            'active' => ['in_progress'],
            'in_progress' => ['completed']
        ]
    ];

    return isset($allowed_transitions[$role][$current_status]) && 
           in_array($new_status, $allowed_transitions[$role][$current_status]);
}

function getFacultyQuests($faculty_id, $status = 'active') {
    $conn = Database::getInstance();
    
    $query = "SELECT q.*, 
                     CONCAT(s.first_name, ' ', s.last_name) as student_name,
                     s.email as student_email
              FROM quests q
              LEFT JOIN users s ON q.student_id = s.id
              WHERE q.faculty_id = ? AND q.status = ?
              ORDER BY q.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $faculty_id, $status);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getStudentQuests($student_id, $status = 'active') {
    $conn = Database::getInstance();
    
    $query = "SELECT q.*, 
                     CONCAT(f.first_name, ' ', f.last_name) as faculty_name,
                     f.email as faculty_email,
                     f.office_name,
                     f.room_number
              FROM quests q
              JOIN users f ON q.faculty_id = f.id
              WHERE q.student_id = ? AND q.status = ?
              ORDER BY q.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $student_id, $status);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function escapeHTML($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    return escapeHTML($data);
}

function validateInput($data, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'float':
            return filter_var($data, FILTER_VALIDATE_FLOAT);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        case 'string':
            return is_string($data) ? $data : false;
        default:
            return false;
    }
}

function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return escapeHTML($data);
}

// Security headers function
function setSecurityHeaders() {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
}
?>