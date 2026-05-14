<?php
/**
 * Database Configuration File
 * Include this file in your PHP scripts to connect to the database
 * 
 * Usage: require_once 'db_config.php';
 */

// Database configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';  // Default XAMPP password is empty
$db_name = 'studysprint';

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");

// Select the database
$conn->select_db($db_name);

// Set charset to handle special characters
$conn->set_charset("utf8mb4");

// Create users table if it doesn't exist
$users_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($users_table);

// Create sessions table if it doesn't exist
$sessions_table = "CREATE TABLE IF NOT EXISTS sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sessions_table);
?>
