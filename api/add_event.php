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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: http://127.0.0.1/CAMPUS-POLLS/events.html?error=invalid_request");
    exit();
}

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$is_public = isset($_POST['is_public']) ? 1 : 0;

// Basic validation
if (!$title || !$category || !$description || !$event_date) {
    header("Location: http://127.0.0.1/CAMPUS-POLLS/events.html?error=missing_fields");
    exit();
}

// Insert event into events table
$stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, is_public, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    error_log("Prepare statement failed: " . $conn->error);
    header("Location: http://127.0.0.1/CAMPUS-POLLS/events.html?error=database");
    exit();
}

$stmt->bind_param("ssssi", $title, $category, $description, $event_date, $is_public);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    // Success: redirect with success message
    header("Location: http://127.0.0.1/CAMPUS-POLLS/events.html?success=1");
    exit();
} else {
    error_log("Failed to insert event: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: http://127.0.0.1/CAMPUS-POLLS/events.html?error=insert_failed");
    exit();
}
?>
