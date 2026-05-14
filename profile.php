<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studysprint';

$error = '';
$dbError = '';
$success = '';

// Create database connection with error handling
try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        $dbError = 'Database connection failed: ' . $conn->connect_error;
    } else {
        $conn->query("CREATE DATABASE IF NOT EXISTS $database");
        $conn->select_db($database);
        
        // Create user_profiles table if it doesn't exist - suppress warnings
        @$conn->query("CREATE TABLE IF NOT EXISTS user_profiles (
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
        )");
    }
} catch (Exception $e) {
    $dbError = 'Database error: ' . $e->getMessage();
}

// Handle profile update form submission
if (empty($dbError) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $user_id = $_SESSION['user']['id'];
    $student_id = $_POST['student_id'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $school = $_POST['school'] ?? '';
    $bio = $_POST['bio'] ?? '';
    
    if (!empty($student_id) || !empty($year_level) || !empty($school) || !empty($bio)) {
        $stmt = $conn->prepare("INSERT INTO user_profiles (user_id, student_id, year_level, school, bio) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE student_id = VALUES(student_id), year_level = VALUES(year_level), school = VALUES(school), bio = VALUES(bio), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sssss", $user_id, $student_id, $year_level, $school, $bio);
        
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile';
        }
    } else {
        $error = 'Please fill in at least one field';
    }
}

// Get user profile data
$profile = null;
if (empty($dbError) && isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
}

// If not logged in, redirect to login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - StudySprint</title>
    <link rel="stylesheet" href="styles.css?v=6">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: rgba(0, 0, 0, 0.1);
            --text-primary: rgba(255, 255, 255, 0.9);
            --text-secondary: rgba(255, 255, 255, 0.7);
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.3);
            --input-placeholder: rgba(255, 255, 255, 0.6);
            --focus-border: rgba(255, 255, 255, 0.5);
            --focus-bg: rgba(255, 255, 255, 0.15);
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
        }
        
        .profile-header {
            padding: 60px 20px 40px;
            text-align: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .profile-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .profile-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .profile-info {
            max-width: 800px;
            margin: 40px auto;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px var(--glass-shadow);
            transition: all 0.3s ease;
        }
        
        .profile-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px var(--glass-shadow);
        }
        
        .profile-info h2 {
            color: var(--text-primary);
            margin-bottom: 30px;
            font-size: 1.8rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        .profile-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid var(--input-border);
            border-radius: 12px;
            font-size: 1rem;
            background: var(--input-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--input-placeholder);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--focus-border);
            background: var(--focus-bg);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        
        .btn-secondary {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            color: var(--text-primary);
            padding: 12px 30px;
            border-radius: 12px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 500;
        }
        
        .btn-secondary:hover {
            background: var(--focus-bg);
            transform: translateY(-2px);
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .profile-form {
                grid-template-columns: 1fr;
            }
            
            .profile-info {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .profile-header {
                padding: 40px 20px 30px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="profile-header">
        <h1>👤 My Profile</h1>
        <p>Manage your personal information</p>
    </div>
    
    <div class="profile-info">
        <h2>Personal Information</h2>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($dbError): ?>
            <div class="error-message">
                <strong>Database Error:</strong> <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-form">
            <div class="form-group">
                <label>Name</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></div>
            </div>
            
            <div class="form-group">
                <label>Student ID</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo htmlspecialchars($profile['student_id'] ?? 'Not provided'); ?></div>
            </div>
            
            <div class="form-group">
                <label>Year/Grade Level</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo htmlspecialchars($profile['year_level'] ?? 'Not provided'); ?></div>
            </div>
            
            <div class="form-group">
                <label>School</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo htmlspecialchars($profile['school'] ?? 'Not provided'); ?></div>
            </div>
            
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Bio/About Me</label>
                <div style="color: rgba(255,255,255,0.9); font-size: 1rem; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.1);"><?php echo nl2br(htmlspecialchars($profile['bio'] ?? 'Not provided')); ?></div>
            </div>
        </div>
        
        <div class="button-group">
            <a href="edit_profile.php" class="btn-primary">Edit Profile</a>
            <a href="index.php" class="btn-secondary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>