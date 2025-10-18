<?php
header('Content-Type: application/json');

$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";

$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
    exit();
}

$sql = "SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC";
$result = $conn->query($sql);

$events = [];
while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}

$conn->close();

echo json_encode([
    'success' => true,
    'events' => $events,
    'count' => count($events)
]);
?>