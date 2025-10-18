<?php
session_start();
require '../includes/database.php';
require '../includes/functions.php';

$content = "New event '{$title}' has been posted.";
$link = "events.html";
createNotification($conn, 1, $content, $link);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Forbid non-POST requests
    http_response_code(405);
    echo "Method Not Allowed";
    exit();
}

$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$event_date = trim($_POST['event_date'] ?? '');
$is_public = isset($_POST['is_public']) ? 1 : 0;

// Basic validation
if (!$title || !$category || !$description || !$event_date) {
    // Could redirect back with error or just exit with message
    echo "Please fill in all required fields.";
    exit();
}

// Insert event into your events table
$stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, is_public, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    echo "Prepare statement failed: " . $conn->error;
    exit();
}
$stmt->bind_param("ssssi", $title, $category, $description, $event_date, $is_public);

if ($stmt->execute()) {
    $stmt->close();
    // Success: redirect or show success message
    header("Location: /CAMPUS-POLLS/events.html?success=1");
    exit();
} else {
    echo "Failed to insert event: " . $stmt->error;
    $stmt->close();
    exit();
}
?>
