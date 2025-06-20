<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: index.php");
    exit;
}

$title = "Hotel Reports";
require_once 'includes/header.php';
require_once 'php/db.php';

// --- Report Generation Logic ---

// Set default date range to the current month
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// 1. Total Revenue
$revenue_stmt = $conn->prepare("
    SELECT SUM(total_price) AS total_revenue
    FROM bookings
    WHERE status IN ('checked-in', 'checked-out', 'confirmed') AND check_in BETWEEN ? AND ?
");
$revenue_stmt->bind_param("ss", $start_date, $end_date);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$revenue_stmt->close();

// 2. Total Bookings
$bookings_stmt = $conn->prepare("
    SELECT COUNT(id) AS total_bookings
    FROM bookings
    WHERE status != 'cancelled' AND check_in BETWEEN ? AND ?
");
$bookings_stmt->bind_param("ss", $start_date, $end_date);
$bookings_stmt->execute();
$total_bookings = $bookings_stmt->get_result()->fetch_assoc()['total_bookings'] ?? 0;
$bookings_stmt->close();

// 3. Occupancy Rate
$total_rooms_query = $conn->query("SELECT COUNT(*) AS total FROM rooms");
$total_rooms = $total_rooms_query ? $total_rooms_query->fetch_assoc()['total'] : 0;

$days_in_range = (new DateTime($start_date))->diff(new DateTime($end_date))->days + 1;
$total_room_nights_available = $total_rooms * $days_in_range;

$occupied_nights_stmt = $conn->prepare("
    SELECT SUM(DATEDIFF(LEAST(check_out, ?), GREATEST(check_in, ?))) AS occupied_nights
    FROM bookings
    WHERE status != 'cancelled' AND check_in <= ? AND check_out > ?
");
$occupied_nights_stmt->bind_param("ssss", $end_date, $start_date, $end_date, $start_date);
$occupied_nights_stmt->execute();
$occupied_room_nights = $occupied_nights_stmt->get_result()->fetch_assoc()['occupied_nights'] ?? 0;
$occupied_nights_stmt->close();

$occupancy_rate = ($total_room_nights_available > 0) ? round(($occupied_room_nights / $total_room_nights_available) * 100, 2) : 0;


// 4. Booking Status Breakdown
$status_stmt = $conn->prepare("
    SELECT status, COUNT(*) AS count
    FROM bookings
    WHERE check_in BETWEEN ? AND ?
    GROUP BY status
");
$status_stmt->bind_param("ss", $start_date, $end_date);
$status_stmt->execute();
$status_counts = $status_stmt->get_result();
$status_stmt->close();

// 5. Top Room Types by Revenue
$top_rooms_stmt = $conn->prepare("
    SELECT r.room_type, COUNT(b.id) AS bookings_count, SUM(b.total_price) AS revenue
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.status != 'cancelled' AND b.check_in BETWEEN ? AND ?
    GROUP BY r.room_type
    ORDER BY revenue DESC
    LIMIT 5
");
$top_rooms_stmt->bind_param("ss", $start_date, $end_date);
$top_rooms_stmt->execute();
$top_rooms_result = $top_rooms_stmt->get_result();
$top_rooms_stmt->close();

?>

<h2 style="color: #F7B223;">📊 Hotel Reports</h2>

<!-- Date Filter Form -->
<form method="get" style="margin-bottom: 30px; background-color: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px;">
    <label style="color: #F7B223; font-weight: bold;">Start Date:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
    
    <label style="margin-left: 20px; color: #F7B223; font-weight: bold;">End Date:</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
    
    <button type="submit" style="background-color: #F7B223; color: #081C3A; padding: 8px 16px; border: none; border-radius: 6px; font-weight: bold; margin-left: 20px; cursor: pointer;">
        Generate Report
    </button>
</form>

<!-- Summary Cards -->
<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
    <div style="flex: 1; min-width: 220px; background: #06172D; padding: 20px; border-radius: 10px; text-align: center;">
        <h3 style="color: #F7B223; margin-top:0;">💵 Total Revenue</h3>
        <p style="font-size: 2rem; color: #fff; font-weight: bold;">$<?= number_format($total_revenue, 2) ?></p>
    </div>
    <div style="flex: 1; min-width: 220px; background: #06172D; padding: 20px; border-radius: 10px; text-align: center;">
        <h3 style="color: #F7B223; margin-top:0;">📅 Total Bookings</h3>
        <p style="font-size: 2rem; color: #fff; font-weight: bold;"><?= $total_bookings ?></p>
    </div>
    <div style="flex: 1; min-width: 220px; background: #06172D; padding: 20px; border-radius: 10px; text-align: center;">
        <h3 style="color: #F7B223; margin-top:0;">🏨 Occupancy Rate</h3>
        <p style="font-size: 2rem; color: #fff; font-weight: bold;"><?= $occupancy_rate ?>%</p>
    </div>
</div>

<!-- Detailed Reports Tables -->
<div style="display: flex; flex-wrap: wrap; gap: 40px;">

    <!-- Booking Status Table -->
    <div style="flex: 1; min-width: 400px;">
        <h3 style="color: #F7B223;">📋 Booking Status Breakdown</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #081E3F; color: white; text-align: left;">
                    <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($status_counts && $status_counts->num_rows > 0): ?>
                    <?php while ($row = $status_counts->fetch_assoc()): ?>
                    <tr style="background-color: #f8f9fa; color: #081E3F;">
                        <td style="padding: 10px; border: 1px solid #ddd; text-transform: capitalize;"><?= htmlspecialchars($row['status']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['count'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                     <tr style="background-color: #f8f9fa; color: #081E3F;"><td colspan="2" style="padding: 10px; text-align: center;">No data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Room Types Table -->
    <div style="flex: 1; min-width: 400px;">
        <h3 style="color: #F7B223;">⭐ Top Room Types by Revenue</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #081E3F; color: white; text-align: left;">
                    <th style="padding: 10px; border: 1px solid #ddd;">Room Type</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Bookings</th>
                    <th style="padding: 10px; border: 1px solid #ddd;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                 <?php if ($top_rooms_result && $top_rooms_result->num_rows > 0): ?>
                    <?php while ($row = $top_rooms_result->fetch_assoc()): ?>
                    <tr style="background-color: #f8f9fa; color: #081E3F;">
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_type']) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['bookings_count'] ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">$<?= number_format($row['revenue'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                     <tr style="background-color: #f8f9fa; color: #081E3F;"><td colspan="3" style="padding: 10px; text-align: center;">No data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>


<?php require_once 'includes/footer.php'; ?>