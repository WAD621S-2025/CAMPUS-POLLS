<?php 
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// --- Database Configuration (Copied from profile.php) ---
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname, port: $port);

if ($conn->connect_error) { 
    die("DB connection failed: " . $conn->connect_error); 
}

$user_id = $_SESSION['user_id'];
$status_message = ''; // Message to display to the user (success/error)

// --- Helper Function to Log Activity (Using mysqli) ---
function log_activity($conn, $user_id, $activity_type) {
    // NOTE: Assumes activity_log table has columns: user_id, activity_type, created_at
    try {
        $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity_type) VALUES (?, ?)");
        if ($stmt === false) {
             throw new Exception("Activity log prepare failed: " . $conn->error);
        }
        $stmt->bind_param("is", $user_id, $activity_type);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

// --- 1. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    // Sanitize input
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Basic validation
    if (empty($full_name)) {
        $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Full Name cannot be empty.</div>';
    } else {
        
        // Start transaction (mysqli requires specific commands)
        $conn->begin_transaction();
        
        try {
            // 1. Update the user's data in the USERS table
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, bio = ? WHERE user_id = ?");
            if ($stmt === false) {
                 throw new Exception("User update prepare failed: " . $conn->error);
            }
            $stmt->bind_param("ssi", $full_name, $bio, $user_id);
            $update_success = $stmt->execute();
            $stmt->close();
            
            // 2. Log the activity to the ACTIVITY_LOG table
            $log_success = log_activity($conn, $user_id, 'Profile Updated');

            if ($update_success && $log_success) {
                $conn->commit();
                $status_message = '<div class="p-3 bg-green-100 text-green-700 rounded-lg"><i class="fas fa-check-circle mr-2"></i>Profile updated successfully! Redirecting in 3 seconds...</div>';
                
                // Redirect back to profile.php after a short delay
                header('Refresh: 3; URL=profile.php');
                
            } else {
                $conn->rollback(); 
                $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Error: Profile update failed.</div>';
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Profile Update Exception: " . $e->getMessage()); 
            $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>An unexpected database error occurred. Please try again.</div>';
        }
    }
}

// --- 2. Fetch Current User Data (For initial load and after error/success) ---

$stmt = $conn->prepare("SELECT username, full_name, profile_image, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username_db, $full_name_db, $profile_image_db, $bio_db);
$stmt->fetch();
$stmt->close();

// Use submitted values if available (after failed POST), otherwise use DB values
$username = htmlspecialchars($username_db ?? '');
$full_name = htmlspecialchars($_POST['full_name'] ?? $full_name_db ?? '');
$profile_image_path = $profile_image ? "uploads/profile_pics/$profile_image" : "https://placehold.co/100x100/fbbf24/1f2937?text=P";$bio = htmlspecialchars($_POST['bio'] ?? $bio_db ?? '');

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile - BUZZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="style.css" />
    <style>
        /* Optional: Add custom styles here if needed */
    </style>
</head>

<body class="bg-amber-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 flex flex-col min-h-screen relative transition-colors duration-300 font-inter">
    <div class="honeycomb-bg"></div>

    <header class="bg-gradient-to-r from-yellow-400 to-amber-500 shadow-lg sticky top-0 z-50">
        <nav class="container mx-auto px-4 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-gray-800">🐝 BUZZ Edit Profile</h1>
            <a href="profile.php" class="text-gray-800 hover:text-white transition">Back to Profile</a>
        </nav>
    </header>

    <main class="flex-grow container mx-auto p-4 sm:p-6 lg:p-8 relative z-10">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-2xl border-t-4 border-amber-500 dark:border-yellow-500 transition-colors duration-300">
            
            <h2 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-50 text-center">
                <i class="fas fa-cog mr-2 text-amber-500"></i> Edit Your Profile
            </h2>
            
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                
                <div id="status-message" class="mb-6">
                    <?= $status_message ?>
                </div>
                
                <div class="space-y-6">
                    
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required
                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="Your full name"
                               value="<?= $full_name ?>">
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                        <input type="text" name="username" id="username" readonly
                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                               value="<?= $username ?>">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Username changes are currently disabled.</p>
                    </div>

                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                        <textarea name="bio" id="bio" rows="4"
                                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100"
                                  placeholder="Tell us a little about yourself..."><?= $bio ?></textarea>
                    </div>
                    
                    <div class="border-t border-amber-200 dark:border-gray-700 pt-6">
                        <label for="profile_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Update Profile Picture</label>
                        <input type="file" name="profile_image" id="profile_image" accept="image/*"
                               class="block w-full text-sm text-gray-500 dark:text-gray-400
                               file:mr-4 file:py-2 file:px-4
                               file:rounded-full file:border-0
                               file:text-sm file:font-semibold
                               file:bg-amber-50 file:text-amber-700
                               hover:file:bg-amber-100 dark:file:bg-amber-900 dark:file:text-amber-300 dark:hover:file:bg-amber-800">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max size 2MB. Only JPG, PNG, GIF allowed. (Upload logic is omitted).</p>
                    </div>
                    
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="profile.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                        Cancel
                    </a>
                    <button type="submit" name="update_profile" 
                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 dark:bg-yellow-500 dark:hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="bg-gradient-to-r from-yellow-400 to-amber-500 shadow-inner mt-auto">
        <div class="container mx-auto px-4 py-6 text-center text-gray-800">
            <p>&copy; 2025 BUZZ. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>
