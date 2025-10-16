<?php
 // test_connection.php
 require_once 'includes/database.php';
 echo "<h2>Testing Database Connection...</h2>";
 $database = new Database();
 $db = $database->connect();
 if($db) {
 echo "<p style='color: green; font-size: 20px;'>
 ✅
 Database connection successful!</p>";
 // Test if tables exist
 $stmt = $db->query("SHOW TABLES");
 $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
 echo "<h3>Tables found:</h3>";
 echo "<ul>";
 foreach($tables as $table) {
 echo "<li>$table</li>";
 }
 echo "</ul>";
 // Test a stored procedure
 try {
 $stmt = $db->prepare("CALL get_trending_memes(5)");
 $stmt->execute();
 echo "<p style='color: green;'>
 ✅
 Stored procedures working!</p>";
 } catch(Exception $e) {
 echo "<p style='color: orange;'>
 ⚠
 Stored procedures test: " . $e->getMessage() . "</p>";
}
 } else {
 echo "<p style='color: red; font-size: 20px;'>
 ❌
 Database connection failed!</p>";
 echo "<p>Check your database settings in includes/database.php</p>";
 }
 ?>