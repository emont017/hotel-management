<?php
session_start();
$title = "Booking Confirmation";
require_once 'includes/header.php';

// Block direct access
if (!isset($_SESSION['booking'])) {
    header("Location: index.php");
    exit;
}

// Get booking details and immediately clear
$room_type = $_SESSION['booking']['room_type'];
$checkin = $_SESSION['booking']['checkin'];
$checkout = $_SESSION['booking']['checkout'];
$total = $_SESSION['booking']['total'];
unset($_SESSION['booking']);
?>

<div style="max-width: 600px; margin: 50px auto; background: #0D2B56; color: #F7B223; padding: 30px; border-radius: 10px; text-align: center;">
    <h2>✅ Booking Confirmed!</h2>
    <p>You have successfully booked a <strong><?= htmlspecialchars($room_type) ?></strong></p>
    <p>Check-In: <strong><?= htmlspecialchars($checkin) ?></strong></p>
    <p>Check-Out: <strong><?= htmlspecialchars($checkout) ?></strong></p>
    <p>Total: <strong>$<?= number_format((float)$total, 2) ?></strong></p>
    <a href="welcome.php" style="color: #F7B223; text-decoration: underline; display: block; margin-top: 20px;">Return to Dashboard</a>
</div>

<?php require_once 'includes/footer.php'; ?>
