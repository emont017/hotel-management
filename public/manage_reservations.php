<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guest') { // Standardized role
    header("Location: index.php");
    exit;
}

$title = "Manage Your Reservations";
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.id, r.room_type, b.check_in, b.check_out, b.total_price FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.user_id = ? ORDER BY b.check_in DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2 class="text-center mb-20">📅 Your Reservations</h2>

<?php if ($result->num_rows === 0): ?>
    <p class="text-center">You have no reservations.</p>
<?php else: ?>
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Room Type</th><th>Check-in</th><th>Check-out</th><th>Total Price</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="text-center">
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= htmlspecialchars($row['check_in']) ?></td>
                        <td><?= htmlspecialchars($row['check_out']) ?></td>
                        <td>$<?= number_format($row['total_price'], 2) ?></td>
                        <td>
                            <a href="admin_booking_detail.php?booking_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                View
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
require_once __DIR__ . '/../includes/footer.php';
?>