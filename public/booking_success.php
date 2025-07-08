<?php
session_start();
$title = "Booking Confirmation";
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['booking'])) {
    header("Location: index.php");
    exit;
}

$room_type = $_SESSION['booking']['room_type'];
$checkin = $_SESSION['booking']['checkin'];
$checkout = $_SESSION['booking']['checkout'];
$total = $_SESSION['booking']['total'];
unset($_SESSION['booking']);
?>

<div class="card text-center" style="max-width: 600px; margin: 60px auto;">
    <h2 style="color: #2ecc71;">✅ Booking Confirmed!</h2>
    
    <p>
        You have successfully booked a <strong><?= htmlspecialchars($room_type) ?></strong>
    </p>
    <p>🗓 Check-In: <strong><?= htmlspecialchars($checkin) ?></strong></p>
    <p>🛏 Check-Out: <strong><?= htmlspecialchars($checkout) ?></strong></p>
    <p class="mb-20">
        💰 Total: <strong>$<?= number_format((float)$total, 2) ?></strong>
    </p>

    <a href="welcome.php" class="btn btn-primary">Go to Dashboard</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>