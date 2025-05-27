<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);


$sql = "
SELECT 
  b.id AS booking_id,
  u.id AS user_id,
  u.username,
  u.full_name,
  u.email,
  u.phone,
  r.room_number,
  r.room_type,
  b.check_in,
  b.check_out,
  b.total_price
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.id
WHERE b.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo "<p>Booking not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

$title = "Booking Details 
require_once 'includes/header.php';
?>

<a href="admin_bookings.php" style="
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: 
    color: 
    text-decoration: none;
    font-weight: bold;
    border-radius: 6px;
">
    ← Back to Manage Bookings
</a>

<h2>Booking Details 

<table style="max-width: 600px; border-collapse: collapse;">
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
  <tr><th style="text-align:left; padding:8px; border-bottom: 1px solid 
</table>

<p style="margin-top: 20px;">
  <a href="admin_user_edit.php?user_id=<?= $booking['user_id'] ?>" style="
      padding: 10px 20px;
      background-color: 
      color: 
      border-radius: 6px;
      font-weight: bold;
      text-decoration: none;
  ">Edit Guest Info</a>
</p>

<?php require_once 'includes/footer.php'; ?>
