<?php
$host = 'localhost';
$db = 'hotel_management';
$user = 'root';
$pass = ''; // XAMPP default has no password

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>