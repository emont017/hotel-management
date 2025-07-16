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
    <h2 style="color: #2ecc71;">Booking Confirmed!</h2>
    
    <p style="margin-bottom: 20px;">
        You have successfully booked a <strong><?= htmlspecialchars($room_type) ?></strong>
    </p>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; text-align: left;">
            <div>
                <strong style="color: #666; font-size: 12px;">CHECK-IN</strong>
                <p style="margin: 5px 0 0 0; font-weight: bold;"><?= htmlspecialchars($checkin) ?></p>
            </div>
            <div>
                <strong style="color: #666; font-size: 12px;">CHECK-OUT</strong>
                <p style="margin: 5px 0 0 0; font-weight: bold;"><?= htmlspecialchars($checkout) ?></p>
            </div>
            <div>
                <strong style="color: #666; font-size: 12px;">TOTAL AMOUNT</strong>
                <p style="margin: 5px 0 0 0; font-weight: bold; color: #2ecc71;">$<?= number_format((float)$total, 2) ?></p>
            </div>
        </div>
    </div>
    
    <p style="color: #666; margin-bottom: 30px;">
        A confirmation email has been sent to your email address with all the details.
    </p>

    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <a href="manage_reservations.php" class="btn btn-primary">View My Reservations</a>
        <a href="welcome.php" class="btn btn-secondary">Go to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>