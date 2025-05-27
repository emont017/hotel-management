<?php
session_start();
require_once 'php/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'friend') {
    header("Location: index.php");
    exit;
}

$title = "Manage Your Reservations";
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

$sql = "SELECT b.id, r.room_type, b.check_in, b.check_out, b.total_price
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE b.user_id = ?
        ORDER BY b.check_in DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h1>Your Reservations</h1>

<?php if ($result->num_rows === 0): ?>
    <p>You have no reservations.</p>
<?php else: ?>
    <table style="width:100%; border-collapse: collapse; color: 
        <thead>
            <tr style="border-bottom: 2px solid 
                <th>ID</th>
                <th>Room Type</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr style="border-bottom: 1px solid 
                <td><?= htmlspecialchars($row['id']) ?></td>
                <td><?= htmlspecialchars($row['room_type']) ?></td>
                <td><?= htmlspecialchars($row['check_in']) ?></td>
                <td><?= htmlspecialchars($row['check_out']) ?></td>
                <td>$<?= number_format($row['total_price'], 2) ?></td>
                <td><a href="reservation_detail.php?id=<?= $row['id'] ?>" style="color:
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
require_once 'includes/footer.php';
?>
