<?php
// api/check_session.php - Lightweight session check for JavaScript
session_start();
header('Content-Type: application/json');

$response = [
    'loggedIn' => false,
    'user' => null
];

// Check if user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $response['loggedIn'] = true;
    $response['user'] = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? 'User',
        'full_name' => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'profile_picture' => $_SESSION['profile_picture'] ?? 'https://placehold.co/100x100/fbbf24/1f2937?text=' . strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1))
    ];
}

echo json_encode($response);
exit();
?>