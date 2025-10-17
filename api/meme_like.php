<?php
session_start();
require 'db.php';

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
    echo json_encode(['success' => true, 'likes_count' => getLikesCount($conn, $meme_id)]);
}

function getLikesCount($conn, $meme_id) {
    $result = $conn->query("SELECT likes_count FROM memes WHERE meme_id=$meme_id");
    $row = $result->fetch_assoc();
    return $row['likes_count'] ?? 0;
}
