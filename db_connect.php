<?php
// db_connect.php

// Database configuration
$host = 'localhost';         // Hostname of your database server
$db   = 'bingo_game';             // Your database name
$user = 'root';  // Your database username
$pass = '';  // Your database password
$charset = 'utf8mb4';        // Character set

// Data Source Name (DSN)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options for better error handling and performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    // Create a new PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Handle connection errors gracefully
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    // For debugging purposes only; remove or comment out in production
    // echo 'Connection failed: ' . $e->getMessage();
    exit;
}
?>
