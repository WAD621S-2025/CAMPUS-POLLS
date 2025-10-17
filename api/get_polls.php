<?php
session_start();
require 'db.php'; // your DB connection setup

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

try {
    // Fetch active polls
    $stmt = $conn->prepare("SELECT poll_id, question, poll_type, category, is_anonymous, showResults FROM polls WHERE is_active=1");
    $stmt->execute();
    $polls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $response = [];

    foreach($polls as $poll) {
        // Fetch options for each poll
        $optStmt = $conn->prepare("SELECT option_id, option_text, vote_count FROM poll_options WHERE poll_id=?");
        $optStmt->bind_param("i", $poll['poll_id']);
        $optStmt->execute();
        $options = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $optStmt->close();

        $poll['options'] = $options;
        $response[] = $poll;
    }
    echo json_encode(['success' => true, 'polls' => $response]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
