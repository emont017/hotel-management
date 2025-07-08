<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

$title = "Hotel Dashboard";
require_once __DIR__ . '/../includes/header.php';

// --- Enhanced Dashboard Data Fetching ---
$today = date('Y-m-d');
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// 1. Core Metrics
$stmt_arrivals = $conn->prepare("SELECT COUNT(id) as count FROM bookings WHERE check_in = ? AND status = 'confirmed'");
$stmt_arrivals->bind_param("s", $today);
$stmt_arrivals->execute();
$arrivals_today = $stmt_arrivals->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_arrivals->close();

$stmt_departures = $conn->prepare("SELECT COUNT(id) as count FROM bookings WHERE check_out = ? AND status = 'checked-in'");
$stmt_departures->bind_param("s", $today);
$stmt_departures->execute();
$departures_today = $stmt_departures->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_departures->close();

$rooms_occupied_query = $conn->query("SELECT COUNT(id) as count FROM bookings WHERE status = 'checked-in'");
$rooms_occupied = $rooms_occupied_query->fetch_assoc()['count'] ?? 0;

$total_rooms_query = $conn->query("SELECT COUNT(id) as count FROM rooms");
$total_rooms = $total_rooms_query->fetch_assoc()['count'] ?? 1; // Avoid division by zero

// 2. Financial KPIs
$revenue_today_query = $conn->query("SELECT SUM(total_price) as revenue FROM bookings WHERE status IN ('checked-in', 'checked-out') AND DATE(created_at) = '$today'");
$revenue_today = $revenue_today_query->fetch_assoc()['revenue'] ?? 0;

$adr = ($rooms_occupied > 0) ? $revenue_today / $rooms_occupied : 0; // Average Daily Rate
$revpar = $revenue_today / $total_rooms; // Revenue Per Available Room

// 3. Occupancy Rate
$occupancy_rate = ($total_rooms > 0) ? ($rooms_occupied / $total_rooms) * 100 : 0;

// 4. Action Lists
$arrivals_list_stmt = $conn->prepare("SELECT b.id as booking_id, u.full_name, r.room_number FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.check_in = ? AND b.status = 'confirmed' ORDER BY u.full_name ASC");
$arrivals_list_stmt->bind_param("s", $today);
$arrivals_list_stmt->execute();
$arrivals_list = $arrivals_list_stmt->get_result();

$departures_list_stmt = $conn->prepare("SELECT b.id as booking_id, u.full_name, r.room_number FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.check_out = ? AND b.status = 'checked-in' ORDER BY u.full_name ASC");
$departures_list_stmt->bind_param("s", $today);
$departures_list_stmt->execute();
$departures_list = $departures_list_stmt->get_result();
?>

<div class="dashboard-header">
    <h1>Welcome, <?= $username ?>!</h1>
    <p>Here is your hotel's operational summary for <?= date('l, F j, Y') ?>.</p>
</div>

<!-- KPI Grid -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="icon">🏨</div>
        <div class="info">
            <div class="value"><?= number_format($occupancy_rate, 1) ?>%</div>
            <div class="label">Occupancy</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="icon">💲</div>
        <div class="info">
            <div class="value">$<?= number_format($adr, 2) ?></div>
            <div class="label">Avg. Daily Rate (ADR)</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="icon">📈</div>
        <div class="info">
            <div class="value">$<?= number_format($revpar, 2) ?></div>
            <div class="label">Revenue / Room (RevPAR)</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="icon">💰</div>
        <div class="info">
            <div class="value"><?= $rooms_occupied ?></div>
            <div class="label">Rooms Occupied</div>
        </div>
    </div>
</div>

<!-- Main Dashboard Columns -->
<div class="dashboard-columns">
    <div class="main-column">
        <div class="card">
            <h3>Today's Arrivals (<?= $arrivals_list->num_rows ?>)</h3>
            <table class="data-table">
                <thead><tr><th>Guest</th><th>Room</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if ($arrivals_list->num_rows > 0): ?>
                        <?php while($row = $arrivals_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                                <td><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" class="btn-link-style">View Details</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No arrivals scheduled for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="side-column">
        <div class="card">
            <h3>Today's Departures (<?= $departures_list->num_rows ?>)</h3>
            <table class="data-table">
                <thead><tr><th>Guest</th><th>Room</th></tr></thead>
                <tbody>
                    <?php if ($departures_list->num_rows > 0): ?>
                        <?php while($row = $departures_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2">No departures scheduled.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>