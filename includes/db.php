<?php
// ============================================================
// includes/db.php
// Database connection using MySQLi scripting language
// ============================================================

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change to your MySQL username
define('DB_PASS', '');            // Change to your MySQL password
define('DB_NAME', 'car_service_db');

// Connect to the database
$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if connection was successful (conditional structure)
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character encoding to UTF-8
mysqli_set_charset($connection, 'utf8');
?>
