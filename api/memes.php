<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
$host = 'localhost';
$dbname = 'buzz';
$username = 'root';
$password = '';
$port = 3307;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));

// GET all memes
if ($method === 'GET' && empty($request[0])) {
    try {
        $stmt = $pdo->query("
            SELECT 
                meme_id as id,
                user_id,
                title,
                image_url as image,
                caption,
                category,
                upvotes as likes,
                downvotes,
                total_score,
                view_count,
                is_flagged,
                is_approved,
                created_at,
                updated_at
            FROM memes 
            WHERE is_approved = 1 
            ORDER BY created_at DESC
        ");
        
        $memes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add empty comments array and liked status
        foreach ($memes as &$meme) {
            $meme['comments'] = [];
            $meme['liked'] = false;
        }
        
        echo json_encode($memes);
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// POST new meme (upload)
elseif ($method === 'POST' && empty($request[0])) {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['memeImage']) || $_FILES['memeImage']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No image uploaded']);
            exit;
        }
        
        $file = $_FILES['memeImage'];
        $caption = $_POST['caption'] ?? '';
        $user_id = $_POST['user_id'] ?? 1;
        $title = $_POST['title'] ?? 'Untitled';
        $category = $_POST['category'] ?? 'general';
        
        // Validate file type
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $file['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['error' => 'Invalid file type']);
            exit;
        }
        
        // Generate unique filename
        $newFilename = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $uploadPath = '../uploads/' . $newFilename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode(['error' => 'Failed to move uploaded file']);
            exit;
        }
        
        $imageUrl = '/uploads/' . $newFilename;
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO memes 
            (user_id, title, image_url, caption, category, upvotes, downvotes, total_score, view_count, is_flagged, is_approved, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, 0, 1, NOW(), NOW())
        ");
        
        $stmt->execute([$user_id, $title, $imageUrl, $caption, $category]);
        
        echo json_encode([
            'success' => true,
            'meme_id' => $pdo->lastInsertId(),
            'message' => 'Meme uploaded successfully'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// POST like/unlike meme
elseif ($method === 'POST' && isset($request[0]) && $request[1] === 'like') {
    try {
        $memeId = $request[0];
        $data = json_decode(file_get_contents('php://input'), true);
        $liked = $data['liked'] ?? false;
        
        if ($liked) {
            $stmt = $pdo->prepare("UPDATE memes SET upvotes = upvotes + 1, total_score = total_score + 1, updated_at = NOW() WHERE meme_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE memes SET upvotes = upvotes - 1, total_score = total_score - 1, updated_at = NOW() WHERE meme_id = ?");
        }
        
        $stmt->execute([$memeId]);
        echo json_encode(['success' => true]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// GET comments for a meme
elseif ($method === 'GET' && isset($request[0]) && $request[1] === 'comments') {
    try {
        $memeId = $request[0];
        $stmt = $pdo->prepare("
            SELECT comment_id, user_id, comment_text as text, created_at
            FROM comments 
            WHERE meme_id = ? 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$memeId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($comments);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// POST new comment
elseif ($method === 'POST' && isset($request[0]) && $request[1] === 'comments') {
    try {
        $memeId = $request[0];
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $data['user_id'] ?? 1;
        $text = $data['text'] ?? '';
        
        if (empty($text)) {
            echo json_encode(['error' => 'Comment text is required']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO comments (meme_id, user_id, comment_text, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->execute([$memeId, $user_id, $text]);
        
        echo json_encode([
            'success' => true,
            'comment_id' => $pdo->lastInsertId()
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

else {
    echo json_encode(['error' => 'Invalid request']);
}
?>