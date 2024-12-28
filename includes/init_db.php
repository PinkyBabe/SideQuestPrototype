<?php
require_once 'config.php';

try {
    $conn = Database::getInstance();
    
    // Create courses table
    $conn->query("
        CREATE TABLE IF NOT EXISTS courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            major VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create users table with profile_pic column
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            actual_password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'faculty', 'student') NOT NULL,
            course_id INT,
            year_level INT,
            office_name VARCHAR(100),
            room_number VARCHAR(20),
            profile_pic VARCHAR(255) DEFAULT 'images/default_avatar.png',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id)
        )
    ");
    
    // Create quests table
    $conn->query("
        CREATE TABLE IF NOT EXISTS quests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            faculty_id INT NOT NULL,
            description TEXT NOT NULL,
            job_type VARCHAR(100) NOT NULL,
            cash_reward DECIMAL(10,2),
            snack_reward TINYINT(1) DEFAULT 0,
            status ENUM('active', 'in_progress', 'completed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES users(id)
        )
    ");
    
    // Create user_quests table
    $conn->query("
        CREATE TABLE IF NOT EXISTS user_quests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quest_id INT NOT NULL,
            student_id INT NOT NULL,
            status ENUM('accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'accepted',
            accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (quest_id) REFERENCES quests(id),
            FOREIGN KEY (student_id) REFERENCES users(id)
        )
    ");

    // Add profile_pic column if it doesn't exist
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT 'images/default_avatar.png'");
    }

    // Insert sample data if tables are empty
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert sample courses
        $conn->query("
            INSERT INTO courses (code, name, description) VALUES 
            ('BSIT', 'Bachelor of Science in Information Technology', 'IT Program'),
            ('BSCS', 'Bachelor of Science in Computer Science', 'CS Program')
        ");

        // Insert sample admin
        $conn->query("
            INSERT INTO users (first_name, last_name, email, actual_password, role, profile_pic)
            VALUES ('Admin', 'User', 'admin@example.com', 'admin123', 'admin', 'images/default_avatar.png')
        ");
        
        // Insert sample faculty
        $conn->query("
            INSERT INTO users (first_name, last_name, email, actual_password, role, office_name, room_number, profile_pic)
            VALUES ('Faculty', 'Member', 'faculty@example.com', 'faculty123', 'faculty', 'IT Department', 'Room 101', 'images/default_avatar.png')
        ");
        
        // Insert sample student
        $conn->query("
            INSERT INTO users (first_name, last_name, email, actual_password, role, year_level, profile_pic)
            VALUES ('Student', 'User', 'student@example.com', 'student123', 'student', 1, 'images/default_avatar.png')
        ");

        // Insert sample quests
        $faculty_id = $conn->insert_id - 1; // Get the faculty ID
        $conn->query("
            INSERT INTO quests (faculty_id, description, job_type, cash_reward, snack_reward) VALUES 
            ($faculty_id, 'Help organize office files', 'Office Work', 100.00, 1),
            ($faculty_id, 'Clean the faculty room', 'Cleaning', 50.00, 0),
            ($faculty_id, 'Print and compile documents', 'Printing', 75.00, 1)
        ");
    }
    
    echo "Database initialized successfully!";
    
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    die("Error initializing database: " . $e->getMessage());
} 