<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

require 'db.php'; // Make sure to use your actual DB connection file

$user_id = $_SESSION['user_id'];

// Fetch user's core profile data
$stmt = $conn->prepare("SELECT username, full_name, profile_image, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $full_name, $profile_image, $bio);
$stmt->fetch();
$stmt->close();

// Function to get counts of posts, votes, comments
function getCount($conn, $table, $user_id) {
    $count = 0;  // Initialize before binding result to please static analyzers
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}


$posts_count = getCount($conn, 'memes', $user_id);
$votes_count = getCount($conn, 'poll_votes', $user_id);
$comments_count = getCount($conn, 'comments', $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Your existing head content -->
</head>
<body class="bg-amber-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 flex flex-col min-h-screen relative">
    <div class="honeycomb-bg"></div>

    <!-- Your existing header -->

    <main class="flex-grow container mx-auto p-4 sm:p-6 lg:p-8 relative z-10">
        <div class="bg-white dark:bg-gray-800 border-2 border-amber-300 dark:border-amber-600 p-6 rounded-lg shadow-lg">
            <div class="flex flex-col md:flex-row items-center">
                <img class="h-32 w-32 rounded-full object-cover border-4 border-amber-500 dark:border-amber-400 shadow-lg"
                     src="<?= htmlspecialchars($profile_image ?: 'https://placehold.co/200x200/fbbf24/1f2937?text=Student') ?>"
                     alt="User Avatar">
                <div class="mt-4 md:mt-0 md:ml-6 text-center md:text-left">
                    <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100"><?= htmlspecialchars($full_name) ?></h2>
                    <p class="text-amber-700 dark:text-amber-400 font-semibold">@<?= htmlspecialchars($username) ?></p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"><?= nl2br(htmlspecialchars($bio)) ?></p>
                    <div class="flex justify-center md:justify-start gap-2 mt-4">
                        <span class="bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 px-3 py-1 rounded-full text-xs font-semibold">🐝 Active Buzzer</span>
                        <span class="bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 px-3 py-1 rounded-full text-xs font-semibold">⭐ Top Voter</span>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t-2 border-amber-200 dark:border-gray-700">
                <div class="text-center">
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400"><?= $posts_count ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Posts</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"><?= $votes_count ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Votes Cast</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-amber-700 dark:text-amber-400"><?= $comments_count ?></div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Comments</div>
                </div>
            </div>

            <!-- Recent Activity (optional dynamic data could be added similarly) -->

        </div>
    </main>

    <!-- Your existing footer -->

    <script src="script.js"></script>
</body>
</html>
