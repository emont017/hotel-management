<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

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
    }
    header("Location: /housekeeping.php");
    exit;
}

// Fetch data for the form
$dirty_rooms = $conn->query("SELECT id, room_number FROM rooms WHERE housekeeping_status = 'dirty'");
$housekeepers = $conn->query("SELECT id, username FROM users WHERE role = 'housekeeping'");

$title = "Assign Housekeeping Tasks";
require_once __DIR__ . '/../includes/header.php';
?>

<h2>📝 Assign Housekeeping Tasks</h2>
<p>Assign dirty rooms to available housekeepers for today.</p>

<div class="form-container">
    <form method="POST" action="admin_housekeeping_assign.php">
        <label for="assignee_id" class="form-label">Assign To:</label>
        <select name="assignee_id" id="assignee_id" class="form-select" required>
            <option value="">-- Select a Housekeeper --</option>
            <?php while($hk = $housekeepers->fetch_assoc()): ?>
                <option value="<?= $hk['id'] ?>"><?= htmlspecialchars($hk['username']) ?></option>
            <?php endwhile; ?>
        </select>

        <label class="form-label mt-30">Rooms to Clean:</label>
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
            <p>No rooms are currently marked as dirty.</p>
        <?php endif; ?>

        <button type="submit" name="assign_tasks" class="btn btn-primary mt-30">Assign Tasks</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>