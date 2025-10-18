<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// --- Direct mysqli Connection ---
$host = "127.0.0.1";
$port = 3307; // Double-check this port! (Usually 3306 or 3307)
$username = "root";
$password = "";
$dbname = "buzz";
// Connect using the standard 5-argument format
$conn = new mysqli($host, $username, $password, $dbname, $port); 

if ($conn->connect_error) { 
    // If this fails, the issue is your XAMPP/WAMP MySQL server is not running or the port/credentials are wrong.
    die("Database Connection Failed: " . $conn->connect_error); 
}

$user_id = $_SESSION['user_id'];

// 1. Fetch User Data (mysqli style)
$stmt = $conn->prepare("SELECT username, full_name, profile_image, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username_db, $full_name_db, $profile_image_db, $bio_db);
$stmt->fetch();
$stmt->close();

// Set variables, using placeholders if data is missing
$username = $username_db ?? '';
$full_name = $full_name_db ?? '';
// IMPORTANT: This line uses the path saved from edit_profile.php, or a default image.
$profile_image = $profile_image_db ?: 'https://placehold.co/100x100/fbbf24/1f2937?text=P';
$bio = $bio_db ?? 'No bio set yet. Click "Edit Profile" to add one!';


// 2. getCount function (mysqli style)
function getCount($conn, $table, $user_id) {
    // Only allow known tables to prevent SQL injection
    $allowed_tables = ['memes', 'poll_votes', 'comments'];
    if (!in_array($table, $allowed_tables)) {
        return 0;
    }

    $count = 0;
    // Use backticks for the table name
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table` WHERE user_id = ?"); 
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


// 3. Fetch Recent Activity (mysqli style)

// Helper function to fetch data rows for recent activity
function fetchRecentActivity($conn, $sql, $user_id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set
    $data = $result->fetch_all(MYSQLI_ASSOC); // Fetch all rows as associative array
    $stmt->close();
    return $data;
}

$recentVotesSql = "
    SELECT p.question, pv.voted_at 
    FROM poll_votes pv 
    JOIN polls p ON pv.poll_id = p.poll_id 
    WHERE pv.user_id = ? 
    ORDER BY pv.voted_at DESC 
    LIMIT 5
";
$recentVotes = fetchRecentActivity($conn, $recentVotesSql, $user_id);

$recentMemesSql = "
    SELECT caption, created_at 
    FROM memes 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recentMemes = fetchRecentActivity($conn, $recentMemesSql, $user_id);

$recentCommentsSql = "
    SELECT comment_text, created_at 
    FROM comments 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recentComments = fetchRecentActivity($conn, $recentCommentsSql, $user_id);

// Close the main connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($username) ?>'s Profile - BUZZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
</head>

<body class="bg-amber-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 flex flex-col min-h-screen relative transition-colors duration-300 font-inter">
    <div class="honeycomb-bg"></div>

    <header class="bg-gradient-to-r from-yellow-400 to-amber-500 dark:from-amber-600 dark:to-yellow-700 shadow-lg sticky top-0 z-50 transition-colors duration-300">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="index.html" class="flex items-center">
                    <i class="fas fa-university text-2xl text-gray-800 dark:text-gray-100 mr-2"></i>
                    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">🐝 BUZZ</h1>
                </a>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.html" class="text-gray-800 dark:text-gray-100 font-semibold px-3 py-2 rounded-md text-sm bg-white dark:bg-gray-800 bg-opacity-30">Home</a>
                    <a href="polls.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Polls</a>
                    <a href="events.html" class="text-gray-800 hover:bg-white hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Events</a>
                    <a href="memes.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Memes</a>
                    <a href="about.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">About Us</a>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="theme-toggle" class="theme-toggle p-2 rounded-md bg-white dark:bg-gray-800 bg-opacity-30 hover:bg-opacity-50 transition" title="Toggle Dark Mode">
                        <i class="fas fa-moon dark:hidden text-gray-800"></i>
                        <i class="fas fa-sun hidden dark:inline text-yellow-300"></i>
                    </button>
                    <a href="user_profile.php" class="p-0.5 border-2 border-gray-800 dark:border-amber-400 rounded-full bg-white dark:bg-gray-800">
                        <img class="h-8 w-8 rounded-full object-cover" src="<?= htmlspecialchars($profile_image) ?>" alt="User Avatar">
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow container mx-auto p-4 sm:p-6 lg:p-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <section class="lg:col-span-1 bg-white dark:bg-gray-800 p-8 rounded-xl shadow-xl border-t-4 border-amber-500 dark:border-yellow-500 transition-colors duration-300 text-center">
                <div class="mb-6">
                    <img class="w-32 h-32 rounded-full object-cover mx-auto border-4 border-amber-400 dark:border-yellow-400 shadow-md" 
                            src="<?= htmlspecialchars($profile_image) ?>" 
                            alt="<?= htmlspecialchars($full_name) ?>'s Avatar">
                </div>
                
                <h2 class="text-3xl font-extrabold mb-1 text-gray-900 dark:text-gray-50"><?= htmlspecialchars($full_name) ?></h2>
                <p class="text-lg mb-4 text-amber-600 dark:text-amber-400">@<?= htmlspecialchars($username) ?></p>
                
                <p class="max-w-md mx-auto text-sm italic text-gray-600 dark:text-gray-300 mb-6 border-t pt-4 mt-4 border-amber-100 dark:border-gray-700">
                    <?= nl2br(htmlspecialchars($bio)) ?>
                </p>

                <a href="edit_profile.php" class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 transition duration-150 shadow-md">
                    <i class="fas fa-user-edit mr-2"></i> Edit Profile
                </a>
            </section>

            <div class="lg:col-span-2 space-y-8">
                
                <section class="grid grid-cols-3 gap-4 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl transition-colors duration-300 border border-amber-200 dark:border-gray-700">
                    
                    <div class="flex flex-col items-center justify-center p-4 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-700">
                        <i class="fas fa-feather-alt text-amber-600 dark:text-amber-400 text-2xl mb-1"></i>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= $posts_count ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Memes Posted</div>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center p-4 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-700">
                        <i class="fas fa-vote-yea text-yellow-600 dark:text-yellow-400 text-2xl mb-1"></i>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= $votes_count ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Votes Cast</div>
                    </div>
                    
                    <div class="flex flex-col items-center justify-center p-4 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-700">
                        <i class="fas fa-comment-dots text-orange-600 dark:text-orange-400 text-2xl mb-1"></i>
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100"><?= $comments_count ?></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Comments</div>
                    </div>

                </section>

                <section class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-xl transition-colors duration-300 border border-amber-200 dark:border-gray-700">
                    <h3 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3 border-amber-300 dark:border-gray-700">
                        <i class="fas fa-fire-alt mr-2 text-red-500"></i> Recent Activity
                    </h3>

                    <?php if(empty($recentVotes) && empty($recentMemes) && empty($recentComments)): ?>
                        <p class="text-gray-600 dark:text-gray-400 italic text-center py-4">No recent activity to show. Get buzzing!</p>
                    <?php else: ?>

                        <div class="grid md:grid-cols-3 gap-6">

                            <div>
                                <h4 class="text-lg font-semibold mb-3 text-amber-600 dark:text-amber-400"><i class="fas fa-poll-h mr-1"></i> Votes</h4>
                                <ul class="space-y-3 text-gray-700 dark:text-gray-300 text-sm">
                                    <?php foreach ($recentVotes as $vote): ?>
                                        <li class="p-3 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-600 hover:shadow-md transition duration-150 cursor-pointer">
                                            <p class="font-medium truncate">Voted on: <?= htmlspecialchars($vote['question']) ?></p>
                                            <small class="text-gray-500 dark:text-gray-400"><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($vote['voted_at'])) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentVotes)): ?>
                                        <li class="italic text-gray-500">No recent votes.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div>
                                <h4 class="text-lg font-semibold mb-3 text-yellow-600 dark:text-yellow-400"><i class="fas fa-image mr-1"></i> Memes</h4>
                                <ul class="space-y-3 text-gray-700 dark:text-gray-300 text-sm">
                                    <?php foreach ($recentMemes as $meme): ?>
                                        <li class="p-3 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-600 hover:shadow-md transition duration-150 cursor-pointer">
                                            <p class="font-medium truncate">Caption: <?= htmlspecialchars($meme['caption']) ?></p>
                                            <small class="text-gray-500 dark:text-gray-400"><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($meme['created_at'])) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentMemes)): ?>
                                        <li class="italic text-gray-500">No recent memes.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                            <div>
                                <h4 class="text-lg font-semibold mb-3 text-orange-600 dark:text-orange-400"><i class="fas fa-comment mr-1"></i> Comments</h4>
                                <ul class="space-y-3 text-gray-700 dark:text-gray-300 text-sm">
                                    <?php foreach ($recentComments as $comment): ?>
                                        <li class="p-3 bg-amber-50 dark:bg-gray-700 rounded-lg shadow-sm border border-amber-200 dark:border-gray-600 hover:shadow-md transition duration-150 cursor-pointer">
                                            <p class="font-medium line-clamp-2"><?= htmlspecialchars($comment['comment_text']) ?></p>
                                            <small class="text-gray-500 dark:text-gray-400"><i class="far fa-clock mr-1"></i> <?= date('M d, Y', strtotime($comment['created_at'])) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($recentComments)): ?>
                                        <li class="italic text-gray-500">No recent comments.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>

                        </div>

                    <?php endif; ?>
                </section> 

            </div>
        </div>
    </main>

    <footer class="bg-gradient-to-r from-yellow-400 to-amber-500 dark:from-amber-600 dark:to-yellow-700 shadow-inner mt-auto relative z-10 transition-colors duration-300">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-gray-800 dark:text-gray-100">
            <p>&copy; 2025 BUZZ. All Rights Reserved.</p>
            <div class="flex justify-center space-x-4 mt-2">
                <a href="#" class="hover:text-white transition" title="Facebook"><i class="fab fa-facebook"></i></a>
                <a href="#" class="hover:text-white transition" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="hover:text-white transition" title="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

<style>
     /* Standard/Compatible Definition for Line Clamping (2 lines) */
     .line-clamp-2 {
           overflow: hidden;
           display: -webkit-box;
           -webkit-box-orient: vertical;
           
           /* Add the standard property for future support and to satisfy linters */
           line-clamp: 2; 
           
           /* The non-standard but required property for current cross-browser support (Chrome, Safari, Edge) */
           -webkit-line-clamp: 2; 
       }
</style>

    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        // Apply saved theme or system preference
        if (currentTheme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            html.classList.add('dark');
        }

        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
        });
    </script>
</body>
</html>