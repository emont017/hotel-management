<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';
require_once __DIR__ . '/../includes/audit_functions.php';
require_once __DIR__ . '/../includes/header.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

// Validate booking_id
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);
$success = "";

// Fetch booking details
$sql = "SELECT b.id AS booking_id, u.full_name, u.email, u.phone,
               b.check_in, b.check_out, b.status, b.total_price, 
               r.room_number, r.room_type, r.id AS room_id
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        WHERE b.id = ?";

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

// Fetch all rooms for the dropdown
$rooms = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE status != 'maintenance'");

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
        // Update booking
        $stmt = $conn->prepare(
            "UPDATE bookings 
             SET room_id = ?, check_in = ?, check_out = ?, status = ?
             WHERE id = ?"
        );
        $stmt->bind_param("isssi", $new_room_id, $new_check_in, $new_check_out, $new_status, $booking_id);
        $stmt->execute();
        $stmt->close();

        // Track changes for audit log
        $changes = [];
        if ($new_check_in != $booking['check_in']) {
            $changes[] = "Check-in: {$booking['check_in']} → {$new_check_in}";
        }
        if ($new_check_out != $booking['check_out']) {
            $changes[] = "Check-out: {$booking['check_out']} → {$new_check_out}";
        }
        if ($new_room_id != $booking['room_id']) {
            $changes[] = "Room: {$booking['room_number']} → {$new_room_id}";
        }
        if ($new_status != $old_status) {
            $changes[] = "Status: {$old_status} → {$new_status}";
        }

        $change_details = !empty($changes) ? implode(", ", $changes) : "Booking updated";
        log_booking_event($conn, $admin_user_id, 'Booking Modified', $booking_id, 
            "Admin {$admin_username} updated booking #{$booking_id}: {$change_details}");

        $conn->commit();
        $success = "Booking updated successfully!";
        // Reload booking details
        header("Location: admin_booking_detail.php?booking_id=" . $booking_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $success = "Error updating booking: " . $e->getMessage();
    }
}
?>

<div class="container mt-20">
    <a href="admin_bookings.php" class="btn btn-primary mb-20">&larr; Back to All Bookings</a>
    
    <h2>Booking Details #<?= htmlspecialchars($booking['booking_id']) ?></h2>

    <?php if ($success): ?>
        <p class="alert alert-success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <div class="booking-info mt-20">
        <p><strong>Guest Name:</strong> <?= htmlspecialchars($booking['full_name']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($booking['phone']) ?></p>
        <p><strong>Room:</strong> <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</p>
        <p><strong>Check-in:</strong> <?= htmlspecialchars($booking['check_in']) ?></p>
        <p><strong>Check-out:</strong> <?= htmlspecialchars($booking['check_out']) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($booking['status']) ?></p>
        <p><strong>Total Price:</strong> $<?= number_format($booking['total_price'], 2) ?></p>
    </div>

    <hr>

    <h3>Edit Booking</h3>
    <form method="POST" class="mt-20" onsubmit="return confirm('Are you sure you want to save these changes?');">
        <input type="hidden" name="old_status" value="<?= htmlspecialchars($booking['status']) ?>">

        <label class="form-label">Assign Room:</label>
        <select name="room_id" class="form-select" required>
            <?php while ($room = $rooms->fetch_assoc()): ?>
                <option value="<?= $room['id'] ?>" <?= $room['id'] == $booking['room_id'] ? 'selected' : '' ?>>
                    Room <?= $room['room_number'] ?> (<?= $room['room_type'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label class="form-label">Check-In Date:</label>
        <input type="date" name="check_in" class="form-input" value="<?= htmlspecialchars($booking['check_in']) ?>" required>

        <label class="form-label">Check-Out Date:</label>
        <input type="date" name="check_out" class="form-input" value="<?= htmlspecialchars($booking['check_out']) ?>" required>

        <label class="form-label">Booking Status:</label>
        <select name="status" class="form-select" required>
            <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
            <option value="checked-in" <?= $booking['status'] === 'checked-in' ? 'selected' : '' ?>>Checked-In</option>
            <option value="checked-out" <?= $booking['status'] === 'checked-out' ? 'selected' : '' ?>>Checked-Out</option>
            <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <button type="submit" class="btn btn-primary mt-20">Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
