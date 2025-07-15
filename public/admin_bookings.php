<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Manage Bookings";
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT b.id AS booking_id, u.username, r.room_number, r.room_type, r.housekeeping_status, b.check_in, b.check_out, b.total_price, b.status FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id ORDER BY b.check_in DESC";
$result = $conn->query($sql);
?>

<h2>Manage Bookings</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>Booking ID</th>
            <th>Guest Username</th>
            <th>Room #</th>
            <th>Room Type</th>
            <th>Check-In</th>
            <th>Check-Out</th>
            <th>Total ($)</th>
            <th>Status</th>
            <th>Housekeeping</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()):
            $status_class = '';
            if ($row['status'] === 'cancelled') {
                $status_class = 'status-cancelled';
            } elseif ($row['status'] === 'checked-in') {
                $status_class = 'status-checked-in';
            }
        ?>
            <tr>
                <td>
                    <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>">
                        #<?= $row['booking_id'] ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>Room <?= htmlspecialchars($row['room_number']) ?></td>
                <td><?= htmlspecialchars($row['room_type']) ?></td>
                <td><?= htmlspecialchars($row['check_in']) ?></td>
                <td><?= htmlspecialchars($row['check_out']) ?></td>
                <td>$<?= number_format($row['total_price'], 2) ?></td>
                <td class="text-capitalize <?= $status_class ?>">
                    <?= htmlspecialchars($row['status'] ?? 'N/A') ?>
                </td>
                <td class="text-capitalize">
                    <?= htmlspecialchars($row['housekeeping_status'] ?? 'unknown') ?>
                </td>
                <td>
                    <a class="btn-link-style" href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>">View Details</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>