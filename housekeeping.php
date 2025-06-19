<?php
session_start();
require_once 'includes/header.php';
require_once 'php/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'housekeeping'])) {
    header("Location: index.php");
    exit;
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = (int) $_POST['room_id'];
    $new_status = $_POST['housekeeping_status'];

    $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $room_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
?>

<h2 style="color: #F7B223;">🧹 Housekeeping Status Manager</h2>

<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background-color: #081E3F; color: white;">
            <th style="padding: 12px; border: 1px solid #ddd;">Room Number</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Room Type</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Current Status</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Update</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $rooms->fetch_assoc()): ?>
        <tr style="background-color: #fff; color: #081E3F;">
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_number']) ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_type']) ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['housekeeping_status'] ?? 'unknown' ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;">
                <form method="post" style="display: flex; gap: 10px;">
                    <input type="hidden" name="room_id" value="<?= $row['id'] ?>">
                    <select name="housekeeping_status" required>
                        <option value="clean" <?= $row['housekeeping_status'] === 'clean' ? 'selected' : '' ?>>Clean</option>
                        <option value="dirty" <?= $row['housekeeping_status'] === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                        <option value="maintenance" <?= $row['housekeeping_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                    <button type="submit" name="update_status" style="padding: 5px 10px; background: #F7B223; border: none; color: white;">Update</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>