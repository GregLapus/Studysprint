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

// Create database connection with error handling
try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        $dbError = 'Database connection failed: ' . $conn->connect_error;
    } else {
        // Create database if it doesn't exist
        $conn->query("CREATE DATABASE IF NOT EXISTS $database");
        $conn->select_db($database);
        
        // Create users table if it doesn't exist
        $conn->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
} catch (Exception $e) {
    $dbError = 'Database error: ' . $e->getMessage();
}

// Handle login form submission
if (empty($dbError) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Check if user is verified
                if ($user['is_verified'] == 1) {
                    $_SESSION['user'] = $user;
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Please verify your email first. Check your inbox for the verification code.';
                }
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'User not found';
        }
    } else {
        $error = 'Please fill in all fields';
    }
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - StudySprint</title>
    <link rel="stylesheet" href="styles-split.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="split-container">
        <div class="split-card">
            <!-- Left Side - Gradient -->
            <div class="split-left">
                <div class="split-left-content">
                    <span class="logo-icon">📖</span>
                    <h1>StudySprint</h1>
                    <p>Your smart study companion for organized learning and productivity.</p>
                    <button class="btn-signup" onclick="window.location.href='signup.php'">Create Account</button>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="split-right">
                <div class="split-form">
                    <h2>Welcome Back</h2>
                    <p class="subtitle">Sign in to continue your journey</p>

                    <?php if ($error || $dbError): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error ?: $dbError); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="login">
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <button type="submit" class="btn-submit">Sign In</button>
                    </form>
                    <div class="form-footer">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
