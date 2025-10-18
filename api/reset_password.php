<?php
// api/reset_password.php - Process password reset
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /CAMPUS-POLLS/forgot_password.html?error=invalid_request");
    exit();
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($token)) {
    header("Location: /CAMPUS-POLLS/reset_password.html?error=invalid_token");
    exit();
}

if (empty($password) || empty($confirm_password)) {
    header("Location: /CAMPUS-POLLS/reset_password.html?token=" . urlencode($token) . "&error=missing_fields");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: /CAMPUS-POLLS/reset_password.html?token=" . urlencode($token) . "&error=passwords_mismatch");
    exit();
}

if (strlen($password) < 8) {
    header("Location: /CAMPUS-POLLS/reset_password.html?token=" . urlencode($token) . "&error=weak_password");
    exit();
}

try {
    // Hash the token to match database
    $token_hash = hash('sha256', $token);
    
    // Find valid reset token
    $stmt = $conn->prepare("SELECT pr.user_id, pr.expires_at, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.user_id WHERE pr.token_hash = ? AND pr.expires_at > NOW()");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Token not found or expired
        header("Location: /CAMPUS-POLLS/reset_password.html?token=" . urlencode($token) . "&error=expired_token");
        exit();
    }
    
    $reset_data = $result->fetch_assoc();
    $user_id = $reset_data['user_id'];
    $stmt->close();
    
    // Hash new password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update password");
    }
    $stmt->close();
    
    // Delete used reset token
    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token_hash = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $stmt->close();
    
    // Delete all active sessions for this user (force re-login)
    $stmt = $conn->prepare("DELETE FROM sessions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Log the password change
    error_log("Password reset successful for user ID: " . $user_id);
    
    // Redirect to login with success message
    header("Location: /CAMPUS-POLLS/login.html?reset=success");
    exit();
    
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    header("Location: /CAMPUS-POLLS/reset_password.html?token=" . urlencode($token) . "&error=server_error");
    exit();}