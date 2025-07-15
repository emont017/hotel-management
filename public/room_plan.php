<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

$title = "Upcoming Stays";
require_once __DIR__ . '/../includes/header.php';

// --- Date and Navigation Logic ---
$today = new DateTime('now', new DateTimeZone('America/New_York'));
$start_date_str = $_GET['start_date'] ?? $today->format('Y-m-d');
$start_date = new DateTime($start_date_str);
$days_to_show = 7;

$end_date = clone $start_date;
$end_date->modify('+' . ($days_to_show - 1) . ' days');
$prev_date = clone $start_date;
$prev_date->modify('-7 days');
$next_date = clone $start_date;
$next_date->modify('+7 days');

// --- Data Fetching Logic ---
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
$rooms = [];
if ($rooms_query) {
    while($row = $rooms_query->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// Fetch all relevant bookings once
$bookings_stmt = $conn->prepare("
    SELECT b.id as booking_id, b.room_id, b.check_in, b.check_out, b.status AS booking_status, u.full_name
    FROM bookings b JOIN users u ON b.user_id = u.id
    WHERE b.room_id IN (SELECT id FROM rooms) AND b.check_out >= ? AND b.check_in <= ? AND b.status != 'cancelled'
");
$start_date_param = $start_date->format('Y-m-d');
$end_date_param = $end_date->format('Y-m-d');
$bookings_stmt->bind_param("ss", $start_date_param, $end_date_param);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings_by_room = [];
while($booking = $bookings_result->fetch_assoc()) {
    $bookings_by_room[$booking['room_id']][] = $booking;
}
$bookings_stmt->close();
?>

<style>
    .room-plan-container { overflow-x: auto; padding-bottom: 15px; border: 1px solid #122C55; border-radius: 8px; background-color: #081C3A;}
    .tape-chart { min-width: 900px; border-collapse: collapse; table-layout: fixed; }
    .tape-chart th, .tape-chart td { border: 1px solid #06172D; text-align: center; padding: 0; height: 40px; }
    .tape-chart th { color: #B6862C; font-size: 0.85rem; padding: 8px 4px; position: sticky; top: 0; z-index: 10; background-color: #0E1E40;}
    .room-number-col { color: #B6862C; font-weight: bold; position: sticky; left: 0; z-index: 5; width: 100px; background-color: #0E1E40;}
    .date-cell { font-size: 0.75rem; }
    .booking-block { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; color: #fff; font-weight: bold; font-size: 0.8rem; padding: 0 5px; box-sizing: border-box; overflow: hidden; text-decoration: none; border-radius: 4px; border: 1px solid rgba(255, 255, 255, 0.2);}
    .status-checked-in { background-color: #e74c3c; }
    .status-confirmed { background-color: #3498db; }
    .legend { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; align-items: center;}
    .legend li { display: flex; align-items: center; gap: 8px; }
    .legend .color-box { width: 20px; height: 20px; border: 1px solid #fff; border-radius: 4px;}
</style>

<div class="mb-20" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Upcoming Stays</h2>
    <div style="display: flex; gap: 10px;">
        <a href="?start_date=<?= $prev_date->format('Y-m-d') ?>" class="btn btn-primary">&larr; Previous</a>
        <a href="?start_date=<?= $next_date->format('Y-m-d') ?>" class="btn btn-primary">Next &rarr;</a>
    </div>
</div>

<ul class="legend">
    <li><span class="color-box" style="background-color: #e74c3c;"></span> Checked-In</li>
    <li><span class="color-box" style="background-color: #3498db;"></span> Confirmed</li>
    <li><span class="color-box" style="background-color: #2ecc71;"></span> Vacant (Clean)</li>
    <li><span class="color-box" style="background-color: #f1c40f;"></span> Vacant (Dirty)</li>
    <li><span class="color-box" style="background-color: #95a5a6;"></span> Maintenance</li>
</ul>

<div class="room-plan-container">
    <table class="tape-chart">
        <thead>
            <tr>
                <th class="room-number-col">Room</th>
                <?php
                $current_header_date = clone $start_date;
                for ($i = 0; $i < $days_to_show; $i++) {
                    echo '<th>' . $current_header_date->format('D') . '<br><small>' . $current_header_date->format('M j') . '</small></th>';
                    $current_header_date->modify("+1 day");
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td class="room-number-col"><?= htmlspecialchars($room['room_number']) ?></td>
                    <?php
                    for ($i = 0; $i < $days_to_show; $i++) {
                        $current_date_cell = clone $start_date;
                        $current_date_cell->modify("+$i days");
                        $cell_date_str = $current_date_cell->format('Y-m-d');
                        
                        $booking_for_this_day = null;
                        
                        if (isset($bookings_by_room[$room['id']])) {
                            foreach ($bookings_by_room[$room['id']] as $booking) {
                                if ($cell_date_str >= $booking['check_in'] && $cell_date_str < $booking['check_out']) {
                                    $booking_for_this_day = $booking;
                                    break;
                                }
                            }
                        }

                        if ($booking_for_this_day) {
                            if ($cell_date_str == $booking_for_this_day['check_in']) {
                                $check_in_dt = new DateTime($booking_for_this_day['check_in']);
                                $check_out_dt = new DateTime($booking_for_this_day['check_out']);
                                $duration = $check_in_dt->diff($check_out_dt)->days;
                                $duration = max(1, $duration);
                                $status_class = 'status-' . htmlspecialchars($booking_for_this_day['booking_status']);
                                
                                echo "<td colspan='{$duration}' class='date-cell'><a href='/admin_booking_detail.php?booking_id={$booking_for_this_day['booking_id']}' class='booking-block {$status_class}'>" . htmlspecialchars($booking_for_this_day['full_name']) . "</a></td>";
                                
                                $i += $duration - 1;
                            }
                        } else {
                            $status_class = $room['housekeeping_status'] === 'clean' ? 'status-vacant-clean' : 'status-vacant-dirty';
                            
                            // THIS LINE IS NOW CORRECTED
                            if ($room['status'] === 'maintenance') {
                                $status_class = 'status-maintenance';
                            }
                             
                            echo "<td class='date-cell'><div class='booking-block {$status_class}' style='opacity: 0.5;'></div></td>";
                        }
                    }
                    ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>