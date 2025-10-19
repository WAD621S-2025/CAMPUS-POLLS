<?php
session_start();
require '../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Handle POST request to create a new event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['api'])) {
    try {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $event_date = $_POST['event_date'] ?? '';
        $category = $_POST['category'] ?? 'social';
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        
        // Validate
        if (empty($title) || empty($event_date)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Title and date are required']);
            exit();
        }
        
        // Insert event
        $stmt = $conn->prepare("INSERT INTO events (title, category, description, event_date, is_public) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $category, $description, $event_date, $is_public);
        $stmt->execute();
        $event_id = $conn->insert_id;
        $stmt->close();
        
        // Redirect back to events page
        header('Location: ../events.html?success=1');
        exit();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// GET API request - return events
if (isset($_GET['api']) && $_GET['api'] == '1') {
    header('Content-Type: application/json');
    try {
        $stmt = $conn->prepare("SELECT event_id, title, category, description, event_date, is_public, created_at FROM events ORDER BY event_date DESC");
        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['success' => true, 'events' => $events]);
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