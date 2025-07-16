<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: /admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);
$success_message = '';

// Handle Check-in / Check-out / Re-Check-in Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];
    
    if (isset($_POST['check_in_guest'])) {
        $room_id = (int)$_POST['room_id'];
        
        $conn->begin_transaction();
        try {
            // Update booking status to checked-in
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-in' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            
            // Set room housekeeping status to occupied
            $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'occupied' WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            // Log the check-in action
            log_booking_event($conn, $admin_user_id, 'Guest Checked In', $booking_id, 
                "Guest checked in by admin: {$admin_username}");
            
            $conn->commit();
            $success_message = "Guest successfully checked in!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $success_message = "Error checking in guest: " . $e->getMessage();
        }
        
        // Refresh page to show updated status
        header("Location: admin_booking_detail.php?booking_id={$booking_id}");
        exit;
    }
    
    if (isset($_POST['check_out_guest'])) {
        $room_id = (int)$_POST['room_id'];
        
        $conn->begin_transaction();
        try {
            // Update booking status to checked-out
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-out' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            
            // Set room housekeeping status to dirty (needs cleaning)
            $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'dirty' WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            // Close the folio
            $stmt = $conn->prepare("UPDATE folios SET status = 'closed' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            
            // Log the check-out action
            log_booking_event($conn, $admin_user_id, 'Guest Checked Out', $booking_id, 
                "Guest checked out by admin: {$admin_username}");
            
            $conn->commit();
            $success_message = "Guest successfully checked out!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $success_message = "Error checking out guest: " . $e->getMessage();
        }
        
        // Refresh page to show updated status
        header("Location: admin_booking_detail.php?booking_id={$booking_id}");
        exit;
    }
    
    if (isset($_POST['re_check_in_guest'])) {
        $room_id = (int)$_POST['room_id'];
        
        $conn->begin_transaction();
        try {
            // Update booking status back to checked-in
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-in' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            
            // Set room housekeeping status to occupied
            $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'occupied' WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $stmt->close();
            
            // Reopen the folio
            $stmt = $conn->prepare("UPDATE folios SET status = 'open' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            
            // Log the re-check-in action
            log_booking_event($conn, $admin_user_id, 'Guest Re-Checked In', $booking_id, 
                "Guest re-checked in by admin: {$admin_username}");
            
            $conn->commit();
            $success_message = "Guest successfully re-checked in!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $success_message = "Error re-checking in guest: " . $e->getMessage();
        }
        
        // Refresh page to show updated status
        header("Location: admin_booking_detail.php?booking_id={$booking_id}");
        exit;
    }
}

// Fetch ALL necessary data for the page
// Fetch Booking, User, and Room details
$sql_booking = "SELECT b.id AS booking_id, b.room_id, u.id AS user_id, u.username, u.full_name, u.email, u.phone, r.room_number, r.room_type, r.housekeeping_status, b.check_in, b.check_out, b.total_price, b.status FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.id = ?";
$stmt_booking = $conn->prepare($sql_booking);
$stmt_booking->bind_param("i", $booking_id);
$stmt_booking->execute();
$booking = $stmt_booking->get_result()->fetch_assoc();
$stmt_booking->close();

// Fetch Folio and Folio Items
$folio_items = [];
if ($booking) {
    $stmt_folio = $conn->prepare("SELECT * FROM folios WHERE booking_id = ?");
    $stmt_folio->bind_param("i", $booking_id);
    $stmt_folio->execute();
    $folio = $stmt_folio->get_result()->fetch_assoc();
    $stmt_folio->close();

    if ($folio) {
        $stmt_items = $conn->prepare("SELECT * FROM folio_items WHERE folio_id = ? ORDER BY timestamp ASC");
        $stmt_items->bind_param("i", $folio['id']);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $folio_items[] = $item;
        }
        $stmt_items->close();
    }
}

$title = "Booking Details";
require_once __DIR__ . '/../includes/header.php';

if (!$booking) {
    echo "<p class='alert alert-danger'>Booking not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<a href="/admin_bookings.php" class="btn btn-secondary mb-20">Back to Manage Bookings</a>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?= $success_message ?></div>
<?php endif; ?>

<div class="detail-grid">
    <div class="detail-main">
        <div class="card">
            <h2>Booking Details #<?= $booking['booking_id'] ?></h2>
            <table class="details-table" style="margin-top: 20px;">
                <tr><th>Guest Name:</th><td><?= htmlspecialchars($booking['full_name']) ?></td></tr>
                <tr><th>Email:</th><td><?= htmlspecialchars($booking['email']) ?></td></tr>
                <tr><th>Phone:</th><td><?= htmlspecialchars($booking['phone']) ?></td></tr>
                <tr><th>Booking Status:</th><td class="text-capitalize"><b><?= htmlspecialchars($booking['status']) ?></b></td></tr>
                <tr><th>Room Number:</th><td><?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</td></tr>
                <tr><th>Housekeeping:</th><td class="text-capitalize"><?= htmlspecialchars($booking['housekeeping_status']) ?></td></tr>
                <tr><th>Check-in Date:</th><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
                <tr><th>Check-out Date:</th><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="detail-sidebar">
        <div class="card">
            <h3>Actions</h3>
            <div style="display: flex; flex-direction:column; gap: 10px; margin-top: 15px;">
                <?php if ($booking['status'] === 'confirmed'): ?>
                    <form method="POST" onsubmit="return confirm('Check in this guest?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="check_in_guest" class="btn btn-primary" style="width:100%;">Check-in Guest</button>
                    </form>
                <?php endif; ?>

                <?php if ($booking['status'] === 'checked-in'): ?>
                    <form method="POST" onsubmit="return confirm('Check out this guest?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="check_out_guest" class="btn btn-danger" style="width:100%;">Check-out Guest</button>
                    </form>
                <?php endif; ?>
                
                <?php if ($booking['status'] === 'checked-out'): ?>
                    <form method="POST" onsubmit="return confirm('Revert to checked-in status?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="re_check_in_guest" class="btn btn-secondary" style="width:100%;">Re-Check-in</button>
                    </form>
                <?php endif; ?>
                
                <a href="admin_booking_edit.php?booking_id=<?= $booking['booking_id'] ?>" class="btn btn-secondary">Edit Booking Dates</a>
            </div>
        </div>

        <div class="card mt-30">
            <h3>Folio Summary</h3>
            <table class="folio-summary-table">
                <?php foreach($folio_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['description']) ?></td>
                        <td class="amount">$<?= number_format($item['amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="folio-balance">
                <span>Balance Due:</span> $<?= number_format($folio['balance'] ?? 0.00, 2) ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>