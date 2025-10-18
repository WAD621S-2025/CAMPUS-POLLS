<?php
session_start();
require '../includes/database.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("
        SELECT m.*, u.username, u.profile_image
        FROM memes m
        LEFT JOIN users u ON m.user_id = u.user_id
        WHERE m.is_active = 1
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $memes = [];
    while ($row = $result->fetch_assoc()) {
        // Load comments for each meme with usernames and profile_images
        $commentStmt = $conn->prepare("
            SELECT mc.comment_id, mc.comment_text, mc.created_at, u.username, u.profile_image
            FROM meme_comments mc
            LEFT JOIN users u ON mc.user_id = u.user_id
            WHERE mc.meme_id = ?
            ORDER BY mc.created_at ASC
        ");
        $commentStmt->bind_param("i", $row['meme_id']);
        $commentStmt->execute();
        $commentsResult = $commentStmt->get_result();

        $comments = [];
        while ($cRow = $commentsResult->fetch_assoc()) {
            $comments[] = [
                'comment_id' => $cRow['comment_id'],
                'username' => $cRow['username'] ?? 'Anonymous',
                'profile_image' => $cRow['profile_image'], // add profile_image here
                'text' => $cRow['comment_text'],
                'created_at' => $cRow['created_at']
            ];
        }
        $commentStmt->close();

        $row['comments'] = $comments;

        // Check if current user liked this meme
        $liked = false;
        if (isset($_SESSION['user_id'])) {
            $likeStmt = $conn->prepare("SELECT like_id FROM meme_likes WHERE meme_id = ? AND user_id = ?");
            $likeStmt->bind_param("ii", $row['meme_id'], $_SESSION['user_id']);
            $likeStmt->execute();
            $likeResult = $likeStmt->get_result();
            $liked = $likeResult->num_rows > 0;
            $likeStmt->close();
        }

        $row['liked'] = $liked;

        // Default username for meme poster if missing
        $row['username'] = $row['username'] ?? 'Anonymous';

        // profile_image from joined users for meme poster (already selected)
        // Make sure to keep as is for frontend use
        // $row['profile_image'] available

        $memes[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'memes' => $memes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
