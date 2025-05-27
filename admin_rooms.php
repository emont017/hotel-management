<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';
$title = "Manage Rooms";
require_once 'includes/header.php';


$rooms = $conn->query("SELECT * FROM rooms ORDER BY room_number");
?>

<h2>🛏 Manage Rooms</h2>

<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
  <thead>
    <tr style="background-color: 
      <th style="padding: 10px; border-bottom: 1px solid 
      <th style="padding: 10px; border-bottom: 1px solid 
      <th style="padding: 10px; border-bottom: 1px solid 
      <th style="padding: 10px; border-bottom: 1px solid 
      <th style="padding: 10px; border-bottom: 1px solid 
      <th style="padding: 10px; border-bottom: 1px solid 
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $rooms->fetch_assoc()): ?>
      <tr>
        <td style="padding: 10px;"><?= htmlspecialchars($row['room_number']) ?></td>
        <td style="padding: 10px;"><?= htmlspecialchars($row['room_type']) ?></td>
        <td style="padding: 10px;">$<?= number_format($row['price'], 2) ?></td>
        <td style="padding: 10px;"><?= $row['capacity'] ?></td>
        <td style="padding: 10px;">
            <?= $row['status'] === 'maintenance' ? 'Maintenance' : 'Available' ?>
        </td>
        <td style="padding: 10px;">
          <a href="admin_room_detail.php?room_id=<?= $row['id'] ?>" class="button-link" style="
            background-color: 
            color: 
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
          ">Manage</a>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>
