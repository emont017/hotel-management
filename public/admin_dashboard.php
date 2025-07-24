<?php
session_start();
date_default_timezone_set('America/New_York');
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

$title = "Hotel Dashboard";
require_once __DIR__ . '/../includes/header.php';

// ENHANCED Dashboard Data Fetching
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// Core Metrics
$stmt_arrivals = $conn->prepare("SELECT COUNT(id) as count FROM bookings WHERE check_in = ? AND status = 'confirmed'");
$stmt_arrivals->bind_param("s", $today);
$stmt_arrivals->execute();
$arrivals_today = $stmt_arrivals->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_arrivals->close();

$stmt_departures = $conn->prepare("SELECT COUNT(id) as count FROM bookings WHERE check_out = ? AND status = 'checked-in'");
$stmt_departures->bind_param("s", $today);
$stmt_departures->execute();
$departures_today = $stmt_departures->get_result()->fetch_assoc()['count'] ?? 0;
$stmt_departures->close();

$rooms_occupied_query = $conn->query("SELECT COUNT(id) as count FROM bookings WHERE status = 'checked-in'");
$rooms_occupied = $rooms_occupied_query->fetch_assoc()['count'] ?? 0;

$total_rooms_query = $conn->query("SELECT COUNT(id) as count FROM rooms WHERE status != 'maintenance'");
$total_rooms = $total_rooms_query->fetch_assoc()['count'] ?? 1;

// Financial KPIs
$revenue_today_query = $conn->query("SELECT SUM(b.total_price / GREATEST(1, DATEDIFF(b.check_out, b.check_in))) as revenue FROM bookings b WHERE b.status = 'checked-in'");
$revenue_today = $revenue_today_query->fetch_assoc()['revenue'] ?? 0;

$adr = ($rooms_occupied > 0) ? $revenue_today / $rooms_occupied : 0;
$revpar = ($total_rooms > 0) ? $revenue_today / $total_rooms : 0;
$occupancy_rate = ($total_rooms > 0) ? ($rooms_occupied / $total_rooms) * 100 : 0;

// Additional metrics for enhanced dashboard
$rooms_clean_query = $conn->query("SELECT COUNT(id) as count FROM rooms WHERE status = 'clean'");
$rooms_clean = $rooms_clean_query->fetch_assoc()['count'] ?? 0;

$rooms_maintenance_query = $conn->query("SELECT COUNT(id) as count FROM rooms WHERE status = 'maintenance'");
$rooms_maintenance = $rooms_maintenance_query->fetch_assoc()['count'] ?? 0;

$pending_checkins_query = $conn->query("SELECT COUNT(id) as count FROM bookings WHERE status = 'confirmed' AND check_in <= '$today'");
$pending_checkins = $pending_checkins_query->fetch_assoc()['count'] ?? 0;

// Action Lists
$arrivals_list_stmt = $conn->prepare("SELECT b.id as booking_id, u.full_name, r.room_number FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.check_in = ? AND b.status = 'confirmed' ORDER BY u.full_name ASC");
$arrivals_list_stmt->bind_param("s", $today);
$arrivals_list_stmt->execute();
$arrivals_list = $arrivals_list_stmt->get_result();

$departures_list_stmt = $conn->prepare("SELECT b.id as booking_id, u.full_name, r.room_number FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.check_out = ? AND b.status = 'checked-in' ORDER BY u.full_name ASC");
$departures_list_stmt->bind_param("s", $today);
$departures_list_stmt->execute();
$departures_list = $departures_list_stmt->get_result();
?>

<div class="dashboard-header">
    <div class="header-main">
        <h1>Welcome, <?= $username ?>!</h1>
        <p>Here is your hotel's operational summary for <?= date('l, F j, Y') ?>.</p>
    </div>
    <div class="header-actions">
        <div class="quick-actions">
            <button class="quick-btn" onclick="location.href='admin_bookings.php'">
                <span class="quick-icon">📅</span>
                <span>New Booking</span>
            </button>
            <button class="quick-btn" onclick="location.href='housekeeping_tasks.php'">
                <span class="quick-icon">🧹</span>
                <span>Housekeeping</span>
            </button>
            <button class="quick-btn" onclick="location.href='admin_rooms.php'">
                <span class="quick-icon">🏨</span>
                <span>Room Status</span>
            </button>
        </div>
    </div>
</div>

