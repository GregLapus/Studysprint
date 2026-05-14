<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'studysprint';

// Create database connection
$conn = new mysqli($host, $username, $password);
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

$error = '';
$success = '';

// Check if user has a valid reset session
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit;
}

$email = $_SESSION['reset_email'];

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $otp = $_POST['otp'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($otp) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Check OTP and get user
        $stmt = $conn->prepare("SELECT id, otp_code, otp_expires_at FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Check if OTP is valid and not expired
            if ($user['otp_code'] === $otp && strtotime($user['otp_expires_at']) > time()) {
                // Hash new password and update
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
                $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                
                if ($updateStmt->execute()) {
                    // Clear session
                    unset($_SESSION['reset_email']);
                    
                    $success = 'Your password has been successfully reset! You can now login with your new password.';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            } else {
                $error = 'Invalid or expired verification code.';
            }
        } else {
            $error = 'User not found. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - StudySprint</title>
    <link rel="stylesheet" href="styles-split.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-container">
    <div class="auth-card">
        <h2>Reset Password</h2>
        <p class="auth-subtitle">Enter verification code and your new password</p>

        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo $success; ?><br>
                <a href="login.php" style="color: #4f46e5; font-weight: 600; text-decoration: underline;">Go to Login</a>
            </div>
        <?php else: ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="reset_password">
            <div class="form-group">
                <label for="otp">Verification Code</label>
                <input type="text" id="otp" name="otp" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
            <button type="submit" class="btn-submit">Reset Password</button>
        </form>
        <?php endif; ?>

        <a href="forgot_password.php" class="back-link">← Back to Forgot Password</a>
    </div>
</body>
</html>