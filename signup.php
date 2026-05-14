<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studysprint';

// Create database connection
$conn = new mysqli($host, $username, $password);

// Load PHPMailer (required)
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Load mail configuration
require 'mail_config.php';

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

// Create users table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        otp_code VARCHAR(6),
        otp_expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create user_profiles table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS user_profiles (
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

// Handle registration form submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $student_id = $_POST['student_id'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    $school = $_POST['school'] ?? '';

    if (!empty($name) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        // Check if passwords match
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else {
            // Check if name already exists
            $nameCheckStmt = $conn->prepare("SELECT id FROM users WHERE name = ?");
            $nameCheckStmt->bind_param("s", $name);
            $nameCheckStmt->execute();
            $nameCheckResult = $nameCheckStmt->get_result();

            if ($nameCheckResult->num_rows > 0) {
                $error = 'Name already exists. Please use a different name.';
            } else {
                // Check if email already exists
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->bind_param("s", $email);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    $error = 'Email already exists. Please use a different email.';
                } else {
                    // Generate OTP
                    $otp = sprintf("%06d", mt_rand(100000, 999999));
                    $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    // Hash password and insert user with OTP
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $otp, $otpExpires);

                    if ($stmt->execute()) {
                        $userId = $conn->insert_id;

                        // Insert profile data
                        $profileStmt = $conn->prepare("INSERT INTO user_profiles (user_id, student_id, year_level, school) VALUES (?, ?, ?, ?)");
                        $profileStmt->bind_param("isss", $userId, $student_id, $year_level, $school);
                        $profileStmt->execute();

                        // Store email in session for verification
                        $_SESSION['pending_email'] = $email;

                        // Send OTP via email
                        try {
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            $mail->isSMTP();
                            $mail->Host = $mail_host;
                            $mail->SMTPAuth = $mail_smtp_auth;
                            $mail->Username = $mail_username;
                            $mail->Password = $mail_password;
                            $mail->SMTPSecure = $mail_smtp_secure;
                            $mail->Port = $mail_port;

                            $mail->setFrom($mail_from, $mail_from_name);
                            $mail->addAddress($email);
                            $mail->Subject = "StudySprint - Verify Your Account";
                            $mail->Body = "Your verification code is: $otp\n\nThis code expires in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";

                            $mail->send();
                            $success = "Account created! A verification code has been sent to your email: <strong>$email</strong><br><small>Please check your inbox (and spam folder).</small><br><a href='verify_otp.php'>Click here to verify</a>";
                        } catch (\Exception $e) {
                            $error = "Email sending failed: " . $mail->ErrorInfo . "<br>Please check your PHPMailer configuration and try again.";
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    } else {
        $error = 'Please fill in all required fields';
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
    <title>Create Account - StudySprint</title>
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
                    <button class="btn-signup" onclick="window.location.href='login.php'">Sign In</button>
                </div>
            </div>

            <!-- Right Side - Form -->
            <div class="split-right">
                <div class="split-form">
                    <h2>Create Account</h2>
                    <p class="subtitle">Join us to get started</p>

                    <?php if ($error): ?>
                        <div class="error-message">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-message">
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="register">
                        <div class="input-group">
                            <label for="name">Name</label>
                            <input type="text" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="input-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" placeholder="Create a password" required>
                        </div>
                        <div class="input-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                        <div class="input-group">
                            <input type="text" name="student_id" placeholder="Student ID (Optional)">
                        </div>
                        <div class="input-group">
                            <input type="text" name="year_level" placeholder="Year/Grade Level (Optional)">
                        </div>
                        <div class="input-group">
                            <input type="text" name="school" placeholder="School (Optional)">
                        </div>
                        <button type="submit" class="btn-submit">Sign Up</button>
                    </form>
                    <div class="form-footer">
                        <span>Already have an account? <a href="login.php">Sign In</a></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
