<?php
require_once 'config.php';

try {
    $conn = Database::getInstance();
    
    // Disable foreign key checks temporarily
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables in correct order
    $conn->query("DROP TABLE IF EXISTS user_quests");
    $conn->query("DROP TABLE IF EXISTS quest_comments");
    $conn->query("DROP TABLE IF EXISTS quests");
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("DROP TABLE IF EXISTS courses");
    
    // Enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create courses table
    $result = $conn->query("
        CREATE TABLE courses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(255) NOT NULL,
            major VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    if (!$result) {
        throw new Exception("Error creating courses table: " . $conn->error);
    }

    // Create users table with improved profile fields
    $result = $conn->query("
        CREATE TABLE users (
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
            bio TEXT,
            contact_number VARCHAR(20),
            department VARCHAR(100),
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id)
        )
    ");
    
    if (!$result) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    // Create quests table with improved fields
    $result = $conn->query("
        CREATE TABLE quests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            faculty_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            job_type VARCHAR(100) NOT NULL,
            location VARCHAR(255),
            deadline DATETIME,
            estimated_hours INT,
            cash_reward DECIMAL(10,2),
            snack_reward TINYINT(1) DEFAULT 0,
            max_participants INT DEFAULT 1,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            status ENUM('active', 'in_progress', 'completed', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (faculty_id) REFERENCES users(id)
        )
    ");
    
    if (!$result) {
        throw new Exception("Error creating quests table: " . $conn->error);
    }

    // Create user_quests table with improved tracking
    $result = $conn->query("
        CREATE TABLE user_quests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quest_id INT NOT NULL,
            student_id INT NOT NULL,
            status ENUM('accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'accepted',
            rating INT,
            feedback TEXT,
            accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (quest_id) REFERENCES quests(id),
            FOREIGN KEY (student_id) REFERENCES users(id)
        )
    ");
    
    if (!$result) {
        throw new Exception("Error creating user_quests table: " . $conn->error);
    }

    // Create quest_comments table for communication
    $result = $conn->query("
        CREATE TABLE quest_comments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            quest_id INT NOT NULL,
            user_id INT NOT NULL,
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quest_id) REFERENCES quests(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ");
    
    if (!$result) {
        throw new Exception("Error creating quest_comments table: " . $conn->error);
    }

    // Insert sample data
    // Insert courses
    $result = $conn->query("
        INSERT INTO courses (code, name, description) VALUES 
        ('BSIT', 'Bachelor of Science in Information Technology', 'IT Program'),
        ('BSCS', 'Bachelor of Science in Computer Science', 'CS Program')
    ");
    
    if (!$result) throw new Exception("Error inserting courses: " . $conn->error);

    // Insert sample users with more details
    $result = $conn->query("
        INSERT INTO users (first_name, last_name, email, actual_password, role, office_name, room_number, department, contact_number, bio, profile_pic) VALUES 
        ('Admin', 'User', 'admin@example.com', 'admin123', 'admin', 'Admin Office', 'A101', 'Administration', '123-456-7890', 'System Administrator', 'images/default_avatar.png'),
        ('Faculty', 'Member', 'faculty@example.com', 'faculty123', 'faculty', 'IT Department', 'B202', 'Information Technology', '123-456-7891', 'IT Professor', 'images/default_avatar.png'),
        ('Student', 'User', 'student@example.com', 'student123', 'student', NULL, NULL, 'Information Technology', '123-456-7892', 'IT Student', 'images/default_avatar.png')
    ");
    
    if (!$result) throw new Exception("Error inserting users: " . $conn->error);

    // Get faculty ID for sample quests
    $faculty_id = $conn->insert_id - 1;

    // Insert sample quests with more details
    $result = $conn->query("
        INSERT INTO quests (faculty_id, title, description, job_type, location, deadline, estimated_hours, cash_reward, snack_reward, priority) VALUES 
        ($faculty_id, 'Office Organization', 'Help organize office files and documents', 'Office Work', 'Room B202', DATE_ADD(NOW(), INTERVAL 7 DAY), 4, 100.00, 1, 'high'),
        ($faculty_id, 'Faculty Room Cleaning', 'Clean and sanitize the faculty room', 'Cleaning', 'Faculty Room', DATE_ADD(NOW(), INTERVAL 3 DAY), 2, 50.00, 0, 'medium'),
        ($faculty_id, 'Document Processing', 'Print and compile important documents', 'Printing', 'IT Lab', DATE_ADD(NOW(), INTERVAL 5 DAY), 3, 75.00, 1, 'medium')
    ");
    
    if (!$result) throw new Exception("Error inserting quests: " . $conn->error);

    echo "Database updated successfully!";
    
} catch (Exception $e) {
    error_log("Database update error: " . $e->getMessage());
    die("Error updating database: " . $e->getMessage() . "\n");
} 