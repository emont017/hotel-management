<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Restrict access to admin and guest roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';
$title = "Manage Rooms";
require_once 'includes/header.php';

// Fetch all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
?>

<h2 style="color: #F7B223; text-align: center; margin-top: 30px;">🛏 Manage Rooms</h2>

<table style="width: 100%; border-collapse: collapse; margin: 30px 0; background-color: #081C3A;">
    <thead>
        <tr>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Room Number</th>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Room Type</th>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Price</th>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Capacity</th>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Availability</th>
            <th style="padding: 12px; border-bottom: 2px solid #F7B223; color: #F7B223;">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $rooms->fetch_assoc()): ?>
            <tr style="background-color: <?= $row['status'] === 'maintenance' ? '#2E4053' : '#122C55'; ?>;">
                <td style="padding: 10px; color: #F7B223;"><?= htmlspecialchars($row['room_number']) ?></td>
                <td style="padding: 10px; color: #F7B223;"><?= htmlspecialchars($row['room_type']) ?></td>
                <td style="padding: 10px; color: #F7B223;">$<?= number_format($row['price'], 2) ?></td>
                <td style="padding: 10px; color: #F7B223;"><?= $row['capacity'] ?></td>
                <td style="padding: 10px; font-weight: bold; color: <?= $row['status'] === 'maintenance' ? '#e67e22' : '#2ecc71'; ?>">
                    <?= ucfirst($row['status']) ?>
                </td>
                <td style="padding: 10px;">
                    <a href="admin_room_detail.php?room_id=<?= $row['id'] ?>" style="
                        background-color: #F7B223;
                        color: #081C3A;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-weight: bold;
                        text-decoration: none;
                        display: inline-block;
                        transition: background-color 0.3s ease;
                    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
                        Manage
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>