<!-- Notifications & Alerts Bar -->
<div class="notifications-bar">
    <?php if ($rooms_maintenance > 0): ?>
        <div class="alert alert-warning">
            <span class="alert-icon">⚠️</span>
            <span><?= $rooms_maintenance ?> room(s) require maintenance attention</span>
            <a href="housekeeping.php" class="alert-action">View Details</a>
        </div>
    <?php endif; ?>
    
    <?php if ($pending_checkins > 0): ?>
        <div class="alert alert-info">
            <span class="alert-icon">🕒</span>
            <span><?= $pending_checkins ?> guest(s) pending check-in today</span>
            <a href="admin_bookings.php" class="alert-action">Process Now</a>
        </div>
    <?php endif; ?>
    
    <?php if ($occupancy_rate > 95): ?>
        <div class="alert alert-success">
            <span class="alert-icon">🎉</span>
            <span>Excellent occupancy rate of <?= round($occupancy_rate, 1) ?>%!</span>
        </div>
    <?php endif; ?>
</div>

<div class="kpi-grid-professional">
    <!-- Occupancy Rate Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= round($occupancy_rate, 1) ?>%</div>
            <div class="kpi-label-pro">Occupancy Rate</div>
            <div class="kpi-sub-pro"><?= $rooms_occupied ?>/<?= $total_rooms ?> rooms</div>
        </div>
        <div class="progress-bar-pro">
            <div class="progress-fill-pro" style="width: <?= $occupancy_rate ?>%"></div>
        </div>
    </div>

    <!-- Average Daily Rate Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro">$<?= number_format($adr, 0) ?></div>
            <div class="kpi-label-pro">Avg. Daily Rate</div>
            <div class="kpi-sub-pro">ADR metric</div>
        </div>
    </div>

    <!-- RevPAR Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro">$<?= number_format($revpar, 0) ?></div>
            <div class="kpi-label-pro">Revenue per Room</div>
            <div class="kpi-sub-pro">RevPAR metric</div>
        </div>
    </div>

    <!-- Arrivals Today Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= $arrivals_today ?></div>
            <div class="kpi-label-pro">Arrivals Today</div>
            <div class="kpi-sub-pro">Expected guests</div>
        </div>
    </div>

    <!-- Departures Today Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= $departures_today ?></div>
            <div class="kpi-label-pro">Departures Today</div>
            <div class="kpi-sub-pro">Check-out schedule</div>
        </div>
    </div>

    <!-- Total Revenue Card -->
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro">$<?= number_format($revenue_today, 0) ?></div>
            <div class="kpi-label-pro">Daily Revenue</div>
            <div class="kpi-sub-pro">Current bookings</div>
        </div>
    </div>
</div>

<!-- Enhanced 3-Column Layout -->
<div class="dashboard-grid mt-30">
    <!-- Revenue Chart - Main Feature -->
    <div class="grid-main">
        <div class="card">
            <h3>Revenue (Last 7 Days)</h3>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity - Prominent Side Panel -->
    <div class="grid-activity">
        <div class="card">
            <h3>Recent Activity</h3>
            <div class="activity-feed">
                <?php
                // Get recent activity from the system
                $activity_query = $conn->query("
                    SELECT 
                        'check-in' as type,
                        CONCAT(u.full_name, ' checked into Room ', r.room_number) as description,
                        b.created_at as timestamp
                    FROM bookings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN rooms r ON b.room_id = r.id 
                    WHERE b.status = 'checked-in' AND DATE(b.created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                    
                    UNION ALL
                    
                    SELECT 
                        'booking' as type,
                        CONCAT('New booking: ', u.full_name, ' for Room ', r.room_number) as description,
                        b.created_at as timestamp
                    FROM bookings b 
                    JOIN users u ON b.user_id = u.id 
                    JOIN rooms r ON b.room_id = r.id 
                    WHERE b.status = 'confirmed' AND DATE(b.created_at) >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                    
                    ORDER BY timestamp DESC 
                    LIMIT 6
                ");
                
                if ($activity_query && $activity_query->num_rows > 0):
                ?>
                    <?php while($activity = $activity_query->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php if ($activity['type'] == 'check-in'): ?>
                                    <span class="icon-circle checkin">✓</span>
                                <?php elseif ($activity['type'] == 'booking'): ?>
                                    <span class="icon-circle booking">+</span>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text"><?= htmlspecialchars($activity['description']) ?></div>
                                <div class="activity-time"><?= timeAgo($activity['timestamp']) ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <span class="icon-circle info">i</span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">No recent activity to display</div>
                            <div class="activity-time">System ready</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Today's Schedule - Compact Side Panel -->
    <div class="grid-schedule">
        <div class="card compact-card">
            <h4>Today's Schedule</h4>
            
            <!-- Arrivals Section -->
            <div class="schedule-section">
                <div class="schedule-header">
                    <span class="schedule-icon">📥</span>
                    <span class="schedule-title">Arrivals (<?= $arrivals_list->num_rows ?>)</span>
                </div>
                <div class="schedule-list">
                    <?php if ($arrivals_list->num_rows > 0): ?>
                        <?php 
                        $arrivals_list->data_seek(0); // Reset pointer
                        $count = 0;
                        while($row = $arrivals_list->fetch_assoc() && $count < 3): 
                            $count++;
                        ?>
                            <div class="schedule-item">
                                <span class="guest-name"><?= htmlspecialchars($row['full_name']) ?></span>
                                <span class="room-number">Room <?= htmlspecialchars($row['room_number']) ?></span>
                            </div>
                        <?php endwhile; ?>
                        <?php if ($arrivals_list->num_rows > 3): ?>
                            <div class="schedule-more">
                                <a href="admin_bookings.php" class="btn-link-style">+<?= $arrivals_list->num_rows - 3 ?> more</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="schedule-empty">No arrivals today</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Departures Section -->
            <div class="schedule-section mt-20">
                <div class="schedule-header">
                    <span class="schedule-icon">📤</span>
                    <span class="schedule-title">Departures (<?= $departures_list->num_rows ?>)</span>
                </div>
                <div class="schedule-list">
                    <?php if ($departures_list->num_rows > 0): ?>
                        <?php 
                        $departures_list->data_seek(0); // Reset pointer
                        $count = 0;
                        while($row = $departures_list->fetch_assoc() && $count < 3): 
                            $count++;
                        ?>
                            <div class="schedule-item">
                                <span class="guest-name"><?= htmlspecialchars($row['full_name']) ?></span>
                                <span class="room-number">Room <?= htmlspecialchars($row['room_number']) ?></span>
                            </div>
                        <?php endwhile; ?>
                        <?php if ($departures_list->num_rows > 3): ?>
                            <div class="schedule-more">
                                <a href="admin_bookings.php" class="btn-link-style">+<?= $departures_list->num_rows - 3 ?> more</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="schedule-empty">No departures today</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function for time formatting
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    return date('M j', strtotime($datetime));
}
?>

<div id="details-modal" class="modal">
  <div class="modal-content">
    <span class="modal-close">&times;</span>
    <h2 id="modal-title">Details</h2>
    <div id="modal-body"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal Logic
    const modal = document.getElementById('details-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const closeModal = document.querySelector('.modal-close');

    // KPI cards are no longer clickable - removed for cleaner professional design

    closeModal.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Enhanced Chart.js Logic
    const chartCanvas = document.getElementById('revenueChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        let revenueChart;

        fetch('/api/get_dashboard_details.php?metric=revenue_chart')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Chart data received:', result); // Debug log
                
                if (revenueChart) {
                    revenueChart.destroy();
                }
                
                if (!result.data || !result.data.labels || !result.data.datasets) {
                    throw new Error('Invalid chart data structure');
                }
                
                revenueChart = new Chart(ctx, {
                    type: 'line',
                    data: result.data,
                    options: { 
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { 
                            y: { 
                                beginAtZero: true,
                                ticks: { 
                                    color: '#ccc',
                                    callback: function(value) {
                                        return '$' + value.toFixed(0);
                                    }
                                },
                                grid: { color: 'rgba(255, 255, 255, 0.1)' }
                            },
                            x: {
                                ticks: { color: '#ccc' },
                                grid: { color: 'rgba(255, 255, 255, 0.1)' }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#ccc' }
                            },
                            tooltip: {
                                displayColors: false,
                                backgroundColor: 'rgba(8, 28, 58, 0.9)',
                                titleColor: '#B6862C',
                                bodyColor: '#fff',
                                borderColor: '#B6862C',
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.raw.toFixed(2);
                                    }
                                }
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully'); // Debug log
            })
            .catch(error => {
                console.error('Error fetching chart data:', error);
                const chartContainer = document.querySelector('.chart-container');
                if (chartContainer) {
                    chartContainer.innerHTML = `
                        <div class="chart-error">
                            <p style="color: #e74c3c; text-align: center; padding: 40px;">
                                <span style="font-size: 2rem;">📊</span><br>
                                Unable to load revenue chart<br>
                                <small style="color: #8892a7;">${error.message}</small>
                            </p>
                        </div>
                    `;
                }
            });
    } else {
        console.error('Revenue chart canvas not found');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>