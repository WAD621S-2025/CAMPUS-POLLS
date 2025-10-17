<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$poll_id = intval($input['poll_id'] ?? 0);
$option_id = intval($input['option_id'] ?? 0);

if (!$poll_id || !$option_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Check if user already voted
$stmt = $conn->prepare("SELECT vote_id FROM poll_votes WHERE poll_id=? AND user_id=?");
$stmt->bind_param("ii", $poll_id, $_SESSION['user_id']);
$stmt->execute();
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Already voted']);
    $stmt->close();
    exit();
}
$stmt->close();

try {
    $conn->begin_transaction();

    // Insert vote
    $stmt = $conn->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id, voted_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iii", $poll_id, $option_id, $_SESSION['user_id']);
    $stmt->execute();
    $vote_id = $stmt->insert_id;
    $stmt->close();

    // Increment vote count in poll_options
    $stmt = $conn->prepare("UPDATE poll_options SET vote_count = vote_count + 1 WHERE option_id=?");
    $stmt->bind_param("i", $option_id);
    $stmt->execute();
    $stmt->close();

    // Optionally, update total_votes in polls table
    $stmt = $conn->prepare("UPDATE polls SET total_votes = total_votes + 1 WHERE poll_id=?");
    $stmt->bind_param("i", $poll_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    // Return updated options for chart
    $optStmt = $conn->prepare("SELECT option_id, option_text, vote_count FROM poll_options WHERE poll_id=?");
    $optStmt->bind_param("i", $poll_id);
    $optStmt->execute();
    $updatedOptions = $optStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $optStmt->close();

    echo json_encode(['success' => true, 'updatedOptions' => $updatedOptions]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
