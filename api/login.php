<?php
session_start();

// Database connection settings
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";
$$conn = new mysqli($host, $username, $password, $dbname, $port);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }


// Helper function to create session record
function createSession($conn, $session_id, $user_id, $user_agent, $ip_address) {
    $stmt = $conn->prepare("INSERT INTO sessions (session_id, user_id, user_agent, ip_address, last_activity)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_activity=NOW()");
    $stmt->bind_param("siss", $session_id, $user_id, $user_agent, $ip_address);
    $stmt->execute();
    $stmt->close();
}

// Helper function to log activity
function logActivity($conn, $user_id, $activity_type, $reference_id = null, $description = null) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity_type, reference_id, description)
        VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $activity_type, $reference_id, $description);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $email = strtolower(trim($_POST['email']));
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $username, $email, $hashed_password, $full_name);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);

            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;

            // Create session entry in DB
            createSession(
                $conn, 
                session_id(), 
                $user_id, 
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );

            // Log login activity
            logActivity($conn, $user_id, 'login', null, 'User logged in');

            // Update last_login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            header("Location: index.php");
            exit();
        } else {
            echo "<script>alert('Incorrect password! Please try again.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No account found with that email!'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>

