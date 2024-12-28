-- Drop and recreate database
DROP DATABASE IF EXISTS sidequest_db;
CREATE DATABASE sidequest_db;
USE sidequest_db;

-- Create courses table first (since users references it)
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some default courses
INSERT INTO courses (code, name) VALUES
('BSIT', 'Bachelor of Science in Information Technology'),
('BSCS', 'Bachelor of Science in Business Administration'),
('BSIS', 'Bachelor of Science in Education');

-- Create users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    actual_password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty', 'student') NOT NULL,
    course_id INT,
    office_name VARCHAR(100),
    room_number VARCHAR(20),
    profile_pic VARCHAR(255) DEFAULT 'images/default_avatar.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test users with hashed passwords
INSERT INTO users (first_name, last_name, email, password, actual_password, role, office_name) 
VALUES ('Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin123', 'admin', 'Admin Office');

INSERT INTO users (first_name, last_name, email, password, actual_password, role, office_name) 
VALUES ('Test', 'Faculty', 'faculty@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', 'faculty', 'Test Department');

INSERT INTO users (first_name, last_name, email, password, actual_password, role, course_id, is_active) 
VALUES ('Test', 'Student', 'student@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'password', 'student', 1, 1);

-- Create quests table with all required fields
CREATE TABLE quests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    faculty_id INT NOT NULL,
    description TEXT NOT NULL,
    job_type VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    meeting_time DATETIME NOT NULL,
    estimated_hours INT NOT NULL,
    cash_reward DECIMAL(10,2),
    snack_reward TINYINT(1) DEFAULT 0,
    status ENUM('active', 'in_progress', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user_quests table
CREATE TABLE user_quests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quest_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'accepted',
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (quest_id) REFERENCES quests(id),
    FOREIGN KEY (student_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add workspace-related tables
CREATE TABLE IF NOT EXISTS workspace_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    quest_id INT NOT NULL,
    activity_type ENUM('create', 'accept', 'complete', 'approve', 'reject') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quest_id) REFERENCES quests(id)
);

CREATE TABLE IF NOT EXISTS quest_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quest_id INT NOT NULL,
    student_id INT NOT NULL,
    faculty_id INT NOT NULL,
    submission_text TEXT,
    file_path VARCHAR(255),
    file_name VARCHAR(255),
    file_type VARCHAR(100),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quest_id) REFERENCES quests(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (faculty_id) REFERENCES users(id)
);

-- Add workspace statistics table
CREATE TABLE IF NOT EXISTS workspace_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_quests INT DEFAULT 0,
    completed_quests INT DEFAULT 0,
    active_quests INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
