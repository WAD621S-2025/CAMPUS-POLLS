<?php
session_start();

$host = "127.0.0.1";
$port = 3307;  // your custom port
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
        echo "<script>alert('Email and password are required.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo "<script>alert('No account found with that email!'); window.history.back();</script>";
        exit();
    }

    $stmt->bind_result($user_id, $username, $password_hash, $full_name);
    $stmt->fetch();

    if (password_verify($password, $password_hash)) {
        session_regenerate_id(true);
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $full_name;

        header("Location: /CAMPUS-POLLS/index.html");
        exit();
    } else {
        echo "<script>alert('Incorrect password! Please try again.'); window.history.back();</script>";
        exit();
    }
} else {
    echo "Please submit the login form.";
}

$conn->close();
?>
