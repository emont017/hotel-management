<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: index.php");
    exit;
}

$title = "Hotel Reports";
require_once __DIR__ . '/../includes/header.php';

// --- Report Generation Logic ---
$start_date_str = $_GET['start_date'] ?? date('Y-m-01');
$end_date_str = $_GET['end_date'] ?? date('Y-m-t');

// 1. Total Revenue
$revenue_stmt = $conn->prepare("SELECT SUM(total_price) AS total_revenue FROM bookings WHERE status IN ('checked-in', 'checked-out', 'confirmed') AND check_in BETWEEN ? AND ?");
$revenue_stmt->bind_param("ss", $start_date_str, $end_date_str);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$revenue_stmt->close();

// 2. Total Bookings
$bookings_stmt = $conn->prepare("SELECT COUNT(id) AS total_bookings FROM bookings WHERE status != 'cancelled' AND check_in BETWEEN ? AND ?");
$bookings_stmt->bind_param("ss", $start_date_str, $end_date_str);
$bookings_stmt->execute();
$total_bookings = $bookings_stmt->get_result()->fetch_assoc()['total_bookings'] ?? 0;
$bookings_stmt->close();

// 3. Occupancy Rate
$total_rooms_query = $conn->query("SELECT COUNT(*) AS total FROM rooms");
$total_rooms = $total_rooms_query ? $total_rooms_query->fetch_assoc()['total'] : 0;

// Fix for "pass by reference" notice
$start_date_obj = new DateTime($start_date_str);
$end_date_obj = new DateTime($end_date_str);
$days_in_range = $start_date_obj->diff($end_date_obj)->days + 1;

$total_room_nights_available = $total_rooms * $days_in_range;
$occupied_nights_stmt = $conn->prepare("SELECT SUM(DATEDIFF(LEAST(check_out, ?), GREATEST(check_in, ?))) AS occupied_nights FROM bookings WHERE status != 'cancelled' AND check_in <= ? AND check_out > ?");
$occupied_nights_stmt->bind_param("ssss", $end_date_str, $start_date_str, $end_date_str, $start_date_str);
$occupied_nights_stmt->execute();
$occupied_room_nights = $occupied_nights_stmt->get_result()->fetch_assoc()['occupied_nights'] ?? 0;
$occupied_nights_stmt->close();
$occupancy_rate = ($total_room_nights_available > 0) ? round(($occupied_room_nights / $total_room_nights_available) * 100, 2) : 0;

// 4. Booking Status Breakdown
$status_stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM bookings WHERE check_in BETWEEN ? AND ? GROUP BY status");
$status_stmt->bind_param("ss", $start_date_str, $end_date_str);
$status_stmt->execute();
$status_counts = $status_stmt->get_result();
$status_stmt->close();

// 5. Top Room Types by Revenue
$top_rooms_stmt = $conn->prepare("SELECT r.room_type, COUNT(b.id) AS bookings_count, SUM(b.total_price) AS revenue FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.status != 'cancelled' AND b.check_in BETWEEN ? AND ? GROUP BY r.room_type ORDER BY revenue DESC LIMIT 5");
$top_rooms_stmt->bind_param("ss", $start_date_str, $end_date_str);
$top_rooms_stmt->execute();
$top_rooms_result = $top_rooms_stmt->get_result();
$top_rooms_stmt->close();
?>

<h2>Hotel Reports</h2>

<form method="get" class="card mb-20">
    <div style="display:flex; align-items:flex-end; gap:20px; flex-wrap:wrap;">
        <div>
            <label class="form-label">Start Date:</label>
            <input type="date" name="start_date" class="form-input" value="<?= htmlspecialchars($start_date_str) ?>" required>
        </div>
        <div>
            <label class="form-label">End Date:</label>
            <input type="date" name="end_date" class="form-input" value="<?= htmlspecialchars($end_date_str) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Generate Report</button>
    </div>
</form>

<div class="dashboard-grid mb-20">
    <div class="stat-card">
        <div class="label">Total Revenue</div>
        <div class="value">$<?= number_format($total_revenue, 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Total Bookings</div>
        <div class="value"><?= $total_bookings ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Occupancy Rate</div>
        <div class="value"><?= $occupancy_rate ?>%</div>
    </div>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 40px;">
    <div style="flex: 1; min-width: 400px;">
        <h3>Booking Status Breakdown</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($status_counts && $status_counts->num_rows > 0): ?>
                    <?php while ($row = $status_counts->fetch_assoc()): ?>
                    <tr><td class="text-capitalize"><?= htmlspecialchars($row['status']) ?></td><td><?= $row['count'] ?></td></tr>
                    <?php endwhile; ?>
                <?php else: ?>
                     <tr><td colspan="2" class="text-center">No data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="flex: 1; min-width: 400px;">
        <h3>Top Room Types by Revenue</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Bookings</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                 <?php if ($top_rooms_result && $top_rooms_result->num_rows > 0): ?>
                    <?php while ($row = $top_rooms_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= $row['bookings_count'] ?></td>
                        <td>$<?= number_format($row['revenue'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                     <tr><td colspan="3" class="text-center">No data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>