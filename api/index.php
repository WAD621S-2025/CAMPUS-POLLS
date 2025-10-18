<?php
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname, port: $port);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }

// Utility function to get count from a table
function getCount($conn, $table) {
    $count = 0;  // Initialize before bind_result
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}


$active_users = getCount($conn, 'users');
$memes_shared = getCount($conn, 'memes');
$active_polls = getCount($conn, 'polls');
$total_votes = getCount($conn, 'poll_votes');

// Fetch recent activity (latest 3 votes, memes, comments)
$recent_votes = $conn->query("SELECT p.question, u.username, pv.voted_at FROM poll_votes pv JOIN polls p ON pv.poll_id = p.poll_id JOIN users u ON pv.user_id = u.user_id ORDER BY pv.voted_at DESC LIMIT 3");
$recent_memes = $conn->query("SELECT caption, user_id, created_at FROM memes ORDER BY created_at DESC LIMIT 3");
$recent_comments = $conn->query("SELECT comment_text, user_id, created_at FROM comments ORDER BY created_at DESC LIMIT 3");

$conn->close();
?>