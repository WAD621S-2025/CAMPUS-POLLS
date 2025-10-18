<?php
session_start();
require '../includes/database.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$meme_id = intval($_POST['meme_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');

if (!$meme_id || !$comment_text) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Helper function to create notification
function createNotification($conn, $user_id, $content, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $link);
    $stmt->execute();
    $stmt->close();
}

// Get username for response
$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$username = $result->fetch_assoc()['username'];
$stmt->close();

// Insert comment safely with prepared statement
$stmt = $conn->prepare("INSERT INTO meme_comments (meme_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $meme_id, $_SESSION['user_id'], $comment_text);
$success = $stmt->execute();
$comment_id = $conn->insert_id;
$stmt->close();

if ($success) {
    // Fetch meme owner
    $stmt2 = $conn->prepare("SELECT user_id, caption FROM memes WHERE meme_id = ?");
    $stmt2->bind_param("i", $meme_id);
    $stmt2->execute();
    $stmt2->bind_result($meme_owner_id, $caption);
    $stmt2->fetch();
    $stmt2->close();

    // Notify owner except if commenter is owner
    if ($meme_owner_id != $_SESSION['user_id']) {
        $content = "Someone commented on your meme: " . htmlspecialchars($caption);
        $link = "memes.php";
        createNotification($conn, $meme_owner_id, $content, $link);
    }

    echo json_encode([
        'success' => true, 
        'username' => $username,
        'comment_id' => $comment_id,
        'time' => 'Just now'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
}
?>