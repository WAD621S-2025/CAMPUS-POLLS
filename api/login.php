<?php
// Start the session
session_start();

// Database connection settings
$host = 'localhost';
$dbname = 'buzz';
$username = 'root';
$password = ''; // Update if your MySQL has a password

// Connect to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Check if form data is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        // Bind results from query
        $stmt->bind_result($user_id, $username, $email, $hashed_password, $full_name);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            // Store session data
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $full_name;

            // Update last_login for the user
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect to homepage or dashboard
            header("Location: index.html");
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
