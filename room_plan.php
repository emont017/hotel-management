<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Restrict access to authorized roles like front desk, managers, and admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: index.php");
    exit;
}

$title = "Visual Room Plan";
require_once 'includes/header.php';
require_once 'php/db.php';

// --- Date and Navigation Logic ---
$today = new DateTime();
$start_date_str = $_GET['start_date'] ?? $today->format('Y-m-d');
$start_date = new DateTime($start_date_str);

$days_to_show = 7;
$end_date = clone $start_date;
$end_date->modify('+' . ($days_to_show - 1) . ' days'); // Show a 7-day view

$prev_date = clone $start_date;
$prev_date->modify('-7 days');
$next_date = clone $start_date;
$next_date->modify('+7 days');

// --- Data Fetching Logic ---

// 1. Get all rooms, ordered by room number
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
$rooms = [];
if ($rooms_query) {
    while($row = $rooms_query->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// 2. Get all bookings within the date range
$bookings_stmt = $conn->prepare("
    SELECT
        b.id as booking_id,
        b.room_id,
        b.check_in,
        b.check_out,
        b.status AS booking_status,
        u.full_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.check_in <= ? AND b.check_out > ? AND b.status != 'cancelled'
");
$end_date_sql = $end_date->format('Y-m-d');
$start_date_sql = $start_date->format('Y-m-d');
$bookings_stmt->bind_param("ss", $end_date_sql, $start_date_sql);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// 3. Organize bookings into an easily accessible array
$bookings_by_room = [];
while($booking = $bookings_result->fetch_assoc()) {
    $bookings_by_room[$booking['room_id']][] = $booking;
}

?>

<style>
    .room-plan-container {
        overflow-x: auto;
        padding-bottom: 15px;
        border: 1px solid #081E3F;
        border-radius: 8px;
    }
    .tape-chart {
        min-width: 800px; /* Ensures table has enough space for 7 day columns */
        border-collapse: collapse;
        table-layout: fixed;
    }
    .tape-chart th, .tape-chart td {
        border: 1px solid #06172D;
        text-align: center;
        padding: 0;
        height: 40px;
    }
    .tape-chart th {
        background-color: #081E3F;
        color: #F7B223;
        font-size: 0.85rem;
        padding: 8px 4px;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .room-number-col {
        background-color: #081E3F;
        color: #F7B223;
        font-weight: bold;
        position: sticky;
        left: 0;
        z-index: 5;
        width: 100px; /* Fixed width for room numbers */
    }
    .date-cell {
        font-size: 0.75rem;
    }
    .booking-block {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        color: #fff;
        font-weight: bold;
        font-size: 0.8rem;
        padding: 0 5px;
        box-sizing: border-box;
        overflow: hidden;
        text-decoration: none;
        border-radius: 4px;
    }

    /* Color Coding */
    .status-checked-in { background-color: #e74c3c; } /* Red */
    .status-confirmed { background-color: #3498db; } /* Blue */
    .status-clean { background-color: #2ecc71; } /* Green */
    .status-dirty { background-color: #f1c40f; } /* Yellow */
    .status-maintenance { background-color: #95a5a6; background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(0,0,0,0.1) 10px, rgba(0,0,0,0.1) 20px); } /* Grey w/ stripes */

    .legend { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
    .legend li { display: flex; align-items: center; gap: 8px; }
    .legend .color-box { width: 20px; height: 20px; border: 1px solid #fff; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="color: #F7B223; margin: 0;">🏨 Visual Room Plan</h2>
    <div style="display: flex; gap: 10px;">
        <a href="?start_date=<?= $prev_date->format('Y-m-d') ?>" style="padding: 8px 16px; background-color: #F7B223; color: #081C3A; text-decoration: none; border-radius: 6px; font-weight: bold;">&larr; Previous 7 Days</a>
        <a href="?start_date=<?= $next_date->format('Y-m-d') ?>" style="padding: 8px 16px; background-color: #F7B223; color: #081C3A; text-decoration: none; border-radius: 6px; font-weight: bold;">Next 7 Days &rarr;</a>
    </div>
</div>

<!-- Legend -->
<ul class="legend">
    <li><span class="color-box status-checked-in"></span> Checked-In</li>
    <li><span class="color-box status-confirmed"></span> Confirmed</li>
    <li><span class="color-box status-clean"></span> Vacant (Clean)</li>
    <li><span class="color-box status-dirty"></span> Vacant (Dirty)</li>
    <li><span class="color-box status-maintenance"></span> Maintenance</li>
</ul>

<div class="room-plan-container">
    <table class="tape-chart">
        <thead>
            <tr>
                <th class="room-number-col">Room</th>
                <?php
                $current_date_header = clone $start_date;
                for ($i = 0; $i < $days_to_show; $i++) {
                    // Using a line break for better formatting in tight spaces
                    echo '<th>' . $current_date_header->format('D') . '<br><small>' . $current_date_header->format('M j') . '</small></th>';
                    $current_date_header->modify('+1 day');
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td class="room-number-col"><?= htmlspecialchars($room['room_number']) ?></td>
                    <?php
                    $current_date_cell = clone $start_date;
                    for ($i = 0; $i < $days_to_show; $i++) {
                        $cell_date_str = $current_date_cell->format('Y-m-d');
                        $booking_found = false;

                        if (isset($bookings_by_room[$room['id']])) {
                            foreach ($bookings_by_room[$room['id']] as $booking) {
                                if ($cell_date_str >= $booking['check_in'] && $cell_date_str < $booking['check_out']) {
                                    // This booking covers the current cell date.
                                    // We only draw the block on the check-in day.
                                    if ($cell_date_str == $booking['check_in']) {
                                        $check_in_dt = new DateTime($booking['check_in']);
                                        $check_out_dt = new DateTime($booking['check_out']);
                                        $duration = $check_in_dt->diff($check_out_dt)->days;
                                        // Ensure duration is at least 1 for same-day check-in/out scenarios
                                        $duration = $duration > 0 ? $duration : 1;
                                        $status_class = 'status-' . htmlspecialchars($booking['booking_status']);

                                        echo "<td colspan='{$duration}' class='date-cell'>";
                                        echo "<a href='admin_booking_detail.php?booking_id={$booking['booking_id']}' class='booking-block {$status_class}'>";
                                        echo htmlspecialchars($booking['full_name']);
                                        echo "</a></td>";

                                        // Advance the loop counter by the duration of the booking
                                        $i += ($duration - 1);
                                        $current_date_cell->modify('+' . ($duration - 1) . ' days');
                                    }
                                    $booking_found = true;
                                    break;
                                }
                            }
                        }

                        if (!$booking_found) {
                             // Room is not booked, determine its status (clean, dirty, maintenance)
                            $status_class = 'status-' . htmlspecialchars($room['housekeeping_status']);
                            if ($room['status'] === 'maintenance') {
                                $status_class = 'status-maintenance';
                            }
                            echo "<td class='date-cell {$status_class}'></td>";
                        }
                        $current_date_cell->modify('+1 day');
                    }
                    ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>