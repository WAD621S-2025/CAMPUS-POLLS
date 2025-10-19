<?php
session_start();
require '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = $_POST['action'] ?? '';
$event_id = $_POST['event_id'] ?? 0;
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'rsvp':
            // Check if event exists and is active
            $checkStmt = $conn->prepare("SELECT max_attendees, is_active FROM events WHERE event_id = ?");
            $checkStmt->bind_param("i", $event_id);
            $checkStmt->execute();
            $event = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            
            if (!$event || !$event['is_active']) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit();
            }
            
            // Check if event is full
            if ($event['max_attendees'] !== null) {
                $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM event_attendees WHERE event_id = ? AND status = 'going'");
                $countStmt->bind_param("i", $event_id);
                $countStmt->execute();
                $count = $countStmt->get_result()->fetch_assoc();
                $countStmt->close();
                
                if ($count['count'] >= $event['max_attendees']) {
                    echo json_encode(['success' => false, 'message' => 'Event is full']);
                    exit();
                }
            }
            
            // Check if already registered
            $existStmt = $conn->prepare("SELECT attendee_id FROM event_attendees WHERE event_id = ? AND user_id = ?");
            $existStmt->bind_param("ii", $event_id, $user_id);
            $existStmt->execute();
            $existing = $existStmt->get_result()->fetch_assoc();
            $existStmt->close();
            
            if ($existing) {
                // Update status to 'going'
                $updateStmt = $conn->prepare("UPDATE event_attendees SET status = 'going' WHERE event_id = ? AND user_id = ?");
                $updateStmt->bind_param("ii", $event_id, $user_id);
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Insert new attendance
                $insertStmt = $conn->prepare("INSERT INTO event_attendees (event_id, user_id, status) VALUES (?, ?, 'going')");
                $insertStmt->bind_param("ii", $event_id, $user_id);
                $insertStmt->execute();
                $insertStmt->close();
            }
            
            echo json_encode(['success' => true, 'message' => 'RSVP confirmed']);
            break;
            
        case 'cancel_rsvp':
            // Cancel attendance (delete or update to 'not_going')
            $deleteStmt = $conn->prepare("DELETE FROM event_attendees WHERE event_id = ? AND user_id = ?");
            $deleteStmt->bind_param("ii", $event_id, $user_id);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            echo json_encode(['success' => true, 'message' => 'RSVP cancelled']);
            break;
            
        case 'delete_event':
            // Check if user is the creator
            $ownerStmt = $conn->prepare("SELECT user_id FROM events WHERE event_id = ?");
            $ownerStmt->bind_param("i", $event_id);
            $ownerStmt->execute();
            $event = $ownerStmt->get_result()->fetch_assoc();
            $ownerStmt->close();
            
            if (!$event || $event['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Not authorized']);
                exit();
            }
            
            // Soft delete (set is_active = 0)
            $deleteStmt = $conn->prepare("UPDATE events SET is_active = 0 WHERE event_id = ?");
            $deleteStmt->bind_param("i", $event_id);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Event deleted']);
            break;
            
        case 'update_event':
            // Check if user is the creator
            $ownerStmt = $conn->prepare("SELECT user_id FROM events WHERE event_id = ?");
            $ownerStmt->bind_param("i", $event_id);
            $ownerStmt->execute();
            $event = $ownerStmt->get_result()->fetch_assoc();
            $ownerStmt->close();
            
            if (!$event || $event['user_id'] != $user_id) {
                echo json_encode(['success' => false, 'message' => 'Not authorized']);
                exit();
            }
            
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $event_date = $_POST['event_date'] ?? '';
            $event_time = $_POST['event_time'] ?? '';
            $location = $_POST['location'] ?? '';
            
            if (empty($title) || empty($event_date) || empty($event_time)) {
                echo json_encode(['success' => false, 'message' => 'Title, date, and time are required']);
                exit();
            }
            
            $event_datetime = $event_date . ' ' . $event_time;
            
            $updateStmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_datetime = ?, location = ? WHERE event_id = ?");
            $updateStmt->bind_param("ssssi", $title, $description, $event_datetime, $location, $event_id);
            $updateStmt->execute();
            $updateStmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Event updated']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>