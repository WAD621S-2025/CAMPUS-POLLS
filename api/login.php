<?php
session_start();

$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";

$conn = new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        header("Location: /CAMPUS-POLLS/login.html?error=missing_fields");
        exit();
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        header("Location: /CAMPUS-POLLS/login.html?error=invalid_credentials");
        exit();
    }

    $stmt->bind_result($user_id, $db_username, $password_hash, $full_name);
    $stmt->fetch();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $password_hash)) {
        header("Location: /CAMPUS-POLLS/login.html?error=invalid_credentials");
        exit();
    }

    // Password is correct - Create session tracking
    try {
        // Generate unique session ID
        $session_id = bin2hex(random_bytes(32));
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        // Optional: Delete old sessions for this user (single session mode)
        // Comment out the next 3 lines if you want to allow multiple sessions per user
        $deleteStmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
        $deleteStmt->bind_param("i", $user_id);
        $deleteStmt->execute();
        $deleteStmt->close();
        
        // Insert new session into database
        $insertStmt = $conn->prepare("INSERT INTO sessions (session_id, user_id, user_agent, ip_address, last_activity, created_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $insertStmt->bind_param("siss", $session_id, $user_id, $user_agent, $ip_address);
        
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to create session in database");
        }
        $insertStmt->close();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['session_id'] = $session_id; // Important: Store DB session ID
        $_SESSION['login_time'] = time();
        
        // Redirect to home page
        header("Location: /CAMPUS-POLLS/index.html?login=success");
        exit();
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: /CAMPUS-POLLS/login.html?error=server_error");
        exit();
    }
    
} else {
    header("Location: /CAMPUS-POLLS/login.html");
    exit();
}

$conn->close();
?>