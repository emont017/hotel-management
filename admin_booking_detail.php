<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
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
  r.housekeeping_status,
  b.check_in,
  b.check_out,
  b.total_price,
  b.status
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
    $title = "Booking Not Found";
    require_once 'includes/header.php';
    echo "<p style='color: red; text-align: center;'>Booking not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

// Optional: Get payment info
$payment = $conn->query("SELECT * FROM payments WHERE booking_id = $booking_id")->fetch_assoc();

$title = "Booking Details";
require_once 'includes/header.php';
?>

<a href="admin_bookings.php" style="
    display: inline-block;
    margin-bottom: 25px;
    padding: 10px 18px;
    background-color: #F7B223;
    color: #081C3A;
    text-decoration: none;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease;
" onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
    ← Back to Manage Bookings
</a>

<h2 style="color: #F7B223;">Booking Details</h2>

<table style="width: 100%; max-width: 700px; margin-top: 20px; border-collapse: collapse; color: #fff;">
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Guest Name:</th><td><?= htmlspecialchars($booking['full_name']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Username:</th><td><?= htmlspecialchars($booking['username']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Email:</th><td><?= htmlspecialchars($booking['email']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Phone:</th><td><?= htmlspecialchars($booking['phone']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Room Type:</th><td><?= htmlspecialchars($booking['room_type']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Room Number:</th><td><?= htmlspecialchars($booking['room_number']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Check-in:</th><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Check-out:</th><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Total Price:</th><td>$<?= number_format($booking['total_price'], 2) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Booking Status:</th><td style="text-transform: capitalize;"><?= htmlspecialchars($booking['status'] ?? 'confirmed') ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Housekeeping Status:</th><td style="text-transform: capitalize;"><?= htmlspecialchars($booking['housekeeping_status'] ?? 'unknown') ?></td></tr>

  <?php if ($payment): ?>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Payment Method:</th><td><?= htmlspecialchars($payment['payment_method']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Payment Date:</th><td><?= htmlspecialchars($payment['payment_date']) ?></td></tr>
  <tr><th style="text-align:left; padding:10px; border-bottom: 1px solid #F7B223;">Transaction ID:</th><td><?= htmlspecialchars($payment['transaction_id']) ?></td></tr>
  <?php endif; ?>
</table>

<p style="margin-top: 30px;">
  <a href="admin_user_edit.php?user_id=<?= $booking['user_id'] ?>" style="
      padding: 12px 24px;
      background-color: #F7B223;
      color: #081C3A;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      transition: background-color 0.3s ease;
  " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
      Edit Guest Info
  </a>

  <a href="admin_booking_edit.php?booking_id=<?= $booking['booking_id'] ?>" style="
      margin-left: 20px;
      padding: 12px 24px;
      background-color: #F7B223;
      color: #081C3A;
      border-radius: 8px;
      font-weight: bold;
      text-decoration: none;
      transition: background-color 0.3s ease;
  " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
      Edit Booking
  </a>
</p>

<?php require_once 'includes/footer.php'; ?>