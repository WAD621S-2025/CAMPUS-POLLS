<?php
$host = '127.0.0.1';
$port = 3307;
$db_name = 'buzz';
$username = 'root';
$password = '';

// Create MySQLi connection
$conn = new mysqli($host, $username, $password, $db_name, $port);

// Check connection
if ($conn->connect_error) {
    // 1. Set the JSON header (in case it wasn't set yet)
    header('Content-Type: application/json');
    
    // 2. Output a structured JSON error
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed. Check credentials/port. Error: " . $conn->connect_error
    ]);
    
    // 3. Stop script execution cleanly
    exit(); 
}

// Set charset
$conn->set_charset("utf8mb4");
?>