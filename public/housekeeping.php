<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Housekeeping";
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'housekeeping'])) {
    header("Location: /index.php");
    exit;
}

// Form handling logic remains the same
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_id'])) {
    if (isset($_POST['update_housekeeping_status'])) {
        $new_status = $_POST['housekeeping_status'];
        $stmt1 = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
        $stmt1->bind_param("si", $new_status, $_POST['room_id']);
        $stmt1->execute();
        $stmt1->close();
    }
    header("Location: housekeeping.php");
    exit;
}

// Simplified data fetching - We get all rooms and determine status in the loop
$query = "SELECT r.id, r.room_number, r.room_type, r.housekeeping_status, r.status as room_op_status, u.full_name as guest_name FROM rooms r LEFT JOIN (SELECT room_id, user_id FROM bookings WHERE status = 'checked-in' AND CURDATE() BETWEEN check_in AND check_out) AS current_bookings ON r.id = current_bookings.room_id LEFT JOIN users u ON current_bookings.user_id = u.id ORDER BY r.room_number ASC";
$all_rooms = $conn->query($query);
?>

<h2>🧹 Master Housekeeping List</h2>
<p>Filter the list to see rooms by their current status or manage them individually.</p>

<div class="filter-tabs">
    <div class="tab active" data-filter="all">All Rooms</div>
    <div class="tab" data-filter="occupied">Occupied</div>
    <div class="tab" data-filter="dirty">Needs Cleaning</div>
    <div class="tab" data-filter="clean">Clean & Ready</div>
    <div class="tab" data-filter="maintenance">Maintenance</div>
</div>

<div style="overflow-x: auto;">
    <table class="data-table" id="housekeeping-table">
        <thead>
            <tr>
                <th>Room</th>
                <th>Type</th>
                <th>Current Status</th>
                <th>Guest</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($room = $all_rooms->fetch_assoc()): ?>
                <?php
                    // Determine room status for filtering
                    $status_class = '';
                    $status_text = '';
                    if ($room['room_op_status'] === 'maintenance') {
                        $status_class = 'maintenance';
                        $status_text = 'Maintenance';
                    } elseif (!empty($room['guest_name'])) {
                        $status_class = 'occupied';
                        $status_text = 'Occupied';
                    } elseif ($room['housekeeping_status'] === 'clean') {
                        $status_class = 'clean';
                        $status_text = 'Clean & Ready';
                    } elseif ($room['housekeeping_status'] === 'dirty') {
                        $status_class = 'dirty';
                        $status_text = 'Needs Cleaning';
                    }
                ?>
                <tr data-status="<?= $status_class ?>">
                    <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                    <td><?= htmlspecialchars($room['room_type']) ?></td>
                    <td>
                        <span class="status-dot <?= $status_class ?>"></span>
                        <?= $status_text ?>
                    </td>
                    <td><?= htmlspecialchars($room['guest_name'] ?? 'N/A') ?></td>
                    <td>
                        <?php if($status_class === 'dirty'): ?>
                            <form method="post" class="update-form">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <input type="hidden" name="housekeeping_status" value="clean">
                                <button type="submit" name="update_housekeeping_status" class="btn btn-primary btn-sm">Mark as Clean</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.filter-tabs .tab');
    const rows = document.querySelectorAll('#housekeeping-table tbody tr');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab style
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            // Show/hide table rows
            rows.forEach(row => {
                if (filter === 'all' || row.getAttribute('data-status') === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>