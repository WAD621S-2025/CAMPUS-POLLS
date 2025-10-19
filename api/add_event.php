<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

// Ensure database connection is valid
if (!isset($conn) || $conn->connect_error) {
    header("Location: /events.html?error=database");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /events.html?error=invalid_request");
    exit();
}

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$is_public = isset($_POST['is_public']) ? 1 : 0;

// Basic validation
if (!$title || !$category || !$description || !$event_date) {
    header("Location: /events.html?error=missing_fields");
    exit();
}

// Validate date format
$date = DateTime::createFromFormat('Y-m-d\TH:i', $event_date);
if (!$date) {
    header("Location: /events.html?error=invalid_date");
    exit();
}

// Insert event into events table
$stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, is_public, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    error_log("Prepare statement failed: " . $conn->error);
    header("Location: /events.html?error=database");
    exit();
}

$stmt->bind_param("ssssi", $title, $category, $description, $event_date, $is_public);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    // Success: redirect with success message
    header("Location: /events.html?success=1");
    exit();
} else {
    error_log("Failed to insert event: " . $stmt->error);
    $stmt->close();
    $conn->close();
    header("Location: /events.html?error=insert_failed");
    exit();
}
?>