<?php
// includes/session_check.php - Handles session timeout and updates activity
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";

$conn = new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    die("Database connection error");
}

// Configuration: Set timeout duration (in seconds)
// 1800 seconds = 30 minutes
// 3600 seconds = 1 hour
// 7200 seconds = 2 hours
define('SESSION_TIMEOUT', 1800); // 30 minutes default

/**
 * Check if user session is still valid and not expired
 * @return bool True if session is valid, false if expired
 */
function checkSessionTimeout() {
    global $conn;
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_id'])) {
        return false;
    }
    
    $session_id = $_SESSION['session_id'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Get last activity from database
        $stmt = $conn->prepare("SELECT last_activity, created_at FROM sessions WHERE session_id = ? AND user_id = ?");
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Session doesn't exist in database
            $stmt->close();
            return false;
        }
        
        $session = $result->fetch_assoc();
        $stmt->close();
        
        $last_activity = strtotime($session['last_activity']);
        $current_time = time();
        
        // Calculate time difference
        $time_diff = $current_time - $last_activity;
        
        // Check if session has expired
        if ($time_diff > SESSION_TIMEOUT) {
            // Session expired - delete from database
            deleteSession($session_id, $user_id);
            return false;
        }
        
        // Session is still valid - update last activity
        updateSessionActivity($session_id, $user_id);
        return true;
        
    } catch (Exception $e) {
        error_log("Session check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update last_activity timestamp for active session
 */
function updateSessionActivity($session_id, $user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE sessions SET last_activity = NOW() WHERE session_id = ? AND user_id = ?");
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Failed to update session activity: " . $e->getMessage());
    }
}

/**
 * Delete expired session from database
 */
function deleteSession($session_id, $user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM sessions WHERE session_id = ? AND user_id = ?");
        $stmt->bind_param("si", $session_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Failed to delete session: " . $e->getMessage());
    }
}

/**
 * Logout user and clear session data
 */
function logoutUser($redirect_url = '/CAMPUS-POLLS/login.html') {
    global $conn;
    
    // Delete session from database if exists
    if (isset($_SESSION['session_id']) && isset($_SESSION['user_id'])) {
        deleteSession($_SESSION['session_id'], $_SESSION['user_id']);
    }
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page with timeout message
    header("Location: $redirect_url?timeout=1");
    exit();
}

/**
 * Optional: Clean up old sessions from database (run periodically)
 */
function cleanupExpiredSessions() {
    global $conn;
    
    $timeout = SESSION_TIMEOUT;
    
    try {
        // Delete sessions older than timeout period
        $stmt = $conn->prepare("DELETE FROM sessions WHERE TIMESTAMPDIFF(SECOND, last_activity, NOW()) > ?");
        $stmt->bind_param("i", $timeout);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        if ($deleted > 0) {
            error_log("Cleaned up $deleted expired sessions");
        }
    } catch (Exception $e) {
        error_log("Failed to cleanup expired sessions: " . $e->getMessage());
    }
}

// Main session check logic
if (!checkSessionTimeout()) {
    // Session is invalid or expired
    logoutUser();
}

// Optional: Clean up old sessions from database (10% chance on each page load)
if (rand(1, 10) === 1) {
    cleanupExpiredSessions();
}
?>