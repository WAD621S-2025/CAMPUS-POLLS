<?php
session_start();
require '../includes/database.php';

header('Content-Type: application/json');

try {
    $result = $conn->query("SELECT * FROM memes WHERE is_active=1 ORDER BY created_at DESC");
    $memes = [];
    while ($row = $result->fetch_assoc()) {
        // Load comments for each meme
        $commentsRes = $conn->query("SELECT username, comment_text FROM meme_comments JOIN users ON meme_comments.user_id=users.user_id WHERE meme_id={$row['meme_id']} ORDER BY created_at");
        $comments = [];
        while ($cRow = $commentsRes->fetch_assoc()) {
            $comments[] = ['username' => $cRow['username'], 'text' => $cRow['comment_text']];
        }
        $row['comments'] = $comments;
        $memes[] = $row;
    }
    echo json_encode(['success' => true, 'memes' => $memes]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
