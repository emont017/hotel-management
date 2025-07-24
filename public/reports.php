<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: index.php");
    exit;
}

$title = "Hotel Reports & Analytics";
require_once __DIR__ . '/../includes/header.php';

// --- Report Generation Logic ---
$start_date_str = $_GET['start_date'] ?? date('Y-m-01');
$end_date_str = $_GET['end_date'] ?? date('Y-m-t');

// 1. Total Revenue
$revenue_stmt = $conn->prepare("SELECT SUM(total_price) AS total_revenue FROM bookings WHERE status IN ('checked-in', 'checked-out', 'confirmed') AND check_in BETWEEN ? AND ?");
$revenue_stmt->bind_param("ss", $start_date_str, $end_date_str);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$revenue_stmt->close();

// 2. Total Bookings
$bookings_stmt = $conn->prepare("SELECT COUNT(id) AS total_bookings FROM bookings WHERE status != 'cancelled' AND check_in BETWEEN ? AND ?");
$bookings_stmt->bind_param("ss", $start_date_str, $end_date_str);
$bookings_stmt->execute();
$total_bookings = $bookings_stmt->get_result()->fetch_assoc()['total_bookings'] ?? 0;
$bookings_stmt->close();

// 3. Occupancy Rate
$total_rooms_query = $conn->query("SELECT COUNT(*) AS total FROM rooms");
$total_rooms = $total_rooms_query ? $total_rooms_query->fetch_assoc()['total'] : 0;

// Fix for "pass by reference" notice
$start_date_obj = new DateTime($start_date_str);
$end_date_obj = new DateTime($end_date_str);
$days_in_range = $start_date_obj->diff($end_date_obj)->days + 1;

$total_room_nights_available = $total_rooms * $days_in_range;
$occupied_nights_stmt = $conn->prepare("SELECT SUM(DATEDIFF(LEAST(check_out, ?), GREATEST(check_in, ?))) AS occupied_nights FROM bookings WHERE status != 'cancelled' AND check_in <= ? AND check_out > ?");
$occupied_nights_stmt->bind_param("ssss", $end_date_str, $start_date_str, $end_date_str, $start_date_str);
$occupied_nights_stmt->execute();
$occupied_room_nights = $occupied_nights_stmt->get_result()->fetch_assoc()['occupied_nights'] ?? 0;
$occupied_nights_stmt->close();
$occupancy_rate = ($total_room_nights_available > 0) ? round(($occupied_room_nights / $total_room_nights_available) * 100, 2) : 0;

// 4. Booking Status Breakdown
$status_stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM bookings WHERE check_in BETWEEN ? AND ? GROUP BY status");
$status_stmt->bind_param("ss", $start_date_str, $end_date_str);
$status_stmt->execute();
$status_counts = $status_stmt->get_result();
$status_stmt->close();

// 5. Top Room Types by Revenue
$top_rooms_stmt = $conn->prepare("SELECT r.room_type, COUNT(b.id) AS bookings_count, SUM(b.total_price) AS revenue FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.status != 'cancelled' AND b.check_in BETWEEN ? AND ? GROUP BY r.room_type ORDER BY revenue DESC LIMIT 5");
$top_rooms_stmt->bind_param("ss", $start_date_str, $end_date_str);
$top_rooms_stmt->execute();
$top_rooms_result = $top_rooms_stmt->get_result();
$top_rooms_stmt->close();

// Format date range for display
$start_formatted = date('M j, Y', strtotime($start_date_str));
$end_formatted = date('M j, Y', strtotime($end_date_str));
?>

<div class="dashboard-header">
    <div>
        <h1>Hotel Reports & Analytics</h1>
        <p>Generate comprehensive reports and analyze key performance metrics for data-driven hotel management decisions.</p>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card" style="margin-top: 20px; margin-bottom: 30px;">
    <h3 style="margin-bottom: 10px; color: #B6862C;">Report Parameters</h3>
    <p style="color: #8892a7; margin-bottom: 20px; font-size: 0.9rem;">
        Select date range to generate custom reports and analytics
    </p>
    
    <form method="get" action="reports.php">
        <div style="display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label">Start Date:</label>
                <input type="date" name="start_date" class="form-input" value="<?= htmlspecialchars($start_date_str) ?>" required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label class="form-label">End Date:</label>
                <input type="date" name="end_date" class="form-input" value="<?= htmlspecialchars($end_date_str) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Generate Report</button>
        </div>
    </form>
    
    <?php if (isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
        <div style="margin-top: 15px; padding: 10px; background-color: rgba(182, 134, 44, 0.1); border-radius: 6px; border-left: 3px solid #B6862C;">
            <span style="color: #B6862C; font-weight: 600; font-size: 0.9rem;">
                📊 Report Period: <?= $start_formatted ?> to <?= $end_formatted ?>
            </span>
        </div>
    <?php endif; ?>
</div>

<!-- Key Metrics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div class="card" style="text-align: center; padding: 20px;">
        <div style="font-size: 2rem; font-weight: 700; color: #2ecc71; margin-bottom: 8px; font-family: 'Orbitron', sans-serif;">
            $<?= number_format($total_revenue, 0) ?>
        </div>
        <div style="font-size: 0.85rem; color: #B6862C; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
            Total Revenue
        </div>
        <div style="font-size: 0.75rem; color: #8892a7; margin-top: 4px;">
            Period earnings
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 20px;">
        <div style="font-size: 2rem; font-weight: 700; color: #3498db; margin-bottom: 8px; font-family: 'Orbitron', sans-serif;">
            <?= number_format($total_bookings) ?>
        </div>
        <div style="font-size: 0.85rem; color: #B6862C; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
            Total Bookings
        </div>
        <div style="font-size: 0.75rem; color: #8892a7; margin-top: 4px;">
            Confirmed reservations
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 20px;">
        <div style="font-size: 2rem; font-weight: 700; color: #f39c12; margin-bottom: 8px; font-family: 'Orbitron', sans-serif;">
            <?= $occupancy_rate ?>%
        </div>
        <div style="font-size: 0.85rem; color: #B6862C; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
            Occupancy Rate
        </div>
        <div style="font-size: 0.75rem; color: #8892a7; margin-top: 4px;">
            Room utilization
        </div>
    </div>
</div>

<!-- Report Tables -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
    
    <!-- Booking Status Breakdown -->
    <div class="card">
        <div style="margin-bottom: 20px;">
            <h3 style="margin: 0; color: #B6862C;">Booking Status Breakdown</h3>
            <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                Distribution of bookings by current status
            </p>
        </div>
        
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Status</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($status_counts && $status_counts->num_rows > 0): ?>
                        <?php 
                        $status_colors = [
                            'confirmed' => '#3498db',
                            'checked-in' => '#2ecc71', 
                            'checked-out' => '#95a5a6',
                            'cancelled' => '#e74c3c'
                        ];
                        $status_counts->data_seek(0);
                        while ($row = $status_counts->fetch_assoc()): 
                            $color = $status_colors[$row['status']] ?? '#8892a7';
                        ?>
                            <tr>
                                <td>
                                    <span class="role-badge" style="background-color: <?= $color ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                        <?= strtoupper(str_replace('-', ' ', htmlspecialchars($row['status']))) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #B6862C;"><?= $row['count'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 40px 20px; color: #8892a7;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">📊</div>
                                <div>No booking data for this period</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Room Types by Revenue -->
    <div class="card">
        <div style="margin-bottom: 20px;">
            <h3 style="margin: 0; color: #B6862C;">Top Room Types by Revenue</h3>
            <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                Best performing room categories by earnings
            </p>
        </div>
        
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Room Type</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Bookings</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_rooms_result && $top_rooms_result->num_rows > 0): ?>
                        <?php 
                        $top_rooms_result->data_seek(0);
                        $rank = 1;
                        while ($row = $top_rooms_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="background-color: #B6862C; color: #081C3A; font-weight: 600; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; min-width: 16px; text-align: center;">
                                            <?= $rank ?>
                                        </span>
                                        <span style="font-weight: 600; color: #fff;">
                                            <?= htmlspecialchars($row['room_type']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td style="color: #8892a7;"><?= $row['bookings_count'] ?></td>
                                <td>
                                    <span style="font-family: monospace; font-weight: 600; color: #2ecc71;">
                                        $<?= number_format($row['revenue'], 2) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                        $rank++;
                        endwhile; 
                        ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px 20px; color: #8892a7;">
                                <div style="font-size: 2rem; margin-bottom: 10px;">🏨</div>
                                <div>No room revenue data for this period</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>