<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guest') { // Standardized role
    header("Location: index.php");
    exit;
}

$title = "Manage Your Reservations";
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Enhanced query to get more booking details including status and confirmation number
$sql = "SELECT b.id, r.room_type, r.room_number, b.check_in, b.check_out, b.total_price, b.status, b.confirmation_number FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? ORDER BY b.check_in DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Function to get room image based on room type
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

// Function to get status badge styling
function getStatusBadge($status) {
    switch ($status) {
        case 'confirmed':
            return '<span style="background: #B6862C; color: #081C3A; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">Confirmed</span>';
        case 'checked-in':
            return '<span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">Checked In</span>';
        case 'checked-out':
            return '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">Completed</span>';
        case 'cancelled':
            return '<span style="background: #dc3545; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">Cancelled</span>';
        default:
            return '<span style="background: #122C55; color: white; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">' . ucfirst($status) . '</span>';
    }
}
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <h2 class="text-center mb-20">Your Reservations</h2>

    <?php if ($result->num_rows === 0): ?>
        <div class="card text-center" style="max-width: 600px; margin: 60px auto;">
            <h3 style="color: #666; margin-bottom: 15px;">No Reservations Found</h3>
            <p style="color: #888; margin-bottom: 25px;">You haven't made any reservations yet. Ready to book your next stay?</p>
            <a href="bookings.php" class="btn btn-primary">Book Your Stay</a>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 20px; max-width: 800px; margin: 0 auto;">
            <?php while ($row = $result->fetch_assoc()): 
                $room_image = getRoomImage($row['room_type']);
                $checkin_date = new DateTime($row['check_in']);
                $checkout_date = new DateTime($row['check_out']);
                $nights = $checkin_date->diff($checkout_date)->days;
            ?>
                <div class="card">
                    <!-- Header with Room Type and Status -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #122C55;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #B6862C;"><?= htmlspecialchars($row['room_type']) ?></h3>
                            <p style="margin: 0; color: #aaa; font-size: 14px;">Room <?= htmlspecialchars($row['room_number']) ?></p>
                        </div>
                        <?= getStatusBadge($row['status']) ?>
                    </div>
                    
                    <!-- Room Image -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="<?= htmlspecialchars($room_image) ?>" alt="<?= htmlspecialchars($row['room_type']) ?>" 
                             style="width: 100%; max-width: 350px; height: 180px; object-fit: cover; border-radius: 8px; border: 1px solid #122C55;"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div style="display: none; background: #122C55; width: 100%; max-width: 350px; height: 180px; border-radius: 8px; align-items: center; justify-content: center; color: #aaa; font-size: 14px; border: 1px solid #122C55;">
                            Room Image Not Available
                        </div>
                    </div>
                    
                    <!-- Booking Information Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div style="text-align: center;">
                            <div style="color: #B6862C; font-size: 11px; font-weight: bold; margin-bottom: 5px;">BOOKING ID</div>
                            <div style="color: white; font-weight: bold;">#<?= htmlspecialchars($row['id']) ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="color: #B6862C; font-size: 11px; font-weight: bold; margin-bottom: 5px;">CHECK-IN</div>
                            <div style="color: white; font-weight: bold;"><?= $checkin_date->format('M j, Y') ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="color: #B6862C; font-size: 11px; font-weight: bold; margin-bottom: 5px;">CHECK-OUT</div>
                            <div style="color: white; font-weight: bold;"><?= $checkout_date->format('M j, Y') ?></div>
                        </div>
                        <div style="text-align: center;">
                            <div style="color: #B6862C; font-size: 11px; font-weight: bold; margin-bottom: 5px;">DURATION</div>
                            <div style="color: white; font-weight: bold;"><?= $nights ?> night<?= $nights > 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                    
                    <!-- Total and Confirmation -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #122C55; border-radius: 8px; margin-bottom: 20px;">
                        <div>
                            <?php if (!empty($row['confirmation_number'])): ?>
                                <div style="color: #aaa; font-size: 12px; margin-bottom: 2px;">Confirmation Number</div>
                                <div style="font-weight: bold; color: white; font-size: 14px;"><?= htmlspecialchars($row['confirmation_number']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: #aaa; font-size: 12px; margin-bottom: 2px;">Total Amount</div>
                            <div style="font-weight: bold; color: #B6862C; font-size: 18px;">$<?= number_format($row['total_price'], 2) ?></div>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <div style="text-align: center;">
                        <a href="guest_booking_detail.php?booking_id=<?= $row['id'] ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="text-center mt-30">
            <a href="bookings.php" class="btn btn-primary">Book Another Stay</a>
        </div>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>