<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';

// Validate & get room_id
if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    header("Location: admin_rooms.php");
    exit;
}
$room_id = intval($_GET['room_id']);

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room'])) {
    $room_type = $_POST['room_type'];
    $room_number = $_POST['room_number'];
    $price = $_POST['price'];
    $capacity = $_POST['capacity'];
    $status = $_POST['status'];

    $sql = "UPDATE rooms SET room_type = ?, room_number = ?, price = ?, capacity = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssi", $room_type, $room_number, $price, $capacity, $status, $room_id);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_room_detail.php?room_id=$room_id");
    exit;
}

// Fetch room data
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();
$stmt->close();

if (!$room) {
    echo "<p>Room not found.</p>";
    require_once 'includes/footer.php';
    exit;
}

$title = "Manage Room " . htmlspecialchars($room['room_number']);
require_once 'includes/header.php';
?>

<style>
  form button {
    cursor: pointer;
    padding: 10px 20px;
    font-weight: bold;
    border-radius: 6px;
    border: none;
    margin-right: 10px;
  }
  button.save-btn {
    background-color: #F7B223;
    color: #081C3A;
  }
  button.cancel-btn {
    background-color: #ccc;
    color: #333;
  }
  button.cancel-btn:hover {
    background-color: #bbb;
  }
  .calendar-card {
    margin-top: 40px;
    background: #0C2A58;
    border-radius: 12px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    padding: 20px;
    color: #F7B223;
  }
  .calendar-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 700;
    font-family: 'Orbitron', sans-serif;
    font-size: 1.5rem;
  }
</style>

<a href="admin_rooms.php" style="
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #F7B223;
    color: #081C3A;
    text-decoration: none;
    font-weight: bold;
    border-radius: 6px;
">
    ← Back to Manage Rooms
</a>

<h2>Manage Room <?= htmlspecialchars($room['room_number']) ?></h2>

<form method="POST" action="admin_room_detail.php?room_id=<?= $room_id ?>" style="max-width: 600px;">
    <label>Room Number:</label><br>
    <input type="text" name="room_number" value="<?= htmlspecialchars($room['room_number']) ?>" required><br><br>

    <label>Room Type:</label><br>
    <input type="text" name="room_type" value="<?= htmlspecialchars($room['room_type']) ?>" required><br><br>

    <label>Price:</label><br>
    <input type="number" step="0.01" name="price" value="<?= $room['price'] ?>" required><br><br>

    <label>Capacity:</label><br>
    <input type="number" name="capacity" value="<?= $room['capacity'] ?>" required><br><br>

    <label>Availability:</label><br>
    <select name="status" required>
        <option value="available" <?= $room['status'] === 'available' ? 'selected' : '' ?>>Available</option>
        <option value="maintenance" <?= $room['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
    </select><br><br>

    <button type="submit" name="update_room" class="save-btn">Save Changes</button>
    <button type="button" class="cancel-btn" onclick="window.location.href='admin_rooms.php'">Cancel</button>
</form>

<div class="calendar-card">
    <h3>Booking Calendar</h3>
    <div id="calendar" style="max-width: 100%; height: 450px; background: #072046; border-radius: 10px;"></div>
</div>

<!-- FullCalendar CSS & JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

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
        events: 'php/get_room_bookings.php?room_id=<?= $room_id ?>',
        eventDidMount: function(info) {
            if(info.event.extendedProps.status === 'available'){
                info.el.style.backgroundColor = '#28a745'; // green
            } else if(info.event.extendedProps.status === 'maintenance'){
                info.el.style.backgroundColor = '#ffc107'; // yellow
            } else {
                info.el.style.backgroundColor = '#dc3545'; // red
            }
        }
    });
    calendar.render();
});
</script>

<?php require_once 'includes/footer.php'; ?>
