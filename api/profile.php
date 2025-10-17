<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT username, full_name, profile_image, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $full_name, $profile_image, $bio);
$stmt->fetch();
$stmt->close();

// Fetch stats: posts, votes, comments
function getCount($conn, $table, $where_field = null, $where_value = null) {
    $count = 0;  // Initialize count variable here
    if ($where_field !== null && $where_value !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE $where_field = ?");
        $stmt->bind_param("i", $where_value);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
    }
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}


$posts_count = getCount($conn, 'memes', $user_id);
$votes_count = getCount($conn, 'poll_votes', $user_id);
$comments_count = getCount($conn, 'comments', $user_id);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Profile - BUZZ</title>
<link href="https://cdn.tailwindcss.com" rel="stylesheet" />
</head>
<body class="bg-amber-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 flex flex-col min-h-screen relative">

<!-- Your existing header/nav HTML here -->

<main class="container mx-auto p-8">
    <div>
        <img src="<?= htmlspecialchars($profile_image ?: 'https://placehold.co/200x200/fbbf24/1f2937?text=Student') ?>" alt="Profile Image" class="rounded-full w-32 h-32 border-4 border-amber-500" />
        <h2 class="text-3xl font-bold"><?= htmlspecialchars($full_name) ?></h2>
        <p class="text-amber-700 font-semibold">@<?= htmlspecialchars($username) ?></p>
        <p class="mt-2"><?= nl2br(htmlspecialchars($bio)) ?></p>

        <div class="stats mt-6 grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-2xl font-bold text-amber-600"><?= $posts_count ?></div>
                <div class="text-sm">Posts</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-yellow-600"><?= $votes_count ?></div>
                <div class="text-sm">Votes Cast</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-amber-700"><?= $comments_count ?></div>
                <div class="text-sm">Comments</div>
            </div>
        </div>
    </div>
</main>


</body>
</html>
