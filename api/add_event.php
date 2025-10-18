<?php
session_start();
require '../includes/database.php';
require '../includes/functions.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

// Get and sanitize form data
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$is_public = isset($_POST['is_public']) ? 1 : 0;

// Basic validation
if (!$title || !$category || !$description || !$event_date) {
    header("Location: /CAMPUS-POLLS/events.html?error=missing_fields");
    exit();
}

// Validate datetime format (optional but recommended)
$datetime = DateTime::createFromFormat('Y-m-d\TH:i', $event_date);
if (!$datetime) {
    header("Location: /CAMPUS-POLLS/events.html?error=invalid_date");
    exit();
}

// Insert event into events table
$stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, is_public, created_at) VALUES (?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    error_log("Prepare statement failed: " . $conn->error);
    header("Location: /CAMPUS-POLLS/events.html?error=database");
    exit();
}

$stmt->bind_param("ssssi", $title, $category, $description, $event_date, $is_public);

if ($stmt->execute()) {
    $event_id = $stmt->insert_id; // Get the ID of the newly created event
    $stmt->close();
    
    // Create notification AFTER successful insertion
    $content = "New event '{$title}' has been posted.";
    $link = "events.html";
    createNotification($conn, 1, $content, $link);
    
    // Success: redirect with success message
    header("Location: /CAMPUS-POLLS/events.php?success=1");
    exit();
} else {
    error_log("Failed to insert event: " . $stmt->error);
    $stmt->close();
    header("Location: /CAMPUS-POLLS/events.html?error=insert_failed");
    exit();
}
?>