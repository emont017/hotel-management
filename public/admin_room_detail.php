<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header("Location: admin_rooms.php");
    exit;
}
$room_id = intval($_GET['room_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $room_type = $_POST['room_type'];
    $room_number = $_POST['room_number'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    // Updated query to remove the obsolete price column
    $sql = "UPDATE rooms SET room_type = ?, room_number = ?, capacity = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisi", $room_type, $room_number, $capacity, $status, $room_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_room_detail.php?room_id=$room_id");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

$title = "Manage Room " . htmlspecialchars($room['room_number']);
require_once __DIR__ . '/../includes/header.php';

if (!$room) {
    echo "<p class='alert alert-danger'>Room not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<a href="admin_rooms.php" class="btn btn-primary mb-20">
    ← Back to Manage Rooms
</a>

<h2>Manage Room <?= htmlspecialchars($room['room_number']) ?></h2>

<form method="POST" action="admin_room_detail.php?room_id=<?= $room_id ?>" class="form-container">
    <label class="form-label">Room Number:</label>
    <input type="text" name="room_number" class="form-input" value="<?= htmlspecialchars($room['room_number']) ?>" required>

    <label class="form-label">Room Type:</label>
    <input type="text" name="room_type" class="form-input" value="<?= htmlspecialchars($room['room_type']) ?>" required>

    <label class="form-label">Capacity:</label>
    <input type="number" name="capacity" class="form-input" value="<?= $room['capacity'] ?>" required>

    <label class="form-label">Availability:</label>
    <select name="status" class="form-select" required>
        <option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
    </select>

    <button type="submit" name="update_room" class="btn btn-primary">Save Changes</button>
    <button type="button" class="btn btn-secondary" onclick="window.location.href='admin_rooms.php'">Cancel</button>
</form>

<div class="card mt-30">
    <h3>Booking Calendar</h3>
    <div id="calendar"></div>
</div>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: '/hotel-management/php/get_room_bookings.php?room_id=<?= $room_id ?>'
    });
    calendar.render();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>