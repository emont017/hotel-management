<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/header.php';
require_once 'php/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'housekeeping'])) {
    header("Location: index.php");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $room_id = (int) $_POST['room_id'];
    $new_status = $_POST['housekeeping_status'];
    $user_id = (int) $_SESSION['user_id'];

    // Use a transaction to ensure data integrity across both tables
    $conn->begin_transaction();
    try {
        // 1. Update the primary status in the 'rooms' table
        $stmt1 = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
        $stmt1->bind_param("si", $new_status, $room_id);
        $stmt1->execute();
        $stmt1->close();

        // 2. Create a log entry in the 'housekeeping' table
        $stmt2 = $conn->prepare("INSERT INTO housekeeping (room_id, status, updated_by) VALUES (?, ?, ?)");
        $stmt2->bind_param("isi", $room_id, $new_status, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        // Optionally, you can set an error message to display to the user
        // error_log("Failed to update housekeeping status: " . $exception->getMessage());
    }
}

// Fetch all rooms to display their status
$rooms = $conn->query("SELECT id, room_number, room_type, housekeeping_status FROM rooms ORDER BY room_number ASC");
?>

<style>
    /* Add some color-coding for status text */
    .status-vacant-clean { color: #2ecc71; font-weight: bold; }
    .status-vacant-dirty { color: #f1c40f; font-weight: bold; }
    .status-occupied { color: #e74c3c; font-weight: bold; }
    .status-maintenance { color: #95a5a6; font-weight: bold; }
</style>

<h2 style="color: #F7B223;">🧹 Housekeeping Status Manager</h2>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background-color: #081E3F; color: white; text-align: left;">
                <th style="padding: 12px; border: 1px solid #ddd;">Room Number</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Room Type</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Current Status</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Update Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rooms && $rooms->num_rows > 0): ?>
                <?php $i = 0; while ($row = $rooms->fetch_assoc()): ?>
                <?php $bg = ($i++ % 2 === 0) ? "#f8f9fa" : "#ffffff"; ?>
                <tr style="background-color: <?= $bg ?>; color: #081E3F;">
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_number']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_type']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <?php 
                            $status_class = 'status-' . str_replace('_', '-', $row['housekeeping_status']);
                            $status_text = ucwords(str_replace('_', ' ', $row['housekeeping_status']));
                        ?>
                        <span class="<?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>
                    </td>
                    <td style="padding: 10px; border: 1px solid #ddd;">
                        <form method="post" style="display: flex; gap: 10px; align-items: center;">
                            <input type="hidden" name="room_id" value="<?= $row['id'] ?>">
                            <select name="housekeeping_status" required style="padding: 8px; border-radius: 6px; border: 1px solid #ccc;">
                                <option value="vacant_clean" <?= $row['housekeeping_status'] === 'vacant_clean' ? 'selected' : '' ?>>Vacant (Clean)</option>
                                <option value="vacant_dirty" <?= $row['housekeeping_status'] === 'vacant_dirty' ? 'selected' : '' ?>>Vacant (Dirty)</option>
                                <option value="occupied" <?= $row['housekeeping_status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                <option value="maintenance" <?= $row['housekeeping_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                            <button type="submit" name="update_status" style="padding: 8px 12px; background: #F7B223; border: none; color: #081C3A; border-radius: 6px; font-weight: bold; cursor: pointer;">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr style="background-color: #f8f9fa; color: #081E3F;">
                    <td colspan="4" style="padding: 10px; border: 1px solid #ddd; text-align: center;">No rooms found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>