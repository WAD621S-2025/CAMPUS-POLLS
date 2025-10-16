<?php
 // includes/config.php
 // Database Configuration (XAMPP Defaults)
 define('DB_HOST', 'localhost');
 define('DB_NAME', 'buzz');
 define('DB_USER', 'root');
 define('DB_PASS', '');  // Empty for XAMPP
 // Application Settings
 define('APP_NAME', 'ProjectWAD');
 define('APP_URL', 'http://localhost/ProjectWAD');
 define('UPLOAD_PATH', __DIR__ . '/../uploads/');
 define('MAX_FILE_SIZE', 5242880); // 5MB
 // Session Configuration
 if (session_status() === PHP_SESSION_NONE) {
 session_start();
 }
 // Security
 define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
 define('PASSWORD_MIN_LENGTH', 8);
 // Pagination
 define('DEFAULT_PAGE_SIZE', 20);
 define('MAX_PAGE_SIZE', 100);
 ?>
