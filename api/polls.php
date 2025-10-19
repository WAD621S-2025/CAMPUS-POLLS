<?php 
session_start();
require '../includes/database.php';


if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Handle POST request to create a new poll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['api'])) {
    try {
        $question = $_POST['question'] ?? '';
        $options = $_POST['options'] ?? [];
        $category = $_POST['category'] ?? 'social';
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        $poll_type = $_POST['poll_type'] ?? 'single';
        $user_id = $_SESSION['user_id'];
        
        // Validate
        if (empty($question) || count($options) < 2) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid poll data']);
            exit();
        }
        
        // Filter out empty options
        $options = array_filter($options, function($opt) {
            return !empty(trim($opt));
        });
        
        if (count($options) < 2) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'At least 2 options required']);
            exit();
        }
        
        // Insert poll
        $stmt = $conn->prepare("INSERT INTO polls (user_id, question, poll_type, category, is_anonymous, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("isssi", $user_id, $question, $poll_type, $category, $is_anonymous);
        $stmt->execute();
        $poll_id = $conn->insert_id;
        $stmt->close();
        
        // Insert options
        $optStmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text, option_order) VALUES (?, ?, ?)");
        $order = 0;
        foreach ($options as $option_text) {
            $option_text = trim($option_text);
            if (!empty($option_text)) {
                $optStmt->bind_param("isi", $poll_id, $option_text, $order);
                $optStmt->execute();
                $order++;
            }
        }
        $optStmt->close();
        
        // Redirect back to polls page
        header('Location: ../polls.html?success=1');
        exit();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// GET API request - return polls
if (isset($_GET['api']) && $_GET['api'] == '1') {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT poll_id, question, poll_type, category, is_anonymous FROM polls WHERE is_active=1 ORDER BY created_at DESC");
        $stmt->execute();
        $polls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
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

// Default response if no valid action
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit();
?>
