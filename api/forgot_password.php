<?php
// api/forgot_password.php - Process password reset request
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
    $conn->close();
    header("Location: /CAMPUS-POLLS/forgot_password.html?error=invalid_request");
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));

// Validation
if (empty($email)) {
    $conn->close();
    header("Location: /CAMPUS-POLLS/forgot_password.html?error=missing_email");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $conn->close();
    header("Location: /CAMPUS-POLLS/forgot_password.html?error=invalid_email");
    exit();
}

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, username, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // User not found; for security, pretend success
        $stmt->close();
        $conn->close();
        header("Location: /CAMPUS-POLLS/forgot_password.html?success=1");
        exit();
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Generate reset token
    $reset_token = bin2hex(random_bytes(32)); // 64 char token
    $reset_token_hash = hash('sha256', $reset_token);
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store reset token in database with upsert logic
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token_hash = ?, expires_at = ?, created_at = NOW()");
    $stmt->bind_param("issss", $user['user_id'], $reset_token_hash, $expires_at, $reset_token_hash, $expires_at);

    if (!$stmt->execute()) {
        $stmt->close();
        $conn->close();
        throw new Exception("Failed to create reset token");
    }
    $stmt->close();

    $reset_link = "http://localhost/CAMPUS-POLLS/reset_password.html?token=" . $reset_token;

    $to = $email;
    $subject = "Password Reset Request - BUZZ Campus";
    $message = "..."; // (your HTML email template here)
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: BUZZ Campus <noreply@buzzcampus.com>\r\n";

    if (!mail($to, $subject, $message, $headers)) {
        error_log("Failed to send password reset email to: " . $email);
        error_log("Reset link: " . $reset_link);
    } else {
        error_log("Password reset email sent to: " . $email);
    }

    $conn->close();

    // Redirect with success for security (don't reveal if email exists)
    header("Location: /CAMPUS-POLLS/forgot_password.html?success=1");
    exit();

} catch (Exception $e) {
    error_log("Forgot password error: " . $e->getMessage());
    $conn->close();
    header("Location: /CAMPUS-POLLS/forgot_password.html?error=server_error");
    exit();
}
?>
