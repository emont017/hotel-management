<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

$title = "Manage Bookings";
require_once __DIR__ . '/../includes/header.php';

// UPDATED SQL: Joined the 'folios' table to get the live balance.
// Using COALESCE to show the original total_price if a folio hasn't been created yet.
$sql = "
    SELECT
        b.id as booking_id,
        u.full_name,
        r.room_number,
        r.housekeeping_status,
        b.check_in,
        b.check_out,
        COALESCE(f.balance, b.total_price, 0) as balance,
        b.status
    FROM
        bookings b
    JOIN
        users u ON b.user_id = u.id
    JOIN
        rooms r ON b.room_id = r.id
    LEFT JOIN
        folios f ON b.id = f.booking_id
    ORDER BY
        b.check_in DESC
";

$bookings_result = $conn->query($sql);
?>

<h2>Manage All Bookings</h2>
<p>View, edit, and manage all guest reservations in the system.</p>

<div style="overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Guest</th>
                <th>Room</th>
                <th>Room Status</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Total</th>
                <th>Booking Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                <?php while ($row = $bookings_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['booking_id'] ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['room_number']) ?></td>
                        <td class="text-capitalize"><?= htmlspecialchars($row['housekeeping_status']) ?></td>
                        <td><?= htmlspecialchars($row['check_in']) ?></td>
                        <td><?= htmlspecialchars($row['check_out']) ?></td>
                        
                        <!-- UPDATED: This now displays the live folio balance, formatted to two decimal places. -->
                        <td>$<?= number_format($row['balance'], 2) ?></td>
                        
                        <td class="text-capitalize"><?= htmlspecialchars($row['status']) ?></td>
                        <td>
                            <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" class="btn-link-style">View Details</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No bookings found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
