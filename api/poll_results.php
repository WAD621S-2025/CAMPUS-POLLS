<?php 
session_start();
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname, port: $port);
if ($conn->connect_error) { die("DB connection failed: " . $conn->connect_error); }


// Get poll_id from query string
$poll_id = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;

if ($poll_id <= 0) {
    die("Invalid poll ID.");
}

// Fetch poll question
$stmtQuestion = $conn->prepare("SELECT question FROM polls WHERE poll_id = ?");
$stmtQuestion->bind_param("i", $poll_id);
$stmtQuestion->execute();
$stmtQuestion->bind_result($question);
if (!$stmtQuestion->fetch()) {
    die("Poll not found.");
}
$stmtQuestion->close();

// Fetch options and vote counts
$stmtOptions = $conn->prepare("SELECT option_text, vote_count FROM poll_options WHERE poll_id = ? ORDER BY option_order");
$stmtOptions->bind_param("i", $poll_id);
$stmtOptions->execute();
$result = $stmtOptions->get_result();

$options = [];
$votes = [];
while ($row = $result->fetch_assoc()) {
    $options[] = $row['option_text'];
    $votes[] = (int)$row['vote_count'];
}
$stmtOptions->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Poll Results - BUZZ</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.tailwindcss.com" rel="stylesheet">
</head>
<body class="bg-amber-50 text-gray-800 flex flex-col items-center p-8 min-h-screen">
<h1 class="text-3xl font-bold mb-6"><?php echo htmlspecialchars($question); ?></h1>

<canvas id="resultsChart" width="400" height="400"></canvas>

<script>
const ctx = document.getElementById('resultsChart').getContext('2d');
const resultsChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($options); ?>,
        datasets: [{
            data: <?php echo json_encode($votes); ?>,
            backgroundColor: ['#fbbf24', '#f59e0b', '#d97706', '#b45309', '#92400e', '#78350f'],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

</body>
</html>
