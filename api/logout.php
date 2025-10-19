<?php
// api/logout.php - Logout handler
session_start();
// Database connection
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";

$conn = new mysqli($host, $username, $password, $dbname, $port);

// Delete session from database
if (isset($_SESSION['session_id']) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id = ? AND user_id = ?");
        $stmt->bind_param("si", $_SESSION['session_id'], $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Logout error: " . $e->getMessage());
    }
}

$conn->close();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: /CAMPUS-POLLS/login.html?logout=success");
exit();
?>
