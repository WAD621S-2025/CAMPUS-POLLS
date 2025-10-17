<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (isset($_GET['api']) && $_GET['api'] == '1') {
    try {
        $result = $conn->query("SELECT * FROM memes WHERE is_active=1 ORDER BY created_at DESC");
        $memes = [];
        while ($row = $result->fetch_assoc()) {
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
    exit();
}

// Normal HTML page render or redirect if needed
echo json_encode(['success' => false, 'message' => 'API parameter missing']);
