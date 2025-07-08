<?php
/**
 * Database Configuration for Hotel Management System
 */

// Use environment variables for security, with fallbacks for local development
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db = $_ENV['DB_NAME'] ?? 'hotel_management';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // Log error to the server's error log instead of showing it to the user
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Show a generic, user-friendly error message
    die("<p style='font-family: sans-serif; color: #800; text-align:center; margin-top:50px;'>
        ❌ We are currently experiencing technical difficulties. Please try again later.
        </p>");
}
?>