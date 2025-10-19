<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

header('Content-Type: application/json');

// Ensure database connection is valid
if (!isset($conn) || $conn->connect_error) {
    error_log("Database connection failed: " . ($conn->connect_error ?? 'Connection object not set.'));
    echo json_encode(['success' => false, 'message' => 'Server error: Database connection unavailable.']);
    exit();
}

try {
    // Get category filter from query parameter
    $category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
    
    // Build SQL query
    if ($category === 'all') {
        $sql = "SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC";
        $stmt = $conn->prepare($sql);
    } else {
        $sql = "SELECT * FROM events WHERE event_date >= NOW() AND category = ? ORDER BY event_date ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        $stmt->bind_param("s", $category);
    }
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'events' => $events]);

} catch (Exception $e) {
    error_log("Get Events Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>