<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Housekeeping Tasks";

// --- Security & Role Management ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$feedback_message = '';
$feedback_type = '';

// --- Handle Status Update Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = (int)$_POST['room_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['clean', 'dirty', 'maintenance'];

    if ($room_id > 0 && in_array($new_status, $allowed_statuses)) {
        $conn->begin_transaction();
        try {
            // Update room status
            $stmt_update = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_status, $room_id);
            $stmt_update->execute();
            $stmt_update->close();

            // If status is changed to 'clean', complete the associated task
            if ($new_status === 'clean') {
                $stmt_complete = $conn->prepare("UPDATE housekeeping_tasks SET status = 'completed' WHERE room_id = ? AND status = 'pending' AND task_date = CURDATE()");
                $stmt_complete->bind_param("i", $room_id);
                $stmt_complete->execute();
                $stmt_complete->close();
            }

            // Log the change
            $log_notes = "Status manually changed to {$new_status} by " . $_SESSION['username'];
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

// --- Data Fetching ---
$sql = "
    SELECT 
        u.full_name as housekeeper_name,
        r.id as room_id,
        r.room_number,
        r.room_type,
        r.housekeeping_status
    FROM housekeeping_tasks ht
    JOIN users u ON ht.assigned_to_user_id = u.id
    JOIN rooms r ON ht.room_id = r.id
    WHERE ht.status = 'pending' AND ht.task_date = CURDATE()
    ORDER BY u.full_name, r.room_number
";
$tasks_result = $conn->query($sql);

$tasks_by_housekeeper = [];
while ($task = $tasks_result->fetch_assoc()) {
    $tasks_by_housekeeper[$task['housekeeper_name']][] = $task;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.status-form { display: flex; align-items: center; gap: 10px; }
.status-form .form-select { min-width: 120px; }
.housekeeper-group { margin-bottom: 30px; }
</style>

<h2>Today's Housekeeping Assignments</h2>
<p>Monitor the progress of today's cleaning tasks and manually update room statuses as needed.</p>

<?php if ($feedback_message): ?>
<div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($feedback_message) ?>
</div>
<?php endif; ?>

<?php if (empty($tasks_by_housekeeper)): ?>
    <div class="card text-center">
        <p>No housekeeping tasks are currently assigned for today.</p>
        <a href="admin_housekeeping_assign.php" class="btn btn-primary">Assign Tasks Now</a>
    </div>
<?php else: ?>
    <?php foreach ($tasks_by_housekeeper as $housekeeper => $tasks): ?>
        <div class="housekeeper-group">
            <h3>Assignments for: <?= htmlspecialchars($housekeeper) ?></h3>
            <div class="card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room</th>
                            <th>Type</th>
                            <th>Current Status</th>
                            <th style="min-width: 250px;">Change Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($task['room_number']) ?></strong></td>
                                <td><?= htmlspecialchars($task['room_type']) ?></td>
                                <td>
                                    <span class="status-dot <?= htmlspecialchars($task['housekeeping_status']) ?>"></span>
                                    <span class="text-capitalize"><?= htmlspecialchars($task['housekeeping_status']) ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="housekeeping_tasks.php" class="status-form">
                                        <input type="hidden" name="room_id" value="<?= $task['room_id'] ?>">
                                        <select name="new_status" class="form-select form-select-sm">
                                            <option value="clean" <?= $task['housekeeping_status'] === 'clean' ? 'selected' : '' ?>>Clean</option>
                                            <option value="dirty" <?= $task['housekeeping_status'] === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>