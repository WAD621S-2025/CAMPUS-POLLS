<?php
session_start();
require '../includes/database.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$caption = trim($_POST['caption'] ?? '');
if (empty($caption)) {
    echo json_encode(['success' => false, 'message' => 'Caption is required']);
    exit();
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image file found']);
    exit();
}

$fileError = $_FILES['image']['error'];
if ($fileError !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive from the form.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
    ];
    $message = $errorMessages[$fileError] ?? 'Unknown upload error.';
    echo json_encode(['success' => false, 'message' => "Upload error: $message"]);
    exit();
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images allowed']);
    exit();
}

$uploadDir = realpath(__DIR__ . '/../uploads/memes/');
if ($uploadDir === false) {
    $baseDir = __DIR__ . '/../uploads/memes/';
    if (!mkdir($baseDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit();
    }
    $uploadDir = realpath($baseDir);
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'message' => 'Upload directory is not writable']);
    exit();
}

if (!file_exists($file['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Temporary uploaded file not found']);
    exit();
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = uniqid('meme_', true) . '.' . $extension;
$uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $imageUrl = 'uploads/memes/' . $filename;

    $stmt = $conn->prepare("INSERT INTO memes (user_id, image_url, caption, likes_count, comments_count, is_active, created_at) VALUES (?, ?, ?, 0, 0, 1, NOW())");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $conn->error]);
        exit();
    }

    $stmt->bind_param("iss", $_SESSION['user_id'], $imageUrl, $caption);

    if ($stmt->execute()) {
        $meme_id = $conn->insert_id;
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Meme uploaded successfully',
            'meme_id' => $meme_id,
            'image_url' => $imageUrl
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database execute error: ' . $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}
?>
