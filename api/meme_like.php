<?php 
session_start();
// IMPORTANT: Make sure 'database.php' and 'functions.php' are in the 'includes' directory relative to the current file (api/).
require_once '../includes/database.php';
require_once '../includes/functions.php'; // Assuming this file contains createNotification

// Set headers for JSON response
header('Content-Type: application/json');

// --- 1. Basic Validation and Authentication ---

if (!isset($_SESSION['user_id'])) {
    // Standard response for unauthenticated users
    echo json_encode(['success' => false, 'message' => 'Not logged in. Please log in to like content.']);
    exit();
}

// Ensure the connection object is available and valid
if (!isset($conn) || $conn->connect_error) {
    // Check if the connection from database.php failed
    error_log("Database connection failed: " . ($conn->connect_error ?? 'Connection object not set.'));
    echo json_encode(['success' => false, 'message' => 'Server error: Database connection unavailable.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$meme_id = intval($_POST['meme_id'] ?? 0);

if ($meme_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid meme ID provided.']);
    exit();
}

// --- 2. Helper Functions (Optimized for MySQLi) ---

/**
 * Gets the current likes count from the memes table.
 * @param mysqli $conn The database connection object.
 * @param int $meme_id The ID of the meme.
 * @return int The current like count.
 */
function getLikesCount(mysqli $conn, $meme_id) {
    $stmt = $conn->prepare("SELECT likes_count FROM memes WHERE meme_id = ?");
    if (!$stmt) {
        error_log("Failed to prepare getLikesCount statement: " . $conn->error);
        return 0; // Return 0 or handle error appropriately
    }
    $stmt->bind_param("i", $meme_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['likes_count'] ?? 0;
}

// Note: Assuming createNotification function is correctly defined in functions.php 
// and uses the passed $conn object.

// --- 3. Like/Unlike Logic ---

try {
    // Check if user has already liked this meme
    $stmt = $conn->prepare("SELECT like_id FROM meme_likes WHERE meme_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $meme_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $liked = $result->num_rows > 0;
    $stmt->close();

    $newly_liked = false;

    if ($liked) {
        // --- UNLIKE Action (Delete) ---
        $stmt = $conn->prepare("DELETE FROM meme_likes WHERE meme_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $meme_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Decrement like count in 'memes' table
        $stmt = $conn->prepare("UPDATE memes SET likes_count = GREATEST(0, likes_count - 1) WHERE meme_id = ?");
        $stmt->bind_param("i", $meme_id);
        $stmt->execute();
        $stmt->close();
        
        $newly_liked = false; // It is now unliked

    } else {
        // --- LIKE Action (Insert) ---
        $stmt = $conn->prepare("INSERT INTO meme_likes (meme_id, user_id, liked_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $meme_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Increment like count in 'memes' table
        $stmt = $conn->prepare("UPDATE memes SET likes_count = likes_count + 1 WHERE meme_id = ?");
        $stmt->bind_param("i", $meme_id);
        $stmt->execute();
        $stmt->close();

        $newly_liked = true; // It is now liked

        // Fetch meme owner and caption for notification
        $stmt = $conn->prepare("SELECT user_id, caption FROM memes WHERE meme_id = ?");
        $stmt->bind_param("i", $meme_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $meme_data = $result->fetch_assoc();
        $stmt->close();
        
        $meme_owner_id = $meme_data['user_id'] ?? null;
        $caption = $meme_data['caption'] ?? 'a shared meme';

        // Notify meme owner if they are not the liker
        if ($meme_owner_id && $meme_owner_id != $user_id) {
            $content = "Someone liked your meme: " . htmlspecialchars(substr($caption, 0, 50)) . "...";
            $link = "memes.php?meme_id=" . $meme_id; // Added specific link for context
            // Assumes createNotification is available globally or defined in functions.php
            createNotification($conn, $meme_owner_id, $content, $link); 
        }
    }

    // --- 4. Final Response ---
    echo json_encode([
        'success' => true, 
        'liked' => $newly_liked,
        'likes_count' => getLikesCount($conn, $meme_id)
    ]);

} catch (\Exception $e) {
    // Log detailed error and send a generic failure message to the client
    error_log("Meme Like Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An unexpected server error occurred during the like process.'
    ]);
}
// Note: $conn is not explicitly closed here if it was included from database.php, 
// as it might be used by other parts of the script before shutdown.

?>
