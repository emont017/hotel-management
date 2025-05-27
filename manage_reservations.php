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

<h2 style="text-align: center; margin-bottom: 30px;">📅 Your Reservations</h2>

<?php if ($result->num_rows === 0): ?>
    <p style="text-align: center;">You have no reservations.</p>
<?php else: ?>
    <div style="overflow-x: auto;">
        <table style="
            width: 100%;
            border-collapse: collapse;
            background-color: rgba(7, 28, 58, 0.8);
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(247, 178, 35, 0.6);
            margin-bottom: 40px;
        ">
            <thead>
                <tr style="background-color: #F7B223; color: #081C3A;">
                    <th style="padding: 12px;">ID</th>
                    <th style="padding: 12px;">Room Type</th>
                    <th style="padding: 12px;">Check-in</th>
                    <th style="padding: 12px;">Check-out</th>
                    <th style="padding: 12px;">Total Price</th>
                    <th style="padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr style="border-top: 1px solid #ccc; text-align: center;">
                        <td style="padding: 10px;"><?= htmlspecialchars($row['id']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($row['room_type']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($row['check_in']) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($row['check_out']) ?></td>
                        <td style="padding: 10px;">$<?= number_format($row['total_price'], 2) ?></td>
                        <td style="padding: 10px;">
                            <a href="reservation_detail.php?id=<?= $row['id'] ?>" style="
                                background-color: #F7B223;
                                color: #081C3A;
                                padding: 6px 12px;
                                border-radius: 8px;
                                text-decoration: none;
                                font-weight: bold;
                                transition: background-color 0.3s ease;
                            " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
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
require_once 'includes/footer.php';
?>
