<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

$title = "Admin Dashboard";
require_once 'includes/header.php';
require_once 'php/db.php';

$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_rooms = $conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];
?>

<h2 style="color: #F7B223;">📊 Hotel Overview</h2>

<div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 30px;">

    <div style="
        flex: 1;
        min-width: 200px;
        background-color: rgba(247, 178, 35, 0.15);
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        color: #fff;
        box-shadow: 0 0 12px rgba(0,0,0,0.3);
    ">
        <h3 style="margin-bottom: 10px;">👥 Total Users</h3>
        <p style="font-size: 2.5em; color: #F7B223; font-weight: bold;"><?= $total_users ?></p>
    </div>

    <div style="
        flex: 1;
        min-width: 200px;
        background-color: rgba(247, 178, 35, 0.15);
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        color: #fff;
        box-shadow: 0 0 12px rgba(0,0,0,0.3);
    ">
        <h3 style="margin-bottom: 10px;">🛏️ Total Rooms</h3>
        <p style="font-size: 2.5em; color: #F7B223; font-weight: bold;"><?= $total_rooms ?></p>
    </div>

    <div style="
        flex: 1;
        min-width: 200px;
        background-color: rgba(247, 178, 35, 0.15);
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        color: #fff;
        box-shadow: 0 0 12px rgba(0,0,0,0.3);
    ">
        <h3 style="margin-bottom: 10px;">📅 Total Bookings</h3>
        <p style="font-size: 2.5em; color: #F7B223; font-weight: bold;"><?= $total_bookings ?></p>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
