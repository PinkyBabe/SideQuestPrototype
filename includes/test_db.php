<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Include config file
    if (file_exists(__DIR__ . '/config.php')) {
        require_once 'config.php';
        
        // Try to get database instance
        $conn = Database::getInstance();
        
        // Check if quests table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'quests'");
        
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful',
            'config_exists' => true,
            'db_connected' => ($conn !== null),
            'quests_table_exists' => ($table_check && $table_check->num_rows > 0)
        ]);
    } else {
        throw new Exception('Config file not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?> 