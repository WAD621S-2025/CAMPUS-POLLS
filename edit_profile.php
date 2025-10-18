<?php
session_start();
// Enable error display for immediate debugging. REMOVE THIS ONCE LIVE.
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// --- 1. Database Configuration (Direct mysqli Connection) ---
$host = "127.0.0.1";
$port = 3307; // Verify this port is correct (often 3306)
$username = "root";
$password = "";
$dbname = "buzz";
$conn = new mysqli($host, $username, $password, $dbname, $port); 

if ($conn->connect_error) { 
    die("Database Connection Failed: " . $conn->connect_error); 
}

$user_id = $_SESSION['user_id'];
$status_message = ''; 

// --- Helper Functions and Setup ---

// Directory where images will be stored (relative to this PHP file's location)
$upload_dir = 'uploads/profile_pics/'; 
if (!is_dir($upload_dir)) {
    // Attempt to create the directory if it doesn't exist
    if (!mkdir($upload_dir, 0777, true)) {
         // This is a critical failure point. Log and set message.
         error_log("Failed to create upload directory: " . $upload_dir);
         $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Critical Error: Upload folder could not be created or accessed. Check folder permissions (should be 777).</div>';
    }
}

function log_activity($conn, $user_id, $activity_type, $description, $reference_id = null) {
    if ($conn->connect_error) return false;
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, activity_type, description, reference_id) VALUES (?, ?, ?, ?)");
    $ref_id_str = $reference_id === null ? null : (string)$reference_id;
    $stmt->bind_param("isss", $user_id, $activity_type, $description, $ref_id_str); 
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

// --- 2. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    
    $full_name = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $image_path_for_db = null; // Will store the new path if an image is uploaded

    // --- Image Upload Handling ---
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        
        $file = $_FILES['profile_image'];
        $max_size = 2 * 1024 * 1024; // 2MB
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if ($file['size'] > $max_size) {
            $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Error: Image file is too large (max 2MB).</div>';
        } elseif (!in_array($file['type'], $allowed_types)) {
            $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Error: Invalid file type. Only JPG, PNG, GIF are allowed.</div>';
        } else {
            // Generate a unique filename using the user ID and a unique ID
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $user_id . '_' . uniqid() . '.' . $ext;
            $destination_full_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $destination_full_path)) {
                // SUCCESS: Store the relative path to be saved in the database
                $image_path_for_db = $destination_full_path; 
                error_log("Profile image successfully moved to: " . $image_path_for_db);
            } else {
                // FAILURE: File move failed (likely permissions issue)
                $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Error: Failed to move uploaded file. Check the permissions of the `uploads/profile_pics/` folder.</div>';
                error_log("Failed to move uploaded file to: " . $destination_full_path);
            }
        }
    } else if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other file upload errors (e.g., UPLOAD_ERR_INI_SIZE)
        $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>File Upload Failed. PHP Error Code: ' . $_FILES['profile_image']['error'] . '</div>';
    }
    // --- End Image Upload Handling ---


    // Only proceed with DB update if no critical errors occurred and there's data to save
    if (empty($full_name) && !$image_path_for_db && empty($status_message)) {
        // Only show error if they haven't filled anything and haven't selected an image
        if (empty($full_name)) {
            $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Full Name cannot be empty.</div>';
        }
    } else if (empty($status_message)) {
        
        $conn->begin_transaction();
        
        try {
            // Build the dynamic SQL query and parameters
            $update_fields = ["full_name = ?", "bio = ?"];
            $bind_types = "ss";
            $bind_params = [$full_name, $bio];
            
            // If a new image path exists, add it to the query
            if ($image_path_for_db) {
                $update_fields[] = "profile_image = ?";
                $bind_types .= "s";
                $bind_params[] = $image_path_for_db;
            }
            
            $update_query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE user_id = ?";
            $bind_types .= "i";
            $bind_params[] = $user_id; // The user_id is the last parameter

            // 1. Update the user's data
            $stmt = $conn->prepare($update_query);

            if ($stmt === false) {
                 throw new Exception("SQL Prepare Failed: " . $conn->error);
            }

            // --- FIX for bind_param reference warning START ---
            $references = [];
            
            // Add the type string as the first element
            $references[] = $bind_types; 
            
            // Convert the rest of the parameters to references
            foreach ($bind_params as $key => $value) {
                // The '&' operator here explicitly creates a reference
                $references[] = &$bind_params[$key]; 
            }
            
            // Execute the bind_param method using the array of references
            if (!call_user_func_array([$stmt, 'bind_param'], $references)) {
                 throw new Exception("Bind Param Failed: " . $stmt->error);
            }
            // --- FIX for bind_param reference warning END ---
            
            $update_success = $stmt->execute();
            $stmt->close();
            
            // 2. Log the activity
            $description = "User updated their Profile (Name, Bio, " . ($image_path_for_db ? "and Image)" : "only)");
            $log_success = log_activity($conn, $user_id, 'Profile Updated', $description, null);

            if ($update_success && $log_success) {
                $conn->commit();
                $status_message = '<div class="p-3 bg-green-100 text-green-700 rounded-lg"><i class="fas fa-check-circle mr-2"></i>Profile updated successfully! Redirecting in 3 seconds...</div>';
                header('Refresh: 3; URL=user_profile.php'); 
            } else {
                $conn->rollback(); 
                $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>Error: Profile update failed. DB Error: ' . $conn->error . '</div>';
                error_log("DB update failed for user $user_id. Error: " . $conn->error);
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Profile Update Exception: " . $e->getMessage()); 
            $status_message = '<div class="p-3 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-times-circle mr-2"></i>An unexpected database error occurred. Please try again.</div>';
        }
    }
}

// --- 3. Fetch Current User Data (For initial load) ---
$stmt = $conn->prepare("SELECT username, full_name, profile_image, bio FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username_db, $full_name_db, $profile_image_db, $bio_db);
$stmt->fetch();
$stmt->close();

// Close the connection before the HTML output
$conn->close();

// Set variables for HTML
$username = htmlspecialchars($username_db ?? '');
$full_name = htmlspecialchars($_POST['full_name'] ?? $full_name_db ?? ''); 
// Use the DB path or the placeholder URL
$profile_image_path = htmlspecialchars($profile_image_db ?? 'https://placehold.co/100x100/fbbf24/1f2937?text=P');
$bio = htmlspecialchars($_POST['bio'] ?? $bio_db ?? ''); 
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
                    <a href="index.html" class="text-gray-800 dark:text-gray-100 hover:bg-white dark:hover:bg-gray-800 hover:bg-opacity-20 px-3 py-2 rounded-md text-sm font-medium transition">Home</a>
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
                        <img class="h-8 w-8 rounded-full object-cover" src="<?= $profile_image_path ?>" alt="User Avatar">
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="flex-grow container mx-auto p-4 sm:p-6 lg:p-8 relative z-10">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 p-8 rounded-xl shadow-2xl border-t-4 border-amber-500 dark:border-yellow-500 transition-colors duration-300">
            
            <h2 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-50 text-center">
                <i class="fas fa-cog mr-2 text-amber-500"></i> Edit Your Profile
            </h2>
            
            <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                
                <div id="status-message" class="mb-4 text-center">
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
                        <input type="text" name="username" id="username" required
                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="your_username"
                               value="<?= $username ?>" readonly> 
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
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Max size 2MB. Only JPG, PNG, GIF allowed.</p>
                    </div>
                    
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="user_profile.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
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

    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        const currentTheme = localStorage.getItem('theme') || 'light';
        
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