<?php
session_start();
$title = "Booking Confirmation";
require_once 'includes/header.php';

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

<div style="
    max-width: 600px;
    margin: 60px auto;
    padding: 30px;
    background: #f4f4f4;
    border-radius: 10px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    text-align: center;
">
    <h2 style="color: #28a745; font-size: 2rem; margin-bottom: 20px;">✅ Booking Confirmed!</h2>
    
    <p style="font-size: 1.2rem; margin-bottom: 10px;">
        You have successfully booked a <strong><?= htmlspecialchars($room_type) ?></strong>
    </p>
    <p style="font-size: 1.1rem; margin-bottom: 5px;">
        🗓 Check-In: <strong><?= htmlspecialchars($checkin) ?></strong>
    </p>
    <p style="font-size: 1.1rem; margin-bottom: 5px;">
        🛏 Check-Out: <strong><?= htmlspecialchars($checkout) ?></strong>
    </p>
    <p style="font-size: 1.1rem; margin-bottom: 25px;">
        💰 Total: <strong>$<?= number_format((float)$total, 2) ?></strong>
    </p>

    <a href="welcome.php" style="
        display: inline-block;
        background-color: #F7B223;
        color: #081C3A;
        padding: 12px 24px;
        font-weight: bold;
        font-size: 1rem;
        border-radius: 6px;
        text-decoration: none;
    ">Go to Dashboard</a>
</div>

<?php require_once 'includes/footer.php'; ?>
