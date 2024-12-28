<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Prevent brute force attacks
    if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 5) {
        if (time() - $_SESSION['last_attempt'] < 300) { // 5 minutes lockout
            throw new Exception('Too many failed attempts. Please try again later.');
        }
        $_SESSION['login_attempts'] = 0;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $password = $data['password'];
    
    // Validate input
    if (!$email || empty($password)) {
        throw new Exception('Invalid email or password format');
    }
    
    $conn = Database::getInstance();
    
    // Get user with this email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception('Database error');
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Increment failed attempts
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt'] = time();
        throw new Exception('Invalid email or password');
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    $valid = password_verify($password, $user['password']) || $password === $user['actual_password'];
    if (!$valid) {
        // Increment failed attempts
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_attempt'] = time();
        throw new Exception('Invalid email or password');
    }
    
    // If using actual_password, update to hashed password
    if ($valid && $password === $user['actual_password']) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user['id']);
        $update->execute();
    }
    
    // Reset login attempts on successful login
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_attempt']);
    
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    
    // Log successful login
    error_log("Successful login - User ID: {$user['id']}, Role: {$user['role']}, IP: {$_SERVER['REMOTE_ADDR']}");
    
    echo json_encode([
        'success' => true,
        'role' => $user['role'],
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage() . " - IP: " . $_SERVER['REMOTE_ADDR']);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 