<?php
require_once 'config.php';

try {
    $conn = Database::getInstance();
    
    // Check if quests table exists and has correct structure
    $result = $conn->query("SHOW TABLES LIKE 'quests'");
    if ($result->num_rows === 0) {
        // Create quests table
        $conn->query("
            CREATE TABLE quests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                faculty_id INT NOT NULL,
                description TEXT NOT NULL,
                job_type VARCHAR(50) NOT NULL,
                quest_location VARCHAR(255) NOT NULL,
                meeting_time DATETIME NOT NULL,
                estimated_hours INT NOT NULL,
                status ENUM('open', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (faculty_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "Created quests table<br>";
    }

    // Check quest_location column
    $result = $conn->query("SHOW COLUMNS FROM quests LIKE 'quest_location'");
    if ($result->num_rows === 0) {
        // Add quest_location column
        $conn->query("ALTER TABLE quests ADD COLUMN quest_location VARCHAR(255) NOT NULL AFTER job_type");
        echo "Added quest_location column<br>";
    }

    // Check quest_rewards table
    $result = $conn->query("SHOW TABLES LIKE 'quest_rewards'");
    if ($result->num_rows === 0) {
        // Create quest_rewards table
        $conn->query("
            CREATE TABLE quest_rewards (
                id INT PRIMARY KEY AUTO_INCREMENT,
                quest_id INT NOT NULL,
                reward_type ENUM('cash', 'food', 'both') NOT NULL,
                cash_amount DECIMAL(10,2) NULL,
                meal_type ENUM('breakfast', 'am_snack', 'lunch', 'pm_snack', 'dinner') NULL,
                FOREIGN KEY (quest_id) REFERENCES quests(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "Created quest_rewards table<br>";
    }

    // Verify faculty user exists
    $result = $conn->query("SELECT * FROM users WHERE email = 'faculty@test.com'");
    if ($result->num_rows === 0) {
        // Add test faculty user
        $conn->query("
            INSERT INTO users (
                first_name, 
                last_name, 
                email, 
                password, 
                role, 
                department
            ) VALUES (
                'Test',
                'Faculty',
                'faculty@test.com',
                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'faculty',
                'Test Department'
            )
        ");
        echo "Added test faculty user<br>";
    }

    echo "Database verification complete";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 