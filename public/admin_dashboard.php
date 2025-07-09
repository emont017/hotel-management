<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

$title = "Hotel Dashboard";
require_once __DIR__ . '/../includes/header.php';

// --- ENHANCED Dashboard Data Fetching ---
$today = date('Y-m-d');
$username = htmlspecialchars($_SESSION['username'] ?? 'User');

// --- Core Metrics ---
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

// --- Financial KPIs ---
$revenue_today_query = $conn->query("SELECT SUM(b.total_price / GREATEST(1, DATEDIFF(b.check_out, b.check_in))) as revenue FROM bookings b WHERE b.status = 'checked-in'");
$revenue_today = $revenue_today_query->fetch_assoc()['revenue'] ?? 0;

$adr = ($rooms_occupied > 0) ? $revenue_today / $rooms_occupied : 0;
$revpar = ($total_rooms > 0) ? $revenue_today / $total_rooms : 0;
$occupancy_rate = ($total_rooms > 0) ? ($rooms_occupied / $total_rooms) * 100 : 0;

// --- Action Lists ---
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
    <h1>Welcome, <?= $username ?>!</h1>
    <p>Here is your hotel's operational summary for <?= date('l, F j, Y') ?>.</p>
</div>

<div class="kpi-grid">
    <div class="kpi-card" data-metric="occupied_rooms" title="Click to see details">
        <div class="icon">🏨</div>
        <div class="info">
            <div class="value"><?= $rooms_occupied ?></div>
            <div class="label">Rooms Occupied</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="icon">💲</div>
        <div class="info">
            <div class="value">$<?= number_format($adr, 2) ?></div>
            <div class="label">Avg. Daily Rate (ADR)</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="icon">📈</div>
        <div class="info">
            <div class="value">$<?= number_format($revpar, 2) ?></div>
            <div class="label">Revenue / Room (RevPAR)</div>
        </div>
    </div>
     <div class="kpi-card" data-metric="arrivals" title="Click to see details">
        <div class="icon">🧳</div>
        <div class="info">
            <div class="value"><?= $arrivals_today ?></div>
            <div class="label">Arrivals Today</div>
        </div>
    </div>
</div>

<div class="dashboard-columns mt-30">
    <div class="main-column">
        <div class="card">
            <h3>Revenue (Last 7 Days)</h3>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    <div class="side-column">
        <div class="card">
            <h3>Today's Arrivals (<?= $arrivals_list->num_rows ?>)</h3>
            <table class="data-table">
                <thead><tr><th>Guest</th><th>Room</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if ($arrivals_list->num_rows > 0): ?>
                        <?php while($row = $arrivals_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                                <td><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" class="btn-link-style">View / Check-In</a></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center">No arrivals scheduled for today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card mt-30">
            <h3>Today's Departures (<?= $departures_list->num_rows ?>)</h3>
            <table class="data-table">
                <thead><tr><th>Guest</th><th>Room</th></tr></thead>
                <tbody>
                    <?php if ($departures_list->num_rows > 0): ?>
                        <?php while($row = $departures_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['room_number']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="2" class="text-center">No departures scheduled.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

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

    document.querySelectorAll('.kpi-card[data-metric]').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function() {
            const metric = this.getAttribute('data-metric');
            modalBody.innerHTML = '<div class="loader"></div>';
            modal.style.display = 'block';

            fetch(`/api/get_dashboard_details.php?metric=${metric}`)
                .then(response => response.json())
                .then(result => {
                    modalTitle.textContent = result.title;
                    let tableHTML = '<p>No data to display.</p>';
                    if (result.data && result.data.length > 0) {
                        let headers = Object.keys(result.data[0]);
                        let originalHeaders = [...headers];
                        let displayHeaders = headers.map(h => {
                            if (h === 'booking_id') return 'Actions';
                            return h.replace(/_/g, ' ').toUpperCase();
                        });
                        
                        tableHTML = '<table class="data-table"><thead><tr>';
                        displayHeaders.forEach(headerText => {
                            tableHTML += `<th>${headerText}</th>`;
                        });
                        tableHTML += '</tr></thead><tbody>';
                        
                        result.data.forEach(row => {
                            tableHTML += '<tr>';
                            originalHeaders.forEach(key => {
                                if (key === 'booking_id') {
                                    tableHTML += `<td><a href="admin_booking_detail.php?booking_id=${row[key]}" class="btn-link-style">View Folio</a></td>`;
                                } else {
                                    tableHTML += `<td>${row[key] === null ? 'N/A' : row[key]}</td>`;
                                }
                            });
                            tableHTML += '</tr>';
                        });
                        tableHTML += '</tbody></table>';
                    }
                    modalBody.innerHTML = tableHTML;
                })
                .catch(err => {
                    modalBody.innerHTML = '<p class="alert alert-danger">Could not fetch details.</p>';
                    console.error("Modal fetch error:", err);
                });
        });
    });

    closeModal.onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };

    // Chart.js Logic
    const ctx = document.getElementById('revenueChart').getContext('2d');
    let revenueChart;

    fetch('/api/get_dashboard_details.php?metric=revenue_chart')
        .then(response => response.json())
        .then(result => {
            if (revenueChart) {
                revenueChart.destroy();
            }
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: result.data,
                options: { 
                    scales: { 
                        y: { 
                            beginAtZero: true,
                            ticks: { color: '#ccc' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        x: {
                            ticks: { color: '#ccc' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    },
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: '#ccc' }
                        },
                        tooltip: {
                            // THIS IS THE NEW SETTING
                            displayColors: false
                        }
                    }
                }
            });
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            const chartContainer = document.querySelector('.chart-container');
            chartContainer.innerHTML = '<p class="alert alert-danger">Could not load revenue data.</p>';
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>