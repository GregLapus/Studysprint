-- StudySprint Database Setup
-- Run this in phpMyAdmin or MySQL to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS studysprint 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE studysprint;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    otp_code VARCHAR(6),
    otp_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Study sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    duration DECIMAL(4,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User profiles table
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(50),
    year_level VARCHAR(50),
    school VARCHAR(200),
    bio TEXT,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample user (password: test123)
-- You can use this to test login
-- email: test@example.com, password: test123
-- INSERT IGNORE INTO users (name, email, password) VALUES 
-- ('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample study sessions for test user
-- INSERT IGNORE INTO sessions (user_id, title, subject, date, time, duration, notes, status) VALUES
-- (1, 'Mathematics Revision', 'Math', '2025-05-01', '14:00', 2.00, 'Chapter 5 - Algebra', 'pending'),
-- (1, 'Science Project', 'Science', '2025-05-02', '10:00', 3.00, 'Complete lab report', 'pending'),
-- (1, 'History Essay', 'History', '2025-04-28', '16:00', 1.50, 'World War II research', 'completed');
