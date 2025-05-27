<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Allow admin and guest (office staff)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

$title = "Admin Dashboard";
require_once 'includes/header.php';
require_once 'php/db.php';

// Fetch totals
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_rooms = $conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
?>

<h2>📊 Hotel Overview</h2>

<div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 30px;">
    <div style="flex: 1; min-width: 200px; background-color: #0C2A58; border: 1px solid #F7B223; padding: 20px; border-radius: 8px; text-align: center;">
        <h3>Total Users</h3>
        <p style="font-size: 2em;"><?= $total_users ?></p>
    </div>

    <div style="flex: 1; min-width: 200px; background-color: #0C2A58; border: 1px solid #F7B223; padding: 20px; border-radius: 8px; text-align: center;">
        <h3>Total Rooms</h3>
        <p style="font-size: 2em;"><?= $total_rooms ?></p>
    </div>

    <div style="flex: 1; min-width: 200px; background-color: #0C2A58; border: 1px solid #F7B223; padding: 20px; border-radius: 8px; text-align: center;">
        <h3>Total Bookings</h3>
        <p style="font-size: 2em;"><?= $total_bookings ?></p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
