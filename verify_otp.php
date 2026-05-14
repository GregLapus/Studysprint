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
$success = '';
$show_login_button = false;

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
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        otp_code VARCHAR(6),
        otp_expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $otp = $_POST['otp'] ?? '';
    $email = $_SESSION['pending_email'] ?? '';
    
    if (!empty($otp) && !empty($email)) {
        // Check OTP
        $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Check if OTP matches and not expired
            if ($user['otp_code'] === $otp && strtotime($user['otp_expires_at']) > time()) {
                // Verify user
                $updateStmt = $conn->prepare("UPDATE users SET is_verified = TRUE, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                
                // Clear session
                unset($_SESSION['pending_email']);
                
                $success = 'Your account is successfully verified!';
                $show_login_button = true;
            } else {
                $error = 'Invalid or expired OTP. Please try again.';
            }
        } else {
            $error = 'User not found. Please register again.';
        }
    } else {
        $error = 'Please enter the OTP code.';
    }
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    $email = $_SESSION['pending_email'] ?? '';
    
    if (!empty($email)) {
        // Generate new OTP
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Update OTP in database
        $stmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
        $stmt->bind_param("sss", $otp, $otpExpires, $email);
        $stmt->execute();
        
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
            $success = "New OTP sent to your email: <strong>$email</strong><br><small>Please check your inbox (and spam folder).</small>";
        } catch (\Exception $e) {
            // Email sending failed
            $error = "Email sending failed: " . $mail->ErrorInfo . "<br>Please check your PHPMailer configuration and try again.";
        }
    } else {
        $error = 'Session expired. Please register again.';
    }
}

// If no pending email, redirect to signup
if (!isset($_SESSION['pending_email'])) {
    header('Location: signup.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - StudySprint</title>
    <link rel="stylesheet" href="styles-split.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-container">
    <div class="auth-card">
        <h2>Verify Your Account</h2>
        <p class="auth-subtitle">Enter the 6-digit code sent to your email</p>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="floating-success" id="successMessage">
                <h3 style="margin: 0 0 10px 0; font-size: 18px; font-family: var(--font-heading);">✓ Success</h3>
                <p style="margin: 0; font-size: 16px;"><?php echo $success; ?></p>
                <?php if (isset($show_login_button) && $show_login_button): ?>
                    <a href="login.php" class="login-btn">Go to Login</a>
                <?php endif; ?>
            </div>
            <script>
                setTimeout(function() {
                    var message = document.getElementById('successMessage');
                    if (message) {
                        message.style.opacity = '0';
                        message.style.transition = 'opacity 0.5s ease-out';
                        setTimeout(function() {
                            message.style.display = 'none';
                        }, 500);
                    }
                }, 30000);
            </script>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="verify">
            <input type="text" name="otp" class="otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
            <button type="submit" class="btn-submit">Verify</button>
        </form>

        <form method="POST" action="">
            <input type="hidden" name="action" value="resend">
            <button type="submit" class="btn-submit" style="background: var(--gradient-warm); box-shadow: 0 4px 14px rgba(213, 94, 246, 0.35);">Resend OTP</button>
        </form>
        <?php endif; ?>

        <a href="signup.php" class="back-link">← Back to Signup</a>
    </div>
</body>
</html>
