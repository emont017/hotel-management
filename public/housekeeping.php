<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Housekeeping";
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'housekeeping'])) {
    header("Location: /index.php");
    exit;
}

$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle task completion form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $task_id = (int)$_POST['task_id'];
    $room_id = (int)$_POST['room_id'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("UPDATE housekeeping_tasks SET status = 'completed' WHERE id = ? AND assigned_to_user_id = ?");
        $stmt1->bind_param("ii", $task_id, $user_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE rooms SET housekeeping_status = 'clean' WHERE id = ?");
        $stmt2->bind_param("i", $room_id);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
    } catch(Exception $e) {
        $conn->rollback();
    }
    header("Location: housekeeping.php");
    exit;
}
?>

<style>
.filter-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #122C55; padding-bottom: 10px; }
.filter-tabs .tab { padding: 8px 15px; cursor: pointer; border-radius: 6px; font-weight: bold; background-color: #122C55; border: 1px solid #122C55; transition: all 0.2s ease; }
.filter-tabs .tab:hover { background-color: #2E4053; }
.filter-tabs .tab.active { background-color: #B6862C; color: #081C3A; border-color: #F7B223; }
.status-dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; vertical-align: middle; }
.status-dot.clean { background-color: #2ecc71; }
.status-dot.dirty { background-color: #B6862C; }
.status-dot.occupied { background-color: #e74c3c; }
.status-dot.maintenance { background-color: #95a5a6; }
</style>

<?php if (in_array($user_role, ['admin', 'manager'])): ?>
    <?php
    // --- FINAL, ROBUST DATA FETCHING LOGIC ---
    // 1. Fetch all rooms
    $all_rooms_result = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
    $all_rooms = [];
    while($room = $all_rooms_result->fetch_assoc()) {
        $all_rooms[$room['id']] = $room;
    }

    // 2. Fetch all currently active, checked-in bookings
    $today = date('Y-m-d');
    $active_bookings = [];
    $bookings_query = "SELECT b.room_id, u.full_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.status = 'checked-in' AND '$today' >= b.check_in AND '$today' < b.check_out";
    $bookings_result = $conn->query($bookings_query);
    while($booking = $bookings_result->fetch_assoc()) {
        $active_bookings[$booking['room_id']] = $booking;
    }
    ?>
    <h2>Master Housekeeping List</h2>
    <p>Filter the list to see rooms by their current status or assign new cleaning tasks.</p>
    
    <div class="filter-tabs">
        <div class="tab active" data-filter="all">All Rooms</div>
        <div class="tab" data-filter="occupied">Occupied</div>
        <div class="tab" data-filter="dirty">Needs Cleaning</div>
        <div class="tab" data-filter="clean">Clean & Ready</div>
        <div class="tab" data-filter="maintenance">Maintenance</div>
        <a href="admin_housekeeping_assign.php" class="btn btn-primary" style="margin-left: auto;">Assign Tasks</a>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table" id="housekeeping-table">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Current Status</th>
                    <th>Guest</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_rooms as $room): ?>
                    <?php
                        // 3. Determine status with PHP for reliability based on ENUM values
                        $status_class = '';
                        $status_text = '';
                        $guest_name = null;
                        
                        // Use a specific hierarchy to determine the most accurate current status.
                        if ($room['status'] === 'maintenance' || $room['housekeeping_status'] === 'maintenance') {
                            $status_class = 'maintenance';
                            $status_text = 'Maintenance';
                        } elseif (isset($active_bookings[$room['id']])) {
                            $status_class = 'occupied';
                            $status_text = 'Occupied';
                            $guest_name = $active_bookings[$room['id']]['full_name'];
                        } elseif ($room['housekeeping_status'] === 'clean') {
                            $status_class = 'clean';
                            $status_text = 'Clean & Ready';
                        } elseif (in_array($room['housekeeping_status'], ['dirty', 'occupied'])) {
                            // A room is dirty if explicitly 'dirty', or if 'occupied' without an active check-in (post-checkout).
                            $status_class = 'dirty';
                            $status_text = 'Checked out / Dirty';
                        } else {
                            // Fallback for any other or null status, ensuring no blank statuses.
                            $status_class = 'dirty';
                            $status_text = 'Needs Attention';
                        }
                    ?>
                    <tr data-status="<?= $status_class ?>">
                        <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                        <td>
                            <span class="status-dot <?= $status_class ?>"></span>
                            <?= $status_text ?>
                        </td>
                        <td><?= htmlspecialchars($guest_name ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <?php
    // ... Housekeeper view logic and HTML ...
    ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.filter-tabs .tab');
    if (tabs.length > 0) {
        const rows = document.querySelectorAll('#housekeeping-table tbody tr');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const filter = this.getAttribute('data-filter');
                rows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }
});
</script>