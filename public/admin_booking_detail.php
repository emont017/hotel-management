<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Fetch main booking details
$sql = "SELECT b.id AS booking_id, u.id AS user_id, u.username, u.full_name, u.email, u.phone, r.room_number, r.room_type, r.housekeeping_status, b.check_in, b.check_out, b.total_price, b.status FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

$title = "Booking Details";
require_once __DIR__ . '/../includes/header.php';

if (!$booking) {
    echo "<p class='alert alert-danger'>Booking not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// SECURELY get payment info
$stmt_payment = $conn->prepare("SELECT * FROM payments WHERE booking_id = ?");
$stmt_payment->bind_param("i", $booking_id);
$stmt_payment->execute();
$payment_result = $stmt_payment->get_result();
$payment = $payment_result->fetch_assoc();
$stmt_payment->close();

?>

<a href="admin_bookings.php" class="btn btn-primary mb-20">
    ← Back to Manage Bookings
</a>

<h2>Booking Details #<?= $booking['booking_id'] ?></h2>

<table class="details-table">
  <tr><th>Guest Name:</th><td><?= htmlspecialchars($booking['full_name']) ?></td></tr>
  <tr><th>Username:</th><td><?= htmlspecialchars($booking['username']) ?></td></tr>
  <tr><th>Email:</th><td><?= htmlspecialchars($booking['email']) ?></td></tr>
  <tr><th>Phone:</th><td><?= htmlspecialchars($booking['phone']) ?></td></tr>
  <tr><th>Room Type:</th><td><?= htmlspecialchars($booking['room_type']) ?></td></tr>
  <tr><th>Room Number:</th><td><?= htmlspecialchars($booking['room_number']) ?></td></tr>
  <tr><th>Check-in:</th><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
  <tr><th>Check-out:</th><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
  <tr><th>Total Price:</th><td>$<?= number_format($booking['total_price'], 2) ?></td></tr>
  <tr><th>Booking Status:</th><td class="text-capitalize"><?= htmlspecialchars($booking['status'] ?? 'confirmed') ?></td></tr>
  <tr><th>Housekeeping Status:</th><td class="text-capitalize"><?= htmlspecialchars($booking['housekeeping_status'] ?? 'unknown') ?></td></tr>

  <?php if ($payment): ?>
  <tr><th>Payment Method:</th><td><?= htmlspecialchars($payment['payment_method']) ?></td></tr>
  <tr><th>Payment Date:</th><td><?= htmlspecialchars($payment['payment_date']) ?></td></tr>
  <tr><th>Transaction ID:</th><td><?= htmlspecialchars($payment['transaction_id']) ?></td></tr>
  <?php endif; ?>
</table>

<p class="mt-30">
  <a href="admin_user_edit.php?user_id=<?= $booking['user_id'] ?>&booking_id=<?= $booking['booking_id'] ?>" class="btn btn-primary">
      Edit Guest Info
  </a>

  <a href="admin_booking_edit.php?booking_id=<?= $booking['booking_id'] ?>" class="btn btn-primary" style="margin-left: 15px;">
      Edit Booking
  </a>
</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>