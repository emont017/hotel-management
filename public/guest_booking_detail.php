<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to guests only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guest') {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: manage_reservations.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);
$user_id = $_SESSION['user_id'];

// Fetch booking details - ensure the booking belongs to the logged-in guest
$sql_booking = "SELECT b.id AS booking_id, b.room_id, u.id AS user_id, u.username, u.full_name, u.email, u.phone, r.room_number, r.room_type, r.image_path, b.check_in, b.check_out, b.total_price, b.status, b.confirmation_number FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.id = ? AND b.user_id = ?";
$stmt_booking = $conn->prepare($sql_booking);
$stmt_booking->bind_param("ii", $booking_id, $user_id);
$stmt_booking->execute();
$booking = $stmt_booking->get_result()->fetch_assoc();
$stmt_booking->close();

if (!$booking) {
    echo "<script>alert('Booking not found or access denied.'); window.location.href = 'manage_reservations.php';</script>";
    exit;
}

// Calculate nights stayed
$checkin_date = new DateTime($booking['check_in']);
$checkout_date = new DateTime($booking['check_out']);
$nights = $checkin_date->diff($checkout_date)->days;

// Get room image based on room type
function getRoomImage($room_type) {
    switch ($room_type) {
        case 'Double Room':
            return 'assets/images/room_double.jpg';
        case 'Executive Suite':
            return 'assets/images/room_executive.jpg';
        case 'Suite with Balcony':
            return 'assets/images/room_balcony.jpg';
        default:
            return 'assets/images/room_double.jpg'; // fallback
    }
}

$room_image = getRoomImage($booking['room_type']);

$title = "Booking Details";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
    <a href="manage_reservations.php" class="btn btn-secondary mb-20">← Back to My Reservations</a>

    <div class="card">
        <h2>Booking Details</h2>
        <p class="mb-20" style="color: #666;">Confirmation Number: <strong><?= htmlspecialchars($booking['confirmation_number']) ?></strong></p>
        
        <!-- Room Image Section -->
        <div style="text-align: center; margin-bottom: 30px;">
            <img src="<?= htmlspecialchars($room_image) ?>" alt="<?= htmlspecialchars($booking['room_type']) ?>" 
                 style="width: 100%; max-width: 500px; height: 250px; object-fit: cover; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.12);"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div style="display: none; background: #f8f9fa; width: 100%; max-width: 500px; height: 250px; border-radius: 12px; align-items: center; justify-content: center; color: #6c757d; font-size: 16px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); margin: 0 auto;">
                Room Image Not Available
            </div>
            <h4 style="margin: 15px 0 5px 0; color: #333; font-size: 24px;"><?= htmlspecialchars($booking['room_type']) ?></h4>
            <p style="color: #666; font-size: 16px; margin: 0;">Room <?= htmlspecialchars($booking['room_number']) ?></p>
        </div>
        
        <!-- Booking Details Table -->
        <table class="details-table" style="margin-top: 20px;">
            <tr><th>Booking ID:</th><td>#<?= htmlspecialchars($booking['booking_id']) ?></td></tr>
            <tr><th>Guest Name:</th><td><?= htmlspecialchars($booking['full_name']) ?></td></tr>
            <tr><th>Email:</th><td><?= htmlspecialchars($booking['email']) ?></td></tr>
            <tr><th>Phone:</th><td><?= htmlspecialchars($booking['phone']) ?></td></tr>
            <tr><th>Booking Status:</th><td class="text-capitalize"><strong style="color: #2ecc71;"><?= htmlspecialchars($booking['status']) ?></strong></td></tr>
            <tr><th>Check-in Date:</th><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
            <tr><th>Check-out Date:</th><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
            <tr><th>Number of Nights:</th><td><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?></td></tr>
            <tr><th>Total Amount:</th><td><strong style="color: #2ecc71;">$<?= number_format($booking['total_price'], 2) ?></strong></td></tr>
        </table>
    </div>

    <?php if ($booking['status'] === 'confirmed'): ?>
        <div class="card mt-20" style="background: #e8f4fd; border-left: 4px solid #2196F3;">
            <h4 style="color: #1976D2; margin-bottom: 10px;">Upcoming Stay</h4>
            <p style="margin: 0; color: #555;">Your reservation is confirmed. Please arrive after 3:00 PM on your check-in date. If you need to make any changes, please contact the hotel directly.</p>
        </div>
    <?php elseif ($booking['status'] === 'checked-in'): ?>
        <div class="card mt-20" style="background: #e8f5e8; border-left: 4px solid #4CAF50;">
            <h4 style="color: #2E7D32; margin-bottom: 10px;">Currently Checked In</h4>
            <p style="margin: 0; color: #555;">Welcome! You are currently checked in. Check-out time is 11:00 AM. We hope you're enjoying your stay.</p>
        </div>
    <?php elseif ($booking['status'] === 'checked-out'): ?>
        <div class="card mt-20" style="background: #f0f0f0; border-left: 4px solid #757575;">
            <h4 style="color: #424242; margin-bottom: 10px;">Stay Completed</h4>
            <p style="margin: 0; color: #555;">Thank you for staying with us! We hope you had a wonderful experience and look forward to welcoming you back.</p>
        </div>
    <?php endif; ?>

    <div class="text-center mt-30">
        <a href="bookings.php" class="btn btn-primary">Book Another Stay</a>
        <a href="welcome.php" class="btn btn-secondary ml-10">Return to Dashboard</a>
    </div>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?> 