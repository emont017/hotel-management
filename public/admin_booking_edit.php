<?php
session_start();
// Corrected paths from /public/ directory
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';
require_once __DIR__ . '/../includes/audit_functions.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_room_id = (int)$_POST['room_id'];
    $new_check_in = $_POST['check_in'];
    $new_check_out = $_POST['check_out'];
    $new_status = $_POST['status'];
    $old_status = $_POST['old_status'];
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET room_id = ?, check_in = ?, check_out = ?, status = ? WHERE id = ?");
        $stmt->bind_param("isssi", $new_room_id, $new_check_in, $new_check_out, $new_status, $booking_id);
        $stmt->execute();
        $stmt->close();
        
        $changes = [];
        if ($new_check_in != $booking['check_in']) {
            $changes[] = "Check-in: {$booking['check_in']} to {$new_check_in}";
        }
        if ($new_check_out != $booking['check_out']) {
            $changes[] = "Check-out: {$booking['check_out']} to {$new_check_out}";
        }
        if ($new_room_id != $booking['room_id']) {
            $changes[] = "Room changed";
        }
        if ($new_status != $old_status) {
            $changes[] = "Status: {$old_status} to {$new_status}";
        }
        
        $change_details = !empty($changes) ? implode(", ", $changes) : "Booking updated";
        log_booking_event($conn, $admin_user_id, 'Booking Modified', $booking_id, 
            "Booking edited by admin {$admin_username}: {$change_details}");
        
        $conn->commit();
        $success = "Booking updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $success = "Error updating booking: " . $e->getMessage();
    }
}

// Fetch booking details
$sql = "SELECT b.id AS booking_id, u.full_name, b.check_in, b.check_out, b.status, b.room_id FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    echo "<p class='alert alert-danger'>Booking not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch rooms for dropdown
$rooms = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE status != 'maintenance'");

$title = "Edit Booking";
?>

<a href="admin_booking_detail.php?booking_id=<?= $booking['booking_id'] ?>" class="btn btn-primary mb-20">
     Back to Booking Details
</a>

<h2>Edit Booking #<?= $booking['booking_id'] ?></h2>

<?php if (isset($success)): ?>
    <p class="alert alert-success"><?= $success ?></p>
<?php endif; ?>

<form method="POST" class="mt-30" onsubmit="return confirm('Are you sure you want to save these changes? If you are cancelling the booking, this action cannot be undone.');">
    
    <input type="hidden" name="old_status" value="<?= htmlspecialchars($booking['status']) ?>">
    
    <label class="form-label">Guest Name:</label>
    <input type="text" class="form-input" value="<?= htmlspecialchars($booking['full_name']) ?>" disabled>

    <label class="form-label">Assign Room:</label>
    <select name="room_id" class="form-select" required>
        <?php while ($room = $rooms->fetch_assoc()): ?>
            <option value="<?= $room['id'] ?>" <?= $room['id'] == $booking['room_id'] ? 'selected' : '' ?>>
                Room <?= $room['room_number'] ?> (<?= $room['room_type'] ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label class="form-label">Check-In Date:</label>
    <input type="date" name="check_in" class="form-input" value="<?= $booking['check_in'] ?>" required>

    <label class="form-label">Check-Out Date:</label>
    <input type="date" name="check_out" class="form-input" value="<?= $booking['check_out'] ?>" required>

    <label class="form-label">Booking Status:</label>
    <select name="status" class="form-select" required>
        <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="checked-in" <?= $booking['status'] === 'checked-in' ? 'selected' : '' ?>>Checked-In</option>
        <option value="checked-out" <?= $booking['status'] === 'checked-out' ? 'selected' : '' ?>>Checked-Out</option>
        <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="btn btn-primary mt-30">
        Save Changes
    </button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>