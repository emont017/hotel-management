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

// --- Form Handling ---

// Handle Housekeeping Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_housekeeping_status'])) {
    $room_id = (int) $_POST['room_id'];
    $new_status = $_POST['housekeeping_status'];
    $user_id = (int) $_SESSION['user_id'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
        $stmt1->bind_param("si", $new_status, $room_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("INSERT INTO housekeeping (room_id, status, updated_by) VALUES (?, ?, ?)");
        $stmt2->bind_param("isi", $room_id, $new_status, $user_id);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        // error_log("Failed to update housekeeping status: " . $exception->getMessage());
    }
    header("Location: housekeeping.php");
    exit;
}

// Handle Room Operational Status Update (e.g., setting to Maintenance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_status'])) {
    $room_id = (int) $_POST['room_id'];
    $new_status = $_POST['room_status'];

    $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $room_id);
    $stmt->execute();
    $stmt->close();

    // If a room is set back to 'available', its housekeeping status should be dirty by default
    if ($new_status === 'available') {
        $stmt_hk = $conn->prepare("UPDATE rooms SET housekeeping_status = 'vacant_dirty' WHERE id = ?");
        $stmt_hk->bind_param("i", $room_id);
        $stmt_hk->execute();
        $stmt_hk->close();
    }


    header("Location: housekeeping.php");
    exit;
}


// --- Data Fetching ---
$all_rooms = [];
$query = "
    SELECT 
        r.id, r.room_number, r.room_type, r.housekeeping_status, r.status as room_status,
        u.full_name as guest_name
    FROM rooms r
    LEFT JOIN (
        SELECT room_id, user_id FROM bookings 
        WHERE status = 'checked-in' AND CURDATE() BETWEEN check_in AND DATE_SUB(check_out, INTERVAL 1 DAY)
    ) AS current_bookings ON r.id = current_bookings.room_id
    LEFT JOIN users u ON current_bookings.user_id = u.id
    ORDER BY r.room_number ASC
";
$rooms_result = $conn->query($query);

$rooms_by_status = [
    'occupied' => [],
    'vacant_dirty' => [],
    'vacant_clean' => [],
    'maintenance' => []
];

if ($rooms_result) {
    while ($room = $rooms_result->fetch_assoc()) {
        $all_rooms[] = $room; // Store all rooms for the master list
        $status_key = $room['room_status'] === 'maintenance' ? 'maintenance' : $room['housekeeping_status'];
        if (array_key_exists($status_key, $rooms_by_status)) {
            $rooms_by_status[$status_key][] = $room;
        }
    }
}
?>

<style>
    .housekeeping-container { display: flex; flex-direction: column; gap: 30px; }
    .status-section { background-color: #081E3F; padding: 20px; border-radius: 10px; border: 1px solid #F7B223; }
    .status-section h3 { margin-top: 0; color: #F7B223; border-bottom: 2px solid #F7B223; padding-bottom: 10px; }
    .room-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; }
    .room-card { background-color: #122C55; padding: 15px; border-radius: 8px; color: #fff; display: flex; flex-direction: column; gap: 15px; }
    .room-card .room-info { display: flex; justify-content: space-between; align-items: flex-start; }
    .room-card .room-number { font-size: 1.5rem; font-weight: bold; }
    .room-card .guest-name { font-size: 0.9rem; font-style: italic; color: #ccc; }
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background-color: #081E3F; padding: 20px; border-radius: 10px; text-align: center; border: 1px solid #F7B223; }
    .stat-card .value { font-size: 2rem; font-weight: bold; color: #F7B223; }
    .stat-card .label { font-size: 1rem; color: #fff; }
    .update-form { display: flex; gap: 10px; align-items: center; }
    .update-form select { flex-grow: 1; padding: 8px; border-radius: 6px; border: 1px solid #ccc; background-color: #fff; color: #000; }
    .update-form button { padding: 8px 12px; background: #F7B223; border: none; color: #081C3A; border-radius: 6px; font-weight: bold; cursor: pointer; white-space: nowrap; }
    .master-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .master-table th, .master-table td { padding: 10px; text-align: left; border-bottom: 1px solid #122C55; }
</style>

<h2 style="color: #F7B223;">🧹 Housekeeping Dashboard</h2>

<!-- Quick Stats -->
<div class="stat-grid">
    <div class="stat-card"><div class="value"><?= count($rooms_by_status['occupied']) ?></div><div class="label">Occupied Rooms</div></div>
    <div class="stat-card"><div class="value"><?= count($rooms_by_status['vacant_dirty']) ?></div><div class="label">Requires Cleaning</div></div>
    <div class="stat-card"><div class="value"><?= count($rooms_by_status['vacant_clean']) ?></div><div class="label">Clean & Ready</div></div>
    <div class="stat-card"><div class="value"><?= count($rooms_by_status['maintenance']) ?></div><div class="label">Under Maintenance</div></div>
</div>

<div class="housekeeping-container">
    <!-- Occupied Rooms, Vacant (Dirty), Vacant (Clean) sections remain the same... -->

    <!-- Occupied Rooms Section -->
    <div class="status-section">
        <h3>Occupied</h3>
        <div class="room-grid">
            <?php if (!empty($rooms_by_status['occupied'])): foreach($rooms_by_status['occupied'] as $room): ?>
                <div class="room-card">
                    <div class="room-info">
                        <div><span class="room-number">Room <?= htmlspecialchars($room['room_number']) ?></span><div class="guest-name"><?= htmlspecialchars($room['guest_name'] ?? 'Guest') ?></div></div>
                        <span><?= htmlspecialchars($room['room_type']) ?></span>
                    </div>
                    <form method="post" class="update-form"><input type="hidden" name="room_id" value="<?= $room['id'] ?>"><select name="housekeeping_status" required><option value="vacant_dirty">Set to Vacant (Dirty)</option><option value="occupied" selected>Occupied</option></select><button type="submit" name="update_housekeeping_status">Update</button></form>
                </div>
            <?php endforeach; else: ?><p>No occupied rooms.</p><?php endif; ?>
        </div>
    </div>

    <!-- Vacant (Dirty) Rooms Section -->
    <div class="status-section">
        <h3>Vacant (Dirty)</h3>
        <div class="room-grid">
            <?php if (!empty($rooms_by_status['vacant_dirty'])): foreach($rooms_by_status['vacant_dirty'] as $room): ?>
                <div class="room-card">
                    <div class="room-info"><span class="room-number">Room <?= htmlspecialchars($room['room_number']) ?></span><span><?= htmlspecialchars($room['room_type']) ?></span></div>
                    <form method="post" class="update-form"><input type="hidden" name="room_id" value="<?= $room['id'] ?>"><select name="housekeeping_status" required><option value="vacant_clean">Set to Vacant (Clean)</option><option value="vacant_dirty" selected>Vacant (Dirty)</option></select><button type="submit" name="update_housekeeping_status">Update Housekeeping</button></form>
                    <form method="post" class="update-form"><input type="hidden" name="room_id" value="<?= $room['id'] ?>"><select name="room_status" required><option value="maintenance">Set to Maintenance</option></select><button type="submit" name="update_room_status">Update Room Status</button></form>
                </div>
            <?php endforeach; else: ?><p>No rooms require cleaning.</p><?php endif; ?>
        </div>
    </div>
    
    <!-- Vacant (Clean) Rooms Section -->
    <div class="status-section">
        <h3>Vacant (Clean)</h3>
        <div class="room-grid">
             <?php if (!empty($rooms_by_status['vacant_clean'])): foreach($rooms_by_status['vacant_clean'] as $room): ?>
                <div class="room-card">
                    <div class="room-info"><span class="room-number">Room <?= htmlspecialchars($room['room_number']) ?></span><span><?= htmlspecialchars($room['room_type']) ?></span></div>
                    <p style="margin:0; text-align: center; color: #2ecc71; font-weight: bold;">Ready for Guest</p>
                </div>
            <?php endforeach; else: ?><p>No clean rooms available.</p><?php endif; ?>
        </div>
    </div>
    
    <!-- Maintenance Rooms Section -->
    <div class="status-section">
        <h3>Under Maintenance</h3>
        <div class="room-grid">
             <?php if (!empty($rooms_by_status['maintenance'])): foreach($rooms_by_status['maintenance'] as $room): ?>
                <div class="room-card">
                    <div class="room-info"><span class="room-number">Room <?= htmlspecialchars($room['room_number']) ?></span><span><?= htmlspecialchars($room['room_type']) ?></span></div>
                    <form method="post" class="update-form"><input type="hidden" name="room_id" value="<?= $room['id'] ?>"><select name="room_status" required><option value="available">Set to Available</option></select><button type="submit" name="update_room_status">Update Room Status</button></form>
                </div>
            <?php endforeach; else: ?><p>No rooms are under maintenance.</p><?php endif; ?>
        </div>
    </div>

    <!-- NEW: Master Control Panel -->
    <div class="status-section">
        <h3>Master Room Control (All Rooms)</h3>
        <p style="color: #ccc; font-style: italic;">For administrative use: override any room's status from here.</p>
        <div style="overflow-x: auto;">
            <table class="master-table">
                <thead><tr><th>Room #</th><th>Current HK Status</th><th>Current Room Status</th><th>Update Housekeeping</th><th>Update Room Status</th></tr></thead>
                <tbody>
                    <?php foreach($all_rooms as $room): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($room['room_number']) ?></strong></td>
                            <td><?= ucwords(str_replace('_', ' ', htmlspecialchars($room['housekeeping_status']))) ?></td>
                            <td><?= ucfirst(htmlspecialchars($room['room_status'])) ?></td>
                            <td>
                                <form method="post" class="update-form">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <select name="housekeeping_status">
                                        <option value="vacant_clean" <?= $room['housekeeping_status'] === 'vacant_clean' ? 'selected' : '' ?>>Vacant (Clean)</option>
                                        <option value="vacant_dirty" <?= $room['housekeeping_status'] === 'vacant_dirty' ? 'selected' : '' ?>>Vacant (Dirty)</option>
                                        <option value="occupied" <?= $room['housekeeping_status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                                    </select>
                                    <button type="submit" name="update_housekeeping_status">Set</button>
                                </form>
                            </td>
                            <td>
                                <form method="post" class="update-form">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <select name="room_status">
                                        <option value="available" <?= $room['room_status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                        <option value="maintenance" <?= $room['room_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                                    <button type="submit" name="update_room_status">Set</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
