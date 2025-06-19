<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';
require_once 'includes/header.php';

$title = "Reports";

// Date filter
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// === Revenue ===
$revenue_stmt = $conn->prepare("
    SELECT SUM(total_price) AS total_revenue
    FROM bookings
    WHERE check_in >= ? AND check_out <= ? AND status != 'cancelled'
");
$revenue_stmt->bind_param("ss", $start_date, $end_date);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$revenue_stmt->close();

// === Total Bookings ===
$bookings_stmt = $conn->prepare("
    SELECT COUNT(*) AS total_bookings
    FROM bookings
    WHERE check_in >= ? AND check_out <= ? AND status != 'cancelled'
");
$bookings_stmt->bind_param("ss", $start_date, $end_date);
$bookings_stmt->execute();
$total_bookings = $bookings_stmt->get_result()->fetch_assoc()['total_bookings'];
$bookings_stmt->close();

// === Occupancy Rate ===
$total_rooms = $conn->query("SELECT COUNT(*) AS total FROM rooms")->fetch_assoc()['total'];
$days = max(1, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24));
$max_occupancy = $total_rooms * $days;

$occupancy_stmt = $conn->prepare("
    SELECT SUM(DATEDIFF(check_out, check_in)) AS occupied_nights
    FROM bookings
    WHERE check_in >= ? AND check_out <= ? AND status = 'checked-out'
");
$occupancy_stmt->bind_param("ss", $start_date, $end_date);
$occupancy_stmt->execute();
$occupied_nights = $occupancy_stmt->get_result()->fetch_assoc()['occupied_nights'] ?? 0;
$occupancy_stmt->close();
$occupancy_rate = $max_occupancy > 0 ? round(($occupied_nights / $max_occupancy) * 100, 2) : 0;

// === Top Room Types ===
$top_rooms = $conn->prepare("
    SELECT room_type, COUNT(*) AS bookings, SUM(total_price) AS revenue
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.check_in >= ? AND b.check_out <= ? AND b.status != 'cancelled'
    GROUP BY room_type
    ORDER BY revenue DESC
    LIMIT 5
");
$top_rooms->bind_param("ss", $start_date, $end_date);
$top_rooms->execute();
$room_type_results = $top_rooms->get_result();

// === Top Guests ===
$top_guests = $conn->prepare("
    SELECT u.full_name, u.email, SUM(b.total_price) AS total_spent, COUNT(*) AS bookings
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.check_in >= ? AND b.check_out <= ? AND b.status != 'cancelled'
    GROUP BY b.user_id
    ORDER BY total_spent DESC
    LIMIT 5
");
$top_guests->bind_param("ss", $start_date, $end_date);
$top_guests->execute();
$guest_results = $top_guests->get_result();

// === Booking Status Breakdown ===
$status_counts = $conn->query("
    SELECT status, COUNT(*) AS count
    FROM bookings
    WHERE check_in >= '$start_date' AND check_out <= '$end_date'
    GROUP BY status
");

?>

<h2 style="color: #F7B223;">📈 Hotel Reports</h2>

<form method="get" style="margin-bottom: 30px;">
    <label style="color: #F7B223;">Start Date:</label>
    <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
    <label style="margin-left: 20px; color: #F7B223;">End Date:</label>
    <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
    <button type="submit" style="
        background-color: #F7B223;
        color: #081C3A;
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        margin-left: 20px;
    ">Filter</button>
</form>

<!-- Summary Cards -->
<div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 40px;">
    <div style="flex: 1; min-width: 200px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
        <h3 style="color: #F7B223;">💰 Total Revenue</h3>
        <p style="font-size: 1.8rem;">$<?= number_format($total_revenue, 2) ?></p>
    </div>
    <div style="flex: 1; min-width: 200px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
        <h3 style="color: #F7B223;">📅 Total Bookings</h3>
        <p style="font-size: 1.8rem;"><?= $total_bookings ?></p>
    </div>
    <div style="flex: 1; min-width: 200px; background: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
        <h3 style="color: #F7B223;">🏨 Occupancy Rate</h3>
        <p style="font-size: 1.8rem;"><?= $occupancy_rate ?>%</p>
    </div>
</div>

<!-- Booking Status Breakdown -->
<h3 style="color: #F7B223;">📊 Booking Status Breakdown</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
    <thead>
        <tr style="background-color: #06172D; color: #F7B223;">
            <th style="padding: 10px;">Status</th>
            <th style="padding: 10px;">Count</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $status_counts->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px;"><?= ucfirst($row['status']) ?></td>
                <td style="padding: 10px;"><?= $row['count'] ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Top Room Types -->
<h3 style="color: #F7B223;">🏅 Top Room Types</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 40px;">
    <thead>
        <tr style="background-color: #06172D; color: #F7B223;">
            <th style="padding: 10px;">Room Type</th>
            <th style="padding: 10px;">Bookings</th>
            <th style="padding: 10px;">Revenue</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $room_type_results->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px;"><?= htmlspecialchars($row['room_type']) ?></td>
                <td style="padding: 10px;"><?= $row['bookings'] ?></td>
                <td style="padding: 10px;">$<?= number_format($row['revenue'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Top Guests -->
<h3 style="color: #F7B223;">👑 Top Guests</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 60px;">
    <thead>
        <tr style="background-color: #06172D; color: #F7B223;">
            <th style="padding: 10px;">Name</th>
            <th style="padding: 10px;">Email</th>
            <th style="padding: 10px;">Total Spent</th>
            <th style="padding: 10px;">Bookings</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($guest = $guest_results->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px;"><?= htmlspecialchars($guest['full_name']) ?></td>
                <td style="padding: 10px;"><?= htmlspecialchars($guest['email']) ?></td>
                <td style="padding: 10px;">$<?= number_format($guest['total_spent'], 2) ?></td>
                <td style="padding: 10px;"><?= $guest['bookings'] ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>
