<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }

// Utility function to get count from a table
function getCount($conn, $table) {
    $count = 0;  // Initialize before bind_result
    $stmt = $conn->prepare("SELECT COUNT(*) FROM $table");
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}


$active_users = getCount($conn, 'users');
$memes_shared = getCount($conn, 'memes');
$active_polls = getCount($conn, 'polls');
$total_votes = getCount($conn, 'poll_votes');

// Fetch recent activity (latest 3 votes, memes, comments)
$recent_votes = $conn->query("SELECT p.question, u.username, pv.voted_at FROM poll_votes pv JOIN polls p ON pv.poll_id = p.poll_id JOIN users u ON pv.user_id = u.user_id ORDER BY pv.voted_at DESC LIMIT 3");
$recent_memes = $conn->query("SELECT caption, user_id, created_at FROM memes ORDER BY created_at DESC LIMIT 3");
$recent_comments = $conn->query("SELECT comment_text, user_id, created_at FROM comments ORDER BY created_at DESC LIMIT 3");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Campus Polls - BUZZ</title>
<link href="https://cdn.tailwindcss.com" rel="stylesheet" />
</head>
<body class="bg-amber-50 text-gray-800 flex flex-col min-h-screen relative">

<!-- Your header/nav HTML here -->

<main class="container mx-auto p-8">
    <section class="stats grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-center">
        <div><div class="text-3xl font-bold text-amber-600"><?= $active_users ?></div><div class="text-sm">Active Users</div></div>
        <div><div class="text-3xl font-bold text-yellow-600"><?= $memes_shared ?></div><div class="text-sm">Memes Shared</div></div>
        <div><div class="text-3xl font-bold text-amber-700"><?= $active_polls ?></div><div class="text-sm">Active Polls</div></div>
        <div><div class="text-3xl font-bold text-yellow-700"><?= $total_votes ?></div><div class="text-sm">Total Votes</div></div>
    </section>

    <section>
        <h3 class="text-xl font-bold mb-4">🔥 Recent Activity</h3>
        <div class="space-y-4">

            <?php while ($vote = $recent_votes->fetch_assoc()): ?>
                <div class="p-4 bg-amber-50 rounded shadow">
                    <strong><?= htmlspecialchars($vote['username']) ?></strong> voted on "<?= htmlspecialchars($vote['question']) ?>" <br />
                    <small><?= $vote['voted_at'] ?></small>
                </div>
            <?php endwhile; ?>

            <?php while ($meme = $recent_memes->fetch_assoc()): ?>
                <div class="p-4 bg-yellow-50 rounded shadow">
                    Meme: <?= htmlspecialchars($meme['caption']) ?> <br />
                    <small>Posted at <?= $meme['created_at'] ?></small>
                </div>
            <?php endwhile; ?>

            <?php while ($comment = $recent_comments->fetch_assoc()): ?>
                <div class="p-4 bg-amber-100 rounded shadow">
                    Comment: <?= htmlspecialchars($comment['comment_text']) ?> <br />
                    <small>Posted at <?= $comment['created_at'] ?></small>
                </div>
            <?php endwhile; ?>

        </div>
    </section>
</main>

<!-- Your footer here -->

</body>
</html>
