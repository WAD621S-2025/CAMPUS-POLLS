<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (isset($_GET['api']) && $_GET['api'] == '1') {
    // API mode: return JSON data of active polls and options
    try {
        $stmt = $conn->prepare("SELECT poll_id, question, poll_type, category, is_anonymous FROM polls WHERE is_active=1 ORDER BY created_at DESC");
        $stmt->execute();
        $polls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $response = [];

        foreach ($polls as $poll) {
            $optStmt = $conn->prepare("SELECT option_id, option_text, vote_count FROM poll_options WHERE poll_id=? ORDER BY option_order");
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
    exit();
}

// Else: Render your normal HTML page here (or redirect to polls.html)
echo json_encode(['success' => false, 'message' => 'API parameter missing']);
