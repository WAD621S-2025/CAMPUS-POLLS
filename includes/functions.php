<?php
function createNotification($conn, $user_id, $content, $link = null) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, content, link) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $content, $link);
    $stmt->execute();
    $stmt->close();
}
