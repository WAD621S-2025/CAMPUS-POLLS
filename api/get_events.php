<?php
// api/get_events.php - Returns events as JSON
header('Content-Type: application/json');
require '../includes/database.php';

try {
    // Get filter parameter if provided
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';
    
    // Build query based on filter
    if ($category === 'all') {
        $sql = "SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT * FROM events WHERE event_date >= NOW() AND category = ? ORDER BY event_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $category);
    }
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch events: ' . $e->getMessage()
    ]);
}
?>