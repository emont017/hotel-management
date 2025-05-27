<?php
$host = 'localhost';
$db = 'hotel_management';
$user = 'root';
$pass = '';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error); // Log the error
    die("<p style='color: red; text-align:center; margin-top:30px;'>❌ Database connection failed. Please try again later.</p>");
}
?>
