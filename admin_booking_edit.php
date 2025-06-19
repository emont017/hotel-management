<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $room_id = $_POST['room_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE bookings SET check_in = ?, check_out = ?, room_id = ?, status = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $check_in, $check_out, $room_id, $status, $booking_id);
    $stmt->execute();
    $stmt->close();

    $success = "Booking updated successfully!";
}

// Fetch booking details
$sql = "
SELECT 
  b.id AS booking_id, 
  b.user_id, 
  u.full_name, 
  r.room_number, 
  r.room_type, 
  b.check_in, 
  b.check_out, 
  b.status,
  b.room_id
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.id
WHERE b.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    $title = "Booking Not Found";
    require_once 'includes/header.php';
    echo "<p style='color: red; text-align: center;'>Booking not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

// Fetch rooms for dropdown
$rooms = $conn->query("SELECT id, room_number, room_type FROM rooms WHERE status != 'maintenance'");

$title = "Edit Booking";
require_once 'includes/header.php';
?>

<a href="admin_booking_detail.php?booking_id=<?= $booking['booking_id'] ?>" style="
    display: inline-block;
    margin-bottom: 25px;
    padding: 10px 18px;
    background-color: #F7B223;
    color: #081C3A;
    text-decoration: none;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease;
" onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
    ← Back to Booking Details
</a>

<h2 style="color: #F7B223;">✏️ Edit Booking #<?= $booking['booking_id'] ?></h2>

<?php if (isset($success)): ?>
    <p style="color: green; font-weight: bold;"><?= $success ?></p>
<?php endif; ?>

<form method="POST" style="max-width: 600px; margin-top: 20px;">
    <label style="color: #F7B223; font-weight: bold;">Guest Name:</label>
    <input type="text" value="<?= htmlspecialchars($booking['full_name']) ?>" disabled style="width: 100%; padding: 10px; margin-bottom: 20px;">

    <label style="color: #F7B223; font-weight: bold;">Assign Room:</label>
    <select name="room_id" required style="width: 100%; padding: 10px; margin-bottom: 20px;">
        <?php while ($room = $rooms->fetch_assoc()): ?>
            <option value="<?= $room['id'] ?>" <?= $room['id'] == $booking['room_id'] ? 'selected' : '' ?>>
                Room <?= $room['room_number'] ?> (<?= $room['room_type'] ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label style="color: #F7B223; font-weight: bold;">Check-In Date:</label>
    <input type="date" name="check_in" value="<?= $booking['check_in'] ?>" required style="width: 100%; padding: 10px; margin-bottom: 20px;">

    <label style="color: #F7B223; font-weight: bold;">Check-Out Date:</label>
    <input type="date" name="check_out" value="<?= $booking['check_out'] ?>" required style="width: 100%; padding: 10px; margin-bottom: 20px;">

    <label style="color: #F7B223; font-weight: bold;">Booking Status:</label>
    <select name="status" required style="width: 100%; padding: 10px; margin-bottom: 30px;">
        <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="checked-in" <?= $booking['status'] === 'checked-in' ? 'selected' : '' ?>>Checked-In</option>
        <option value="checked-out" <?= $booking['status'] === 'checked-out' ? 'selected' : '' ?>>Checked-Out</option>
        <option value="cancelled" <?= $booking['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" style="
        background-color: #F7B223;
        color: #081C3A;
        font-weight: bold;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
        Save Changes
    </button>
</form>

<?php require_once 'includes/footer.php'; ?>