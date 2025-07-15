<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

$feedback_message = '';
$feedback_type = '';

// Handle form submission to assign tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_tasks'])) {
    $assignee_id = (int)$_POST['assignee_id'];
    $room_ids = $_POST['room_ids'] ?? [];
    $task_date = date('Y-m-d');

    if ($assignee_id && !empty($room_ids)) {
        $stmt = $conn->prepare("INSERT INTO housekeeping_tasks (room_id, assigned_to_user_id, task_date) VALUES (?, ?, ?)");
        foreach ($room_ids as $room_id) {
            $stmt->bind_param("iis", $room_id, $assignee_id, $task_date);
            $stmt->execute();
        }
        $stmt->close();
        $feedback_message = count($room_ids) . " task(s) assigned successfully!";
        $feedback_type = 'success';
    } else {
        $feedback_message = "Please select a housekeeper and at least one room.";
        $feedback_type = 'danger';
    }
}

// --- DATA FETCHING ---

// 1. Fetch rooms that are 'dirty' AND do NOT have a pending task for today
$dirty_rooms_sql = "
    SELECT r.id, r.room_number 
    FROM rooms r
    LEFT JOIN housekeeping_tasks ht ON r.id = ht.room_id AND ht.status = 'pending' AND ht.task_date = CURDATE()
    WHERE r.housekeeping_status = 'dirty' AND ht.id IS NULL
";
$dirty_rooms = $conn->query($dirty_rooms_sql);

// 2. Fetch all housekeepers
$housekeepers = $conn->query("SELECT id, username FROM users WHERE role = 'housekeeping' AND is_active = 1");

// 3. Fetch all currently pending assignments for today
$assigned_tasks_sql = "
    SELECT r.room_number, u.username as housekeeper_name, ht.task_date 
    FROM housekeeping_tasks ht
    JOIN rooms r ON ht.room_id = r.id
    JOIN users u ON ht.assigned_to_user_id = u.id
    WHERE ht.status = 'pending' AND ht.task_date = CURDATE()
    ORDER BY u.username, r.room_number
";
$assigned_tasks = $conn->query($assigned_tasks_sql);


$title = "Assign Housekeeping Tasks";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: flex-start;">

    <!-- Left Column: Assign New Tasks -->
    <div class="card">
        <h2>📝 Assign New Tasks</h2>
        <p>Assign dirty rooms to available housekeepers for today.</p>

        <?php if ($feedback_message): ?>
            <div class="alert alert-<?= $feedback_type ?>">
                <?= htmlspecialchars($feedback_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin_housekeeping_assign.php">
            <label for="assignee_id" class="form-label">Assign To:</label>
            <select name="assignee_id" id="assignee_id" class="form-select" required>
                <option value="">-- Select a Housekeeper --</option>
                <?php while($hk = $housekeepers->fetch_assoc()): ?>
                    <option value="<?= $hk['id'] ?>"><?= htmlspecialchars($hk['username']) ?></option>
                <?php endwhile; ?>
            </select>

            <label class="form-label mt-30">Unassigned Dirty Rooms:</label>
            <?php if ($dirty_rooms->num_rows > 0): ?>
                <div class="checkbox-grid">
                <?php while($room = $dirty_rooms->fetch_assoc()): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="room_ids[]" value="<?= $room['id'] ?>" id="room_<?= $room['id'] ?>" style="margin-right: 8px;">
                        <label for="room_<?= $room['id'] ?>"> Room <?= htmlspecialchars($room['room_number']) ?></label>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No unassigned dirty rooms available to be tasked.</p>
            <?php endif; ?>

            <button type="submit" name="assign_tasks" class="btn btn-primary mt-30">Assign Selected Rooms</button>
        </form>
    </div>

    <!-- Right Column: View Current Assignments -->
    <div class="card">
        <h2>📋 Today's Assignments</h2>
        <p>A list of all cleaning tasks currently pending for today.</p>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Assigned To</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($assigned_tasks->num_rows > 0): ?>
                        <?php while($task = $assigned_tasks->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($task['room_number']) ?></td>
                                <td><?= htmlspecialchars($task['housekeeper_name']) ?></td>
                                <td><?= htmlspecialchars($task['task_date']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No tasks assigned for today yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>