<?php
session_start();
require 'db.php';
require 'functions.php';


header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$meme_id = intval($_POST['meme_id'] ?? 0);
if (!$meme_id) {
    echo json_encode(['success' => false]);
    exit();
}

// Helper function to create notification
function createNotification($conn, $user_id, $content, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $link);
    $stmt->execute();
    $stmt->close();
}

// Check if user liked this meme
$result = $conn->query("SELECT like_id FROM meme_likes WHERE meme_id=$meme_id AND user_id={$_SESSION['user_id']}");
if ($result->num_rows > 0) {
    // User liked it, so remove like
    $conn->query("DELETE FROM meme_likes WHERE meme_id=$meme_id AND user_id={$_SESSION['user_id']}");
    // Decrement like count
    $conn->query("UPDATE memes SET likes_count = likes_count - 1 WHERE meme_id=$meme_id");
    echo json_encode(['success' => true, 'likes_count' => max(0, (int) getLikesCount($conn, $meme_id))]);
} else {
    // Add like
    $conn->query("INSERT INTO meme_likes (meme_id, user_id, liked_at) VALUES ($meme_id, {$_SESSION['user_id']}, NOW())");
    $conn->query("UPDATE memes SET likes_count = likes_count + 1 WHERE meme_id=$meme_id");

    // Fetch meme owner to notify
    $stmt = $conn->prepare("SELECT user_id, caption FROM memes WHERE meme_id = ?");
    $stmt->bind_param("i", $meme_id);
    $stmt->execute();
    $stmt->bind_result($meme_owner_id, $caption);
    $stmt->fetch();
    $stmt->close();

    // Notify meme owner if they are not the liker
    if ($meme_owner_id != $_SESSION['user_id']) {
        $content = "Someone liked your meme: " . htmlspecialchars($caption);
        $link = "memes.php"; // adjust link if needed
        createNotification($conn, $meme_owner_id, $content, $link);
    }

    echo json_encode(['success' => true, 'likes_count' => getLikesCount($conn, $meme_id)]);
}

function getLikesCount($conn, $meme_id) {
    $result = $conn->query("SELECT likes_count FROM memes WHERE meme_id=$meme_id");
    $row = $result->fetch_assoc();
    return $row['likes_count'] ?? 0;
}
