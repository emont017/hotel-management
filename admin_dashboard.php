<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'guest'])) {
    header("Location: index.php");
    exit;
}

$title = "Admin Dashboard";
require_once 'includes/header.php';
require_once 'php/db.php';

// Basic stats
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_rooms = $conn->query("SELECT COUNT(*) FROM rooms")->fetch_row()[0];
$total_bookings = $conn->query("SELECT COUNT(*) FROM bookings")->fetch_row()[0];

// Room status breakdown
$available_rooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetch_row()[0];
$booked_rooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'booked'")->fetch_row()[0];
$maintenance_rooms = $conn->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetch_row()[0];

// Recent bookings
$recent_bookings = $conn->query("
    SELECT b.id, r.room_number, r.room_type, u.username, b.check_in, b.check_out
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 5
");
?>

<h2 style="color: #F7B223;">📊 Hotel Management Dashboard</h2>

<div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px; margin-top: 30px;">
    <?php
    $cards = [
        ['👥', 'Total Users', $total_users, 'users.php'],         // ✅ FIXED HERE
        ['🛏️', 'Total Rooms', $total_rooms, 'admin_rooms.php'],
        ['📅', 'Total Bookings', $total_bookings, 'admin_bookings.php'],
        ['✅', 'Available Rooms', $available_rooms, 'admin_rooms.php'],
        ['🚫', 'Booked Rooms', $booked_rooms, 'admin_rooms.php'],
        ['🛠️', 'Maintenance Rooms', $maintenance_rooms, 'admin_rooms.php'],
    ];

    foreach ($cards as [$icon, $label, $value, $link]) {
        echo "
        <a href=\"$link\" style=\"text-decoration: none; flex: 1; min-width: 200px;\">
            <div style=\"
                background-color: rgba(247, 178, 35, 0.15);
                padding: 25px;
                border-radius: 12px;
                text-align: center;
                color: #fff;
                box-shadow: 0 0 12px rgba(0,0,0,0.3);
                transition: background-color 0.3s ease;
            \" onmouseover=\"this.style.backgroundColor='rgba(247,178,35,0.3)'\" onmouseout=\"this.style.backgroundColor='rgba(247,178,35,0.15)'\">
                <h3 style=\"margin-bottom: 10px;\">$icon $label</h3>
                <p style=\"font-size: 2.5em; color: #F7B223; font-weight: bold;\">$value</p>
            </div>
        </a>";
    }
    ?>
</div>

<!-- Recent Bookings -->
<div style="margin-top: 50px;">
    <h3 style="color: #F7B223;">🕓 Recent Bookings</h3>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 1rem;">
        <thead>
            <tr style="background-color: #081E3F; color: white;">
                <th style="padding: 12px; border: 1px solid #ddd;">Booking ID</th>
                <th style="padding: 12px; border: 1px solid #ddd;">User</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Room</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Type</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Check-In</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Check-Out</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            while ($row = $recent_bookings->fetch_assoc()):
                $bg = ($i++ % 2 === 0) ? "#f8f9fa" : "#ffffff";
            ?>
                <tr style="background-color: <?= $bg ?>; color: #081E3F;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='<?= $bg ?>'">
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['id'] ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['username']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;">Room <?= htmlspecialchars($row['room_number']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['room_type']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['check_in']) ?></td>
                    <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['check_out']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
