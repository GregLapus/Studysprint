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

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $database");
$conn->select_db($database);

// Create tables if they don't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$conn->query("
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
    )
");

// Handle login
if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}

// Handle registration
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    
    if ($stmt->execute()) {
        $_SESSION['user'] = [
            'id' => $conn->insert_id,
            'name' => $name,
            'email' => $email
        ];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
    exit;
}

// Handle logout
if (isset($_POST['action']) && $_POST['action'] == 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Handle session CRUD operations
if (isset($_POST['action']) && $_POST['action'] == 'add_session') {
    $user_id = $_SESSION['user']['id'];
    $title = $_POST['title'];
    $subject = $_POST['subject'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $duration = $_POST['duration'];
    $notes = $_POST['notes'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("INSERT INTO sessions (user_id, title, subject, date, time, duration, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssdss", $user_id, $title, $subject, $date, $time, $duration, $notes, $status);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add session']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'update_session') {
    if (isset($_POST['session_id']) && isset($_POST['title']) && isset($_POST['subject']) && isset($_POST['date']) && isset($_POST['time']) && isset($_POST['duration']) && isset($_POST['notes']) && isset($_POST['status'])) {
        $session_id = $_POST['session_id'];
        $title = $_POST['title'];
        $subject = $_POST['subject'];
        $date = $_POST['date'];
        $time = $_POST['time'];
        $duration = $_POST['duration'];
        $notes = $_POST['notes'];
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE sessions SET title=?, subject=?, date=?, time=?, duration=?, notes=?, status=? WHERE id=? AND user_id=?");
        $stmt->bind_param("ssssdsiii", $title, $subject, $date, $time, $duration, $notes, $status, $session_id, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update session']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'delete_session') {
    if (isset($_POST['session_id'])) {
        $session_id = $_POST['session_id'];
        
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $session_id, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete session']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'complete_session') {
    if (isset($_POST['session_id'])) {
        $session_id = $_POST['session_id'];
        
        $stmt = $conn->prepare("UPDATE sessions SET status='completed' WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $session_id, $_SESSION['user']['id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to complete session']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    }
    exit;
}

// Handle get sessions
if (isset($_GET['action']) && $_GET['action'] == 'get_sessions') {
    $filter = $_GET['filter'] ?? 'all';
    $user_id = $_SESSION['user']['id'] ?? null;
    
    if ($user_id) {
        $query = "SELECT * FROM sessions WHERE user_id=?";
        $params = [$user_id];
        $types = "i";
        
        if ($filter != 'all') {
            $query .= " AND status=?";
            $params[] = $filter;
            $types .= "s";
        }
        
        $query .= " ORDER BY date DESC, time DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sessions = [];
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        
        echo json_encode(['success' => true, 'sessions' => $sessions]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
    }
    exit;
}

// Handle get stats
if (isset($_GET['action']) && $_GET['action'] == 'get_stats') {
    $user_id = $_SESSION['user']['id'] ?? null;
    
    if ($user_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM sessions WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM sessions WHERE user_id=? AND status='completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $completed = $stmt->get_result()->fetch_assoc()['completed'];
        
        $stmt = $conn->prepare("SELECT SUM(duration) as hours FROM sessions WHERE user_id=? AND status='completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hours = $stmt->get_result()->fetch_assoc()['hours'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_sessions' => $total,
                'completed_sessions' => $completed,
                'total_hours' => number_format($hours, 1)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
    }
    exit;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);
$current_user = $_SESSION['user'] ?? null;

// If not logged in, redirect to login page
if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudySprint - Personal Study Session Organizer</title>
    <link rel="stylesheet" href="styles.css?v=6">
</head>
<body>
    <div id="app">
        <!-- Dashboard Page -->
        <div id="dashboardPage" class="page active">
            <header class="header">
                <div class="container">
                    <div class="header-content">
                        <h1 class="logo">StudySprint</h1>
                        <div class="user-info">
                            <span id="userName">Welcome <?php echo htmlspecialchars($current_user['name']); ?></span>
                            <a href="profile.php" class="btn btn-outline" style="margin-right: 10px;">Profile</a>
                            <button id="logoutBtn" class="btn btn-secondary">Logout</button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="container">
                    <div class="dashboard-header">
                        <h2>My Study Sessions</h2>
                        <button id="addSessionBtn" class="btn btn-primary">+ Add Session</button>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <h3>Total Sessions</h3>
                            <p id="totalSessions">0</p>
                        </div>
                        <div class="stat-card">
                            <h3>Completed</h3>
                            <p id="completedSessions">0</p>
                        </div>
                        <div class="stat-card">
                            <h3>Hours Studied</h3>
                            <p id="totalHours">0</p>
                        </div>
                    </div>

                    <div class="sessions-container">
                        <div class="filter-tabs">
                            <button class="tab-btn active" data-filter="all">All</button>
                            <button class="tab-btn" data-filter="pending">Pending</button>
                            <button class="tab-btn" data-filter="completed">Completed</button>
                        </div>

                        <div id="sessionsList" class="sessions-list">
                            <!-- Sessions will be dynamically added here -->
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="footer">
                <div class="container">
                    <div class="footer-content">
                        <div class="footer-section">
                            <h3>StudySprint</h3>
                            <p>Your personal study session organizer for academic success.</p>
                        </div>
                        <div class="footer-section">
                            <h4>Features</h4>
                            <ul>
                                <li>Session Management</li>
                                <li>Progress Tracking</li>
                                <li>Time Management</li>
                                <li>Goal Setting</li>
                            </ul>
                        </div>
                        <div class="footer-section">
                            <h4>Resources</h4>
                            <ul>
                                <li>Study Tips</li>
                                <li>Time Management</li>
                                <li>Productivity Hacks</li>
                                <li>Academic Success</li>
                            </ul>
                        </div>
                        <div class="footer-section">
                            <h4>Connect With Us</h4>
                            <div class="social-links">
                                <a href="https://facebook.com/studysprint" target="_blank" class="social-link facebook" title="Facebook">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="https://instagram.com/studysprint" target="_blank" class="social-link instagram" title="Instagram">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1 1 12.324 0 6.162 6.162 0 0 1-12.324 0zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm4.965-10.405a1.44 1.44 0 1 1 2.881.001 1.44 1.44 0 0 1-2.881-.001z"/>
                                    </svg>
                                </a>
                                <a href="https://tiktok.com/@studysprint" target="_blank" class="social-link tiktok" title="TikTok">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.69 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.88-.27-1.85-.36-2.72-.04-1.34.43-2.28 1.84-2.05 3.25.24 1.68 2.23 2.87 3.84 2.27 1.13-.4 1.85-1.57 1.83-2.77.02-3.76.01-7.52.01-11.29.01-.4 0-.8-.02-1.19z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="footer-bottom">
                        <p>&copy; 2026 StudySprint. All rights reserved. | Empowering Students Worldwide</p>
                    </div>
                </div>
            </footer>
        </div>

        <!-- Add/Edit Session Modal -->
        <div id="sessionModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modalTitle">Add Study Session</h2>
                    <button class="close-btn" id="closeModal">&times;</button>
                </div>
                <form id="sessionForm">
                    <div class="form-group">
                        <label for="sessionTitle">Session Title</label>
                        <input type="text" id="sessionTitle" required>
                    </div>
                    <div class="form-group">
                        <label for="sessionSubject">Subject</label>
                        <input type="text" id="sessionSubject" required>
                    </div>
                    <div class="form-group">
                        <label for="sessionDate">Date</label>
                        <input type="date" id="sessionDate" required>
                    </div>
                    <div class="form-group">
                        <label for="sessionTime">Time</label>
                        <input type="time" id="sessionTime" required>
                    </div>
                    <div class="form-group">
                        <label for="sessionDuration">Duration (hours)</label>
                        <input type="number" id="sessionDuration" min="0.5" step="0.5" required>
                    </div>
                    <div class="form-group">
                        <label for="sessionNotes">Notes</label>
                        <textarea id="sessionNotes" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="sessionStatus">Status</label>
                        <select id="sessionStatus">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Session</button>
                        <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="script.php"></script>
</body>
</html>
