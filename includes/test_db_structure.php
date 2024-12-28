<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

try {
    require_once 'config.php';
    $conn = Database::getInstance();

    // Get quests table structure
    $result = $conn->query("DESCRIBE quests");
    $quests_columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $quests_columns[] = $row;
        }
    }

    // Check if user_quests table exists
    $user_quests_exists = $conn->query("SHOW TABLES LIKE 'user_quests'")->num_rows > 0;
    $user_quests_columns = [];
    if ($user_quests_exists) {
        $result = $conn->query("DESCRIBE user_quests");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $user_quests_columns[] = $row;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'quests_table' => [
            'columns' => $quests_columns
        ],
        'user_quests_table' => [
            'exists' => $user_quests_exists,
            'columns' => $user_quests_columns
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 