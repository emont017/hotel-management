<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';
$title = "Manage Bookings";
require_once 'includes/header.php';

$sql = "
SELECT b.id AS booking_id, u.username, r.room_number, r.room_type, r.housekeeping_status, b.check_in, b.check_out, b.total_price, b.status
FROM bookings b
JOIN users u ON b.user_id = u.id
JOIN rooms r ON b.room_id = r.id
ORDER BY b.check_in DESC
";
$result = $conn->query($sql);
?>

<h2 style="color: #F7B223;">📅 Manage Bookings</h2>

<table style="width: 100%; border-collapse: collapse; margin-top: 25px; color: #fff;">
    <thead>
        <tr style="background-color: rgba(247, 178, 35, 0.2); color: #F7B223;">
            <th style="padding: 12px;">Booking ID</th>
            <th style="padding: 12px;">Guest Username</th>
            <th style="padding: 12px;">Room #</th>
            <th style="padding: 12px;">Room Type</th>
            <th style="padding: 12px;">Check-In</th>
            <th style="padding: 12px;">Check-Out</th>
            <th style="padding: 12px;">Total ($)</th>
            <th style="padding: 12px;">Status</th>
            <th style="padding: 12px;">Housekeeping</th>
            <th style="padding: 12px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid #333;">
                <td style="padding: 12px;">
                    <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #F7B223; font-weight: bold; text-decoration: none;">
                        #<?= $row['booking_id'] ?>
                    </a>
                </td>
                <td style="padding: 12px;"><?= htmlspecialchars($row['username']) ?></td>
                <td style="padding: 12px;">Room <?= htmlspecialchars($row['room_number']) ?></td>
                <td style="padding: 12px;"><?= htmlspecialchars($row['room_type']) ?></td>
                <td style="padding: 12px;"><?= htmlspecialchars($row['check_in']) ?></td>
                <td style="padding: 12px;"><?= htmlspecialchars($row['check_out']) ?></td>
                <td style="padding: 12px;">$<?= number_format($row['total_price'], 2) ?></td>
                <td style="padding: 12px; text-transform: capitalize; color: <?= $row['status'] === 'cancelled' ? 'red' : ($row['status'] === 'checked-in' ? '#0f0' : '#F7B223') ?>;">
                    <?= htmlspecialchars($row['status'] ?? 'N/A') ?>
                </td>
                <td style="padding: 12px; text-transform: capitalize;">
                    <?= htmlspecialchars($row['housekeeping_status'] ?? 'unknown') ?>
                </td>
                <td style="padding: 12px;">
                    <?php if ($row['status'] === 'confirmed'): ?>
                        <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #0af;">Check In</a>
                    <?php elseif ($row['status'] === 'checked-in'): ?>
                        <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #0af;">Check Out</a>
                    <?php else: ?>
                        <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" style="color: #888;">View</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>