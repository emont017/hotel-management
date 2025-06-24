<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: index.php");
    exit;
}

$title = "Hotel Dashboard";
require_once 'includes/header.php';
require_once 'php/db.php';

// --- Dashboard Data Fetching ---
$today = date('Y-m-d');

// 1. "At a Glance" Metrics for Today
$arrivals_today = $conn->query("SELECT COUNT(id) as count FROM bookings WHERE check_in = '$today' AND status = 'confirmed'")->fetch_assoc()['count'] ?? 0;
$departures_today = $conn->query("SELECT COUNT(id) as count FROM bookings WHERE check_out = '$today' AND status = 'checked-in'")->fetch_assoc()['count'] ?? 0;
$rooms_occupied = $conn->query("SELECT COUNT(id) as count FROM rooms WHERE housekeeping_status = 'occupied'")->fetch_assoc()['count'] ?? 0;
$rooms_to_clean = $conn->query("SELECT COUNT(id) as count FROM rooms WHERE housekeeping_status = 'vacant_dirty'")->fetch_assoc()['count'] ?? 0;

// 2. Occupancy Rate
$total_rooms = $conn->query("SELECT COUNT(id) as count FROM rooms")->fetch_assoc()['count'] ?? 0;
$occupancy_rate = ($total_rooms > 0) ? round(($rooms_occupied / $total_rooms) * 100) : 0;


// 3. Lists for Today's Arrivals and Departures
$arrivals_list_stmt = $conn->prepare("
    SELECT b.id as booking_id, u.full_name, r.room_number, r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.check_in = ? AND b.status = 'confirmed'
    ORDER BY u.full_name ASC
");
$arrivals_list_stmt->bind_param("s", $today);
$arrivals_list_stmt->execute();
$arrivals_list = $arrivals_list_stmt->get_result();

$departures_list_stmt = $conn->prepare("
    SELECT b.id as booking_id, u.full_name, r.room_number, r.room_type
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON b.room_id = r.id
    WHERE b.check_out = ? AND b.status = 'checked-in'
    ORDER BY u.full_name ASC
");
$departures_list_stmt->bind_param("s", $today);
$departures_list_stmt->execute();
$departures_list = $departures_list_stmt->get_result();

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    .stat-card {
        background-color: #081E3F;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        border: 1px solid #F7B223;
    }
    .stat-card .value {
        font-size: 2.5rem;
        font-weight: bold;
        color: #F7B223;
    }
    .stat-card .label {
        font-size: 1rem;
        color: #fff;
    }
    .action-list {
        background-color: #081E3F;
        padding: 20px;
        border-radius: 10px;
    }
    .action-list h3 {
        margin-top: 0;
        color: #F7B223;
    }
    .action-list table {
        width: 100%;
        border-collapse: collapse;
    }
    .action-list th, .action-list td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #122C55;
    }
    .action-list th {
        font-size: 0.9rem;
    }
    .action-list td {
        font-size: 0.9rem;
    }
    .occupancy-card .progress-bar {
        width: 100%;
        background-color: #122C55;
        border-radius: 5px;
        height: 25px;
        overflow: hidden;
    }
    .occupancy-card .progress-fill {
        height: 100%;
        background-color: #F7B223;
        text-align: center;
        line-height: 25px;
        color: #081C3A;
        font-weight: bold;
    }
</style>

<h2 style="color: #F7B223;">Welcome, <?= $username ?>!</h2>
<p>Here is your hotel's status for today, <?= date('l, F j, Y') ?>.</p>

<!-- Quick Actions -->
<div style="margin: 20px 0; display: flex; gap: 15px; flex-wrap: wrap;">
    <a href="room_plan.php" style="padding: 10px 20px; background-color: #F7B223; color: #081C3A; text-decoration: none; border-radius: 6px; font-weight: bold;">View Room Plan</a>
    <a href="bookings.php" style="padding: 10px 20px; background-color: #F7B223; color: #081C3A; text-decoration: none; border-radius: 6px; font-weight: bold;">Create New Booking</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="admin_notifications.php" style="padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">📧 Notifications & Alerts</a>
    <?php endif; ?>
</div>

<!-- "At a Glance" Grid -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="value"><?= $arrivals_today ?></div>
        <div class="label">Arrivals Today</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $departures_today ?></div>
        <div class="label">Departures Today</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $rooms_occupied ?></div>
        <div class="label">Rooms Occupied</div>
    </div>
    <div class="stat-card">
        <div class="value"><?= $rooms_to_clean ?></div>
        <div class="label">Rooms to Clean</div>
    </div>
</div>

<!-- Occupancy Card -->
<div class="action-list" style="margin-top: 20px;">
    <h3>Hotel Occupancy</h3>
    <div class="occupancy-card">
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $occupancy_rate ?>%;">
                <?= $occupancy_rate ?>%
            </div>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div style="display: flex; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
    <!-- Today's Arrivals -->
    <div class="action-list" style="flex: 1; min-width: 400px;">
        <h3>Today's Arrivals</h3>
        <table>
            <thead><tr><th>Guest</th><th>Room</th><th>Action</th></tr></thead>
            <tbody>
                <?php if ($arrivals_list->num_rows > 0): ?>
                    <?php while($row = $arrivals_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['room_number']) ?> (<?= htmlspecialchars($row['room_type']) ?>)</td>
                            <td><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #F7B223;">Check-In</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No arrivals scheduled for today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Today's Departures -->
    <div class="action-list" style="flex: 1; min-width: 400px;">
        <h3>Today's Departures</h3>
        <table>
            <thead><tr><th>Guest</th><th>Room</th><th>Action</th></tr></thead>
            <tbody>
                <?php if ($departures_list->num_rows > 0): ?>
                    <?php while($row = $departures_list->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['room_number']) ?> (<?= htmlspecialchars($row['room_type']) ?>)</td>
                            <td><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #F7B223;">Check-Out</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3">No departures scheduled for today.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
