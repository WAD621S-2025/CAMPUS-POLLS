<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$meme_id = intval($_POST['meme_id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');

if (!$meme_id || !$comment_text) {
    echo json_encode(['success' => false]);
    exit();
}

// Get username for response
$resultUser = $conn->query("SELECT username FROM users WHERE user_id={$_SESSION['user_id']}");
$username = $resultUser->fetch_assoc()['username'];

$resultInsert = $conn->query("INSERT INTO meme_comments (meme_id, user_id, comment_text, created_at) VALUES ($meme_id, {$_SESSION['user_id']}, '$comment_text', NOW())");
if ($resultInsert) {
    echo json_encode(['success' => true, 'username' => $username]);
} else {
    echo json_encode(['success' => false]);
}
