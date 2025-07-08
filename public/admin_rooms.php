<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Manage Rooms";
require_once __DIR__ . '/../includes/header.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

// Fetch all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
?>

<h2 class="text-center mt-30">🛏 Manage Rooms</h2>

<table class="data-table">
    <thead>
        <tr>
            <th>Room Number</th>
            <th>Room Type</th>
            <th>Capacity</th>
            <th>Availability</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $rooms->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['room_number']) ?></td>
                <td><?= htmlspecialchars($row['room_type']) ?></td>
                <td><?= $row['capacity'] ?></td>
                <td class="text-capitalize" style="font-weight: bold; color: <?= $row['status'] === 'maintenance' ? '#f39c12' : '#2ecc71'; ?>">
                    <?= htmlspecialchars($row['status']) ?>
                </td>
                <td>
                    <a href="admin_room_detail.php?room_id=<?= $row['id'] ?>" class="btn btn-primary">
                        Manage
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>