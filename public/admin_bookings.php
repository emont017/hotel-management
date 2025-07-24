<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

$title = "Manage Bookings";
require_once __DIR__ . '/../includes/header.php';

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$where_conditions = [];
$params = [];

if ($status_filter && $status_filter !== 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if ($search_query) {
    $where_conditions[] = "(u.full_name LIKE ? OR r.room_number LIKE ? OR b.id = ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_query;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "
    SELECT
        b.id as booking_id,
        u.full_name,
        u.email,
        r.room_number,
        r.room_type,
        r.housekeeping_status,
        b.check_in,
        b.check_out,
        COALESCE(f.balance, b.total_price, 0) as balance,
        b.status,
        b.created_at,
        DATEDIFF(b.check_out, b.check_in) as nights
    FROM
        bookings b
    JOIN
        users u ON b.user_id = u.id
    JOIN
        rooms r ON b.room_id = r.id
    LEFT JOIN
        folios f ON b.id = f.booking_id
    {$where_clause}
    ORDER BY
        b.check_in DESC, b.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings_result = $stmt->get_result();

// Get booking statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
        SUM(CASE WHEN b.status = 'checked_in' THEN 1 ELSE 0 END) as checked_in_bookings,
        SUM(CASE WHEN b.status = 'checked_out' THEN 1 ELSE 0 END) as checked_out_bookings,
        SUM(CASE WHEN b.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
        SUM(COALESCE(f.balance, b.total_price, 0)) as total_revenue
    FROM bookings b 
    LEFT JOIN folios f ON b.id = f.booking_id
    WHERE b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<div class="dashboard-header">
    <div class="header-main">
        <h1>Booking Management</h1>
        <p>Comprehensive view and control of all guest reservations</p>
    </div>
    <div class="quick-actions">
        <a href="admin_rooms.php" class="quick-btn">
            <span class="quick-icon">🛏️</span>
            <span>Rooms</span>
        </a>
        <a href="reports.php" class="quick-btn">
            <span class="quick-icon">📊</span>
            <span>Reports</span>
        </a>
    </div>
</div>

<!-- KPI Dashboard -->
<div class="kpi-grid-professional">
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-label-pro">Total Bookings</div>
            <div class="kpi-value-pro"><?= number_format($stats['total_bookings']) ?></div>
            <div class="kpi-sub-pro">Last 30 days</div>
        </div>
    </div>
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-label-pro">Confirmed</div>
            <div class="kpi-value-pro"><?= number_format($stats['confirmed_bookings']) ?></div>
            <div class="kpi-sub-pro">Ready for check-in</div>
        </div>
    </div>
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-label-pro">Checked In</div>
            <div class="kpi-value-pro"><?= number_format($stats['checked_in_bookings']) ?></div>
            <div class="kpi-sub-pro">Current guests</div>
        </div>
    </div>
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-label-pro">Revenue</div>
            <div class="kpi-value-pro">$<?= number_format($stats['total_revenue'], 0) ?></div>
            <div class="kpi-sub-pro">30-day total</div>
        </div>
    </div>
</div>

<!-- Search and Filter Section -->
<div class="card" style="margin-bottom: 25px;">
    <form method="GET" class="booking-filters" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px;">
            <label class="form-label">Search Bookings</label>
            <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                   placeholder="Guest name, room number, or booking ID..." 
                   class="form-input" style="margin-bottom: 0;">
        </div>
        <div style="min-width: 150px;">
            <label class="form-label">Status Filter</label>
            <select name="status" class="form-select" style="margin-bottom: 0;">
                <option value="all" <?= $status_filter === 'all' || !$status_filter ? 'selected' : '' ?>>All Statuses</option>
                <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="checked_in" <?= $status_filter === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                <option value="checked_out" <?= $status_filter === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <span style="margin-right: 5px;">🔍</span> Search
            </button>
            <a href="admin_bookings.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #B6862C;">Booking Records</h3>
        <div style="color: #8892a7; font-size: 0.9rem;">
            <?= $bookings_result->num_rows ?> booking(s) found
        </div>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table" style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Guest Information</th>
                    <th>Room Details</th>
                    <th>Stay Period</th>
                    <th style="text-align: right;">Amount</th>
                    <th>Status</th>
                    <th style="width: 120px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings_result && $bookings_result->num_rows > 0): ?>
                    <?php while ($row = $bookings_result->fetch_assoc()): ?>
                        <tr style="border-left: 3px solid <?= 
                            $row['status'] === 'confirmed' ? '#3498db' : 
                            ($row['status'] === 'checked_in' ? '#2ecc71' : 
                            ($row['status'] === 'checked_out' ? '#95a5a6' : 
                            ($row['status'] === 'cancelled' ? '#e74c3c' : '#B6862C'))) ?>;">
                            
                            <td style="font-weight: bold; color: #B6862C;">
                                #<?= $row['booking_id'] ?>
                            </td>
                            
                            <td>
                                <div style="font-weight: 600; color: #fff; margin-bottom: 4px;">
                                    <?= htmlspecialchars($row['full_name']) ?>
                                </div>
                                <div style="font-size: 0.85rem; color: #8892a7;">
                                    <?= htmlspecialchars($row['email']) ?>
                                </div>
                            </td>
                            
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div>
                                        <div style="font-weight: 600; color: #B6862C;">
                                            Room <?= htmlspecialchars($row['room_number']) ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: #fff; text-transform: capitalize;">
                                            <?= htmlspecialchars($row['room_type']) ?>
                                        </div>
                                    </div>
                                    <div class="status-dot <?= strtolower($row['housekeeping_status']) ?>" 
                                         title="Room Status: <?= ucfirst($row['housekeeping_status']) ?>"></div>
                                </div>
                            </td>
                            
                            <td>
                                <div style="margin-bottom: 4px;">
                                    <span style="color: #8892a7; font-size: 0.85rem;">Check-in:</span>
                                    <span style="color: #fff; font-weight: 500;"><?= date('M j, Y', strtotime($row['check_in'])) ?></span>
                                </div>
                                <div>
                                    <span style="color: #8892a7; font-size: 0.85rem;">Check-out:</span>
                                    <span style="color: #fff; font-weight: 500;"><?= date('M j, Y', strtotime($row['check_out'])) ?></span>
                                </div>
                                <div style="margin-top: 4px; color: #B6862C; font-size: 0.85rem;">
                                    <?= $row['nights'] ?> night<?= $row['nights'] != 1 ? 's' : '' ?>
                                </div>
                            </td>
                            
                            <td style="text-align: right;">
                                <div style="font-size: 1.2rem; font-weight: bold; color: #fff;">
                                    $<?= number_format($row['balance'], 2) ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #8892a7;">
                                    Total Amount
                                </div>
                            </td>
                            
                            <td>
                                <span class="booking-status-badge status-<?= $row['status'] ?>" style="
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 6px;
                                    padding: 6px 12px;
                                    border-radius: 20px;
                                    font-size: 0.8rem;
                                    font-weight: 600;
                                    text-transform: capitalize;
                                    <?= 
                                        $row['status'] === 'confirmed' ? 'background: rgba(52, 152, 219, 0.15); color: #3498db; border: 1px solid rgba(52, 152, 219, 0.3);' : 
                                        ($row['status'] === 'checked_in' ? 'background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3);' : 
                                        ($row['status'] === 'checked_out' ? 'background: rgba(149, 165, 166, 0.15); color: #95a5a6; border: 1px solid rgba(149, 165, 166, 0.3);' : 
                                        ($row['status'] === 'cancelled' ? 'background: rgba(231, 76, 60, 0.15); color: #e74c3c; border: 1px solid rgba(231, 76, 60, 0.3);' : 'background: rgba(182, 134, 44, 0.15); color: #B6862C; border: 1px solid rgba(182, 134, 44, 0.3);')))
                                    ?>">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor;"></span>
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            
                            <td style="text-align: center;">
                                <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" 
                                   class="btn btn-sm" 
                                   style="background: rgba(93, 173, 226, 0.15); color: #5dade2; border: 1px solid rgba(93, 173, 226, 0.3); 
                                          font-size: 0.8rem; padding: 6px 12px; text-decoration: none; border-radius: 6px;
                                          transition: all 0.2s ease;"
                                   onmouseover="this.style.background='rgba(93, 173, 226, 0.25)'; this.style.transform='translateY(-1px)';"
                                   onmouseout="this.style.background='rgba(93, 173, 226, 0.15)'; this.style.transform='translateY(0)';">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #8892a7;">
                            <div style="margin-bottom: 10px; font-size: 2rem;">📅</div>
                            <div style="font-size: 1.1rem; margin-bottom: 5px;">No bookings found</div>
                            <div style="font-size: 0.9rem;">
                                <?php if ($search_query || $status_filter): ?>
                                    Try adjusting your search criteria or <a href="admin_bookings.php" style="color: #B6862C;">clear filters</a>
                                <?php else: ?>
                                    Create your first booking to get started
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.booking-filters {
    background: rgba(8, 28, 58, 0.4);
    padding: 20px;
    border-radius: 10px;
    border: 1px solid rgba(182, 134, 44, 0.2);
}

.data-table tbody tr {
    transition: all 0.2s ease;
}

.data-table tbody tr:hover {
    background-color: rgba(18, 44, 85, 0.8);
    transform: translateX(2px);
}

.booking-status-badge {
    white-space: nowrap;
}

.status-dot {
    flex-shrink: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-header {
        padding: 20px;
    }
    
    .booking-filters {
        flex-direction: column;
        gap: 15px !important;
    }
    
    .booking-filters > div {
        min-width: auto !important;
        flex: 1 !important;
    }
    
    .data-table th,
    .data-table td {
        padding: 8px;
        font-size: 0.85rem;
    }
    
    .kpi-grid-professional {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
