<?php
session_start();
require '../includes/database.php';

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

function createNotification($conn, $user_id, $content, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $link);
    $stmt->execute();
    $stmt->close();
}

// Get username and profile_image for response
$stmt = $conn->prepare("SELECT username, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$username = $userData['username'];
$profile_image = $userData['profile_image'];
$stmt->close();

// Insert comment
$stmt = $conn->prepare("INSERT INTO meme_comments (meme_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $meme_id, $_SESSION['user_id'], $comment_text);
$success = $stmt->execute();
$comment_id = $conn->insert_id;
$stmt->close();

if ($success) {
    // Notify meme owner if commenter is not owner
    $stmt2 = $conn->prepare("SELECT user_id, caption FROM memes WHERE meme_id = ?");
    $stmt2->bind_param("i", $meme_id);
    $stmt2->execute();
    $stmt2->bind_result($meme_owner_id, $caption);
    $stmt2->fetch();
    $stmt2->close();

    if ($meme_owner_id != $_SESSION['user_id']) {
        $content = "Someone commented on your meme: " . htmlspecialchars($caption);
        $link = "memes.php"; // Or more specific link if available
        createNotification($conn, $meme_owner_id, $content, $link);
    }

    echo json_encode([
        'success' => true,
        'username' => $username,
        'profile_image' => $profile_image,
        'comment_id' => $comment_id,
        'time' => 'Just now'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
}
?>
