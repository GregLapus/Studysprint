<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer (required)
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Load mail configuration
require 'mail_config.php';

session_start();

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studysprint';

// Create database connection
$conn = new mysqli($host, $username, $password);

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

$error = '';
$success = '';

// Handle forgot password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    $email = $_POST['email'] ?? '';
    
    if (!empty($email)) {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $otpExpires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Update OTP in database
            $updateStmt = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE email = ?");
            $updateStmt->bind_param("sss", $otp, $otpExpires, $email);
            $updateStmt->execute();
            
            // Store email in session for password reset
            $_SESSION['reset_email'] = $email;
            
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
                $mail->Subject = "StudySprint - Reset Your Password";
                $mail->Body = "Your password reset code is: $otp\n\nThis code expires in 10 minutes.\n\nIf you didn't request this code, please ignore this email.";
                
                $mail->send();
                $success = "A verification code has been sent to your email: <strong>$email</strong><br><small>Please check your inbox (and spam folder).</small><br><a href='reset_password.php' style='color: #28a745;'>Click here to reset your password</a>";
            } catch (\Exception $e) {
                // Email sending failed
                $error = "Email sending failed: " . $mail->ErrorInfo . "<br>Please check your PHPMailer configuration and try again.";
            }
        } else {
            $error = 'Email not found. Please check your email address.';
        }
    } else {
        $error = 'Please enter your email address.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - StudySprint</title>
    <link rel="stylesheet" href="styles-split.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-container">
    <div class="auth-card">
        <h2>Forgot Password?</h2>
        <p class="auth-subtitle">Enter your email and we'll send you a verification code</p>

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
            <input type="hidden" name="action" value="send_otp">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn-submit">Send Reset Code</button>
        </form>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>
