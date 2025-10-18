<?php
// test_session.php - Test page to verify session timeout works
require 'includes/session_check.php';

// If we reach here, user is logged in and session is valid
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Test - BUZZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-6 text-gray-900">
                <i class="fas fa-shield-alt text-green-500"></i> Session Timeout Test
            </h1>
            
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                <p class="font-bold text-green-700">✓ Session is Active!</p>
                <p class="text-green-600">If you can see this page, your session is valid.</p>
            </div>

            <div class="bg-blue-50 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-blue-900">Session Information</h2>
                <table class="w-full text-left">
                    <tr class="border-b">
                        <td class="py-2 font-semibold">User ID:</td>
                        <td class="py-2"><?php echo htmlspecialchars($_SESSION['user_id']); ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-semibold">Username:</td>
                        <td class="py-2"><?php echo htmlspecialchars($_SESSION['username']); ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-semibold">Full Name:</td>
                        <td class="py-2"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-semibold">Session ID:</td>
                        <td class="py-2 font-mono text-sm"><?php echo htmlspecialchars(substr($_SESSION['session_id'], 0, 20)); ?>...</td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-semibold">Login Time:</td>
                        <td class="py-2"><?php echo date('Y-m-d H:i:s', $_SESSION['login_time']); ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-semibold">Session Timeout:</td>
                        <td class="py-2"><?php echo SESSION_TIMEOUT; ?> seconds (<?php echo SESSION_TIMEOUT/60; ?> minutes)</td>
                    </tr>
                    <tr>
                        <td class="py-2 font-semibold">Current Time:</td>
                        <td class="py-2" id="current-time"><?php echo date('Y-m-d H:i:s'); ?></td>
                    </tr>
                </table>
            </div>

            <?php
            // Get session data from database
            $session_id = $_SESSION['session_id'];
            $user_id = $_SESSION['user_id'];
            
            $stmt = $conn->prepare("SELECT last_activity, created_at, ip_address, user_agent FROM sessions WHERE session_id = ? AND user_id = ?");
            $stmt->bind_param("si", $session_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $session_data = $result->fetch_assoc();
                $last_activity = strtotime($session_data['last_activity']);
                $time_remaining = SESSION_TIMEOUT - (time() - $last_activity);
                ?>
                <div class="bg-yellow-50 rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4 text-yellow-900">Database Session Info</h2>
                    <table class="w-full text-left">
                        <tr class="border-b">
                            <td class="py-2 font-semibold">Last Activity:</td>
                            <td class="py-2"><?php echo $session_data['last_activity']; ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-semibold">Session Created:</td>
                            <td class="py-2"><?php echo $session_data['created_at']; ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-semibold">IP Address:</td>
                            <td class="py-2"><?php echo htmlspecialchars($session_data['ip_address']); ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-semibold">User Agent:</td>
                            <td class="py-2 text-sm"><?php echo htmlspecialchars(substr($session_data['user_agent'], 0, 50)); ?>...</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-semibold">Time Until Timeout:</td>
                            <td class="py-2">
                                <span class="font-bold text-lg" id="time-remaining">
                                    <?php echo gmdate('i:s', $time_remaining); ?>
                                </span>
                                <span class="text-sm text-gray-600">(mm:ss)</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php
                $stmt->close();
            }
            ?>

            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-900">
                    <i class="fas fa-flask"></i> Testing Instructions
                </h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Note the "Time Until Timeout" above</li>
                    <li>Wait for the timeout period to pass (<?php echo SESSION_TIMEOUT/60; ?> minutes)</li>
                    <li>Refresh this page or click the button below</li>
                    <li>You should be redirected to login with "session expired" message</li>
                    <li>For quick testing, change SESSION_TIMEOUT to 60 seconds in session_check.php</li>
                </ol>
            </div>

            <div class="flex gap-4">
                <button onclick="location.reload()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i> Refresh Page (Updates Activity)
                </button>
                
                <a href="/CAMPUS-POLLS/api/logout.php" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition inline-block">
                    <i class="fas fa-sign-out-alt mr-2"></i> Manual Logout
                </a>
                
                <a href="/CAMPUS-POLLS/index.html" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition inline-block">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
            </div>

            <div class="mt-6 p-4 bg-amber-50 border-l-4 border-amber-500">
                <p class="text-amber-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> Every time you load or refresh this page, your "last_activity" updates, 
                    resetting the timeout countdown. To test timeout, don't interact with the page for <?php echo SESSION_TIMEOUT/60; ?> minutes.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Update current time every second
        setInterval(() => {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toISOString().slice(0, 19).replace('T', ' ');
        }, 1000);

        // Countdown timer
        let timeRemaining = <?php echo $time_remaining ?? 0; ?>;
        setInterval(() => {
            if (timeRemaining > 0) {
                timeRemaining--;
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                document.getElementById('time-remaining').textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                
                // Change color when less than 2 minutes remaining
                if (timeRemaining < 120) {
                    document.getElementById('time-remaining').className = 'font-bold text-lg text-red-600';
                }
            } else {
                document.getElementById('time-remaining').textContent = 'EXPIRED';
                document.getElementById('time-remaining').className = 'font-bold text-lg text-red-600';
            }
        }, 1000);
    </script>
</body>
</html>