<?php
// =========================================
// Database Configuration
// =========================================
$host = "localhost";   // Database host (default: localhost)
$user = "root";        // Database username
$pass = "";            // Database password
$db   = "mydata";      // Database name


// =========================================
// MySQLi (Object-Oriented) Connection
// =========================================
$conn = new mysqli($host, $user, $pass, $db);

// Check MySQLi connection
if ($conn->connect_error) {
    die("DB Connection failed (MySQLi): " . $conn->connect_error);
}


// =========================================
// PDO (PHP Data Objects) Connection - Recommended
// =========================================
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    
    // Create PDO instance
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exceptions
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Disable emulation for better security
    ]);

    // Uncomment to test connection
    // echo "PDO connection successful!";

} catch (PDOException $e) {
    die("DB Connection failed (PDO): " . $e->getMessage());
}
?>
