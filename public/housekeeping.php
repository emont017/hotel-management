<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Housekeeping";

// --- Security & Role Management ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'housekeeping'])) {
    header("Location: /index.php");
    exit;
}
$user_role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];
$feedback_message = '';
$feedback_type = '';

// --- Handle Status Update Form Submission (for Admins/Managers) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (in_array($user_role, ['admin', 'manager'])) {
        $room_id = (int)$_POST['room_id'];
        $new_status = $_POST['new_status'];
        $allowed_statuses = ['clean', 'dirty', 'maintenance'];

        if ($room_id > 0 && in_array($new_status, $allowed_statuses)) {
            $conn->begin_transaction();
            try {
                // Update the room's status
                $stmt_update = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
                $stmt_update->bind_param("si", $new_status, $room_id);
                $stmt_update->execute();
                $stmt_update->close();

                // Log the change
                $log_notes = "Status changed by " . $_SESSION['username'];
                $stmt_log = $conn->prepare("INSERT INTO housekeeping_logs (room_id, status, updated_by, notes) VALUES (?, ?, ?, ?)");
                $stmt_log->bind_param("isis", $room_id, $new_status, $user_id, $log_notes);
                $stmt_log->execute();
                $stmt_log->close();
                
                $conn->commit();
                $feedback_message = "Room status updated successfully!";
                $feedback_type = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $feedback_message = "Error updating status: " . $e->getMessage();
                $feedback_type = 'danger';
            }
        } else {
            $feedback_message = "Invalid data provided for status update.";
            $feedback_type = 'danger';
        }
    }
}

// --- Handle Task Completion (for Housekeepers) ---
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
        $feedback_message = "Task completed and room marked as clean!";
        $feedback_type = 'success';
    } catch(Exception $e) {
        $conn->rollback();
        $feedback_message = "Error completing task.";
        $feedback_type = 'danger';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.status-form { display: flex; align-items: center; gap: 10px; }
.status-form .form-select { min-width: 120px; }
</style>

<?php if ($feedback_message): ?>
<div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($feedback_message) ?>
</div>
<?php endif; ?>

<?php if (in_array($user_role, ['admin', 'manager'])): ?>
    <?php
    // --- Data Fetching Logic for Admin/Manager View ---
    $all_rooms_result = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
    $all_rooms = [];
    while($room = $all_rooms_result->fetch_assoc()) {
        $all_rooms[$room['id']] = $room;
    }
    $today = date('Y-m-d');
    
    // Get active bookings
    $active_bookings = [];
    $bookings_result = $conn->query("SELECT b.room_id, u.full_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.status = 'checked-in' AND '$today' >= b.check_in AND '$today' < b.check_out");
    while($booking = $bookings_result->fetch_assoc()) {
        $active_bookings[$booking['room_id']] = $booking;
    }

    // Get pending tasks
    $pending_tasks = [];
    $tasks_result = $conn->query("SELECT ht.room_id, u.username FROM housekeeping_tasks ht JOIN users u ON ht.assigned_to_user_id = u.id WHERE ht.status = 'pending' AND ht.task_date = CURDATE()");
    while($task = $tasks_result->fetch_assoc()) {
        $pending_tasks[$task['room_id']] = $task['username'];
    }
    ?>
    <h2>Master Housekeeping List</h2>
    <p>Filter the list to see rooms by their current status, assign tasks, or manually update a room's condition.</p>
    
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
                    <th>Guest / Assigned To</th>
                    <th style="min-width: 250px;">Change Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_rooms as $room): ?>
                    <?php
                        $status_class = '';
                        $status_text = '';
                        $occupant_info = 'N/A';
                        $is_occupied = isset($active_bookings[$room['id']]);

                        if ($room['status'] === 'maintenance' || $room['housekeeping_status'] === 'maintenance') {
                            $status_class = 'maintenance';
                            $status_text = 'Maintenance';
                        } elseif ($is_occupied) {
                            $status_class = 'occupied';
                            $status_text = 'Occupied';
                            $occupant_info = $active_bookings[$room['id']]['full_name'];
                        } elseif ($room['housekeeping_status'] === 'clean') {
                            $status_class = 'clean';
                            $status_text = 'Clean & Ready';
                        } else {
                            $status_class = 'dirty';
                            $status_text = 'Checked out / Dirty';
                            if (isset($pending_tasks[$room['id']])) {
                                $occupant_info = 'Assigned to: ' . htmlspecialchars($pending_tasks[$room['id']]);
                            }
                        }
                    ?>
                    <tr data-status="<?= $status_class ?>">
                        <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                        <td>
                            <span class="status-dot <?= $status_class ?>"></span>
                            <?= $status_text ?>
                        </td>
                        <td><?= htmlspecialchars($occupant_info) ?></td>
                        <td>
                            <form method="POST" action="housekeeping.php" class="status-form">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <select name="new_status" class="form-select form-select-sm" <?= $is_occupied ? 'disabled' : '' ?>>
                                    <option value="clean" <?= $room['housekeeping_status'] === 'clean' ? 'selected' : '' ?>>Clean</option>
                                    <option value="dirty" <?= $room['housekeeping_status'] === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                                    <option value="maintenance" <?= $room['housekeeping_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                        </td>
                        <td>
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm" <?= $is_occupied ? 'disabled' : '' ?>>Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: // --- Housekeeper View --- ?>
    <?php
    $tasks_stmt = $conn->prepare("SELECT ht.id as task_id, r.id as room_id, r.room_number, r.room_type FROM housekeeping_tasks ht JOIN rooms r ON ht.room_id = r.id WHERE ht.assigned_to_user_id = ? AND ht.status = 'pending' AND ht.task_date = CURDATE()");
    $tasks_stmt->bind_param("i", $user_id);
    $tasks_stmt->execute();
    $tasks_result = $tasks_stmt->get_result();
    ?>
    <h2>My Cleaning Assignments for Today</h2>
    <p>Once you have finished cleaning a room, mark the task as complete.</p>
    <div style="overflow-x: auto;">
        <table class="data-table">
             <thead>
                <tr>
                    <th>Room</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tasks_result->num_rows > 0): ?>
                    <?php while($task = $tasks_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($task['room_number']) ?></strong></td>
                            <td><?= htmlspecialchars($task['room_type']) ?></td>
                            <td>
                                <form method="POST" action="housekeeping.php">
                                    <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                    <input type="hidden" name="room_id" value="<?= $task['room_id'] ?>">
                                    <button type="submit" name="complete_task" class="btn btn-primary">Mark as Clean</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">You have no pending cleaning assignments for today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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