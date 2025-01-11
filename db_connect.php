<?php
// Database connection configuration
$host = 'localhost';       // The hostname (for XAMPP, it's usually 'localhost')
$db = 'green_virtual_classroom'; // Your database name
$user = 'root';            // Your MySQL username (default for XAMPP is 'root')
$pass = '';                // Your MySQL password (default for XAMPP is empty)

// Create connection
$mysqli = new mysqli($host, $user, $pass, $db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
} 
?>
