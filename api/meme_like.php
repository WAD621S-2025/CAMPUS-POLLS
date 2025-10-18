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
if (!$meme_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid meme ID']);
    exit();
}

// Helper function to create notification
function createNotification($conn, $user_id, $content, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $link);
    $stmt->execute();
    $stmt->close();
}

function getLikesCount($conn, $meme_id) {
    $stmt = $conn->prepare("SELECT likes_count FROM memes WHERE meme_id = ?");
    $stmt->bind_param("i", $meme_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['likes_count'] ?? 0;
}

// Check if user liked this meme
$stmt = $conn->prepare("SELECT like_id FROM meme_likes WHERE meme_id = ? AND user_id = ?");
$stmt->bind_param("ii", $meme_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$liked = $result->num_rows > 0;
$stmt->close();

if ($liked) {
    // User liked it, so remove like
    $stmt = $conn->prepare("DELETE FROM meme_likes WHERE meme_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $meme_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Decrement like count
    $stmt = $conn->prepare("UPDATE memes SET likes_count = likes_count - 1 WHERE meme_id = ?");
    $stmt->bind_param("i", $meme_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true, 
        'liked' => false,
        'likes_count' => max(0, getLikesCount($conn, $meme_id))
    ]);
} else {
    // Add like
    $stmt = $conn->prepare("INSERT INTO meme_likes (meme_id, user_id, liked_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $meme_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE memes SET likes_count = likes_count + 1 WHERE meme_id = ?");
    $stmt->bind_param("i", $meme_id);
    $stmt->execute();
    $stmt->close();

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
        $link = "memes.php";
        createNotification($conn, $meme_owner_id, $content, $link);
    }

    echo json_encode([
        'success' => true, 
        'liked' => true,
        'likes_count' => getLikesCount($conn, $meme_id)
    ]);
}
?>