<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';

// Restrict access to admin role only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// ... (PHP logic from the file is unchanged) ...

$title = "Notifications & Alerts";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 class="text-center">Notifications & Alerts Management</h1>

    <?php if (isset($message) && $message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <div class="notification-section">
        <h2>Current Room Inventory</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Total Rooms</th>
                    <th>Available</th>
                    <th>Booked</th>
                    <th>Maintenance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $inventory_result->fetch_assoc()):
                    $available = $row['available_rooms'];
                    $status_class = '';
                    $status_text = 'Good';
                    if ($available <= 1) {
                        $status_class = 'low-inventory';
                        $status_text = 'Critical';
                    } elseif ($available <= 2) {
                        $status_class = 'warning-inventory';
                        $status_text = 'Low';
                    }
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= $row['total_rooms'] ?></td>
                        <td class="<?= $status_class ?>"><?= $available ?></td>
                        <td><?= $row['booked_rooms'] ?></td>
                        <td><?= $row['maintenance_rooms'] ?></td>
                        <td class="<?= $status_class ?>"><?= $status_text ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <form method="POST" class="mt-30">
            <label class="form-label">Alert Threshold:</label>
            <input type="number" name="threshold" value="2" min="1" max="10" class="form-input" style="width: auto; display: inline-block;">
            <button type="submit" name="check_inventory" class="btn btn-danger">
                Check Inventory & Send Alerts
            </button>
        </form>
    </div>

    <div class="notification-section">
        <h2>Upcoming Check-ins (Next 7 Days)</h2>
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Number of Check-ins</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($upcoming_result->num_rows > 0): ?>
                    <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('l, F j, Y', strtotime($row['check_in'])) ?></td>
                            <td><?= $row['checkins_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No upcoming check-ins in the next 7 days.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="notification-section">
        <h2>Automated Processes</h2>
        <p>The system automatically handles booking reminders and inventory alerts daily at 9:00 AM.</p>
        <p>You can manually trigger these processes here for immediate execution.</p>
        <form method="POST" class="mt-30">
            <button type="submit" name="run_daily_automation" class="btn btn-primary">
                Run Daily Automation Now
            </button>
        </form>
    </div>

    <div class="text-center mt-30">
        <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>