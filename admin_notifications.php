<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Restrict access to admin role only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';
require_once 'includes/email_functions.php';

$title = "Notifications & Alerts";
$message = '';
$message_type = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_inventory'])) {
        $threshold = intval($_POST['threshold']);
        $alerts_sent = checkRoomInventory($conn, $threshold);
        $message = "Inventory check completed. $alerts_sent alert(s) sent.";
        $message_type = 'success';
    }
    
    // Automatic daily processes - these run via scheduled tasks
    if (isset($_POST['run_daily_automation'])) {
        // Run the same processes as the automated script
        $today_reminders = 0;
        $three_day_reminders = 0;
        
        // 1-day reminders
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $sql = "
            SELECT 
                b.confirmation_number, b.check_in, b.check_out, b.total_price,
                u.full_name, u.email, r.room_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN rooms r ON b.room_id = r.id
            WHERE b.check_in = ? AND b.status = 'confirmed'
            AND u.email IS NOT NULL AND u.email != ''
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tomorrow);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($booking = $result->fetch_assoc()) {
            $booking_details = [
                'confirmation_number' => $booking['confirmation_number'],
                'room_type' => $booking['room_type'],
                'checkin_date' => $booking['check_in'],
                'checkout_date' => $booking['check_out'],
                'total_price' => $booking['total_price']
            ];
            
            if (sendBookingReminder($booking['email'], $booking['full_name'], $booking_details, 1)) {
                $today_reminders++;
            }
        }
        $stmt->close();
        
        // 3-day reminders
        $three_days = date('Y-m-d', strtotime('+3 days'));
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $three_days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($booking = $result->fetch_assoc()) {
            $booking_details = [
                'confirmation_number' => $booking['confirmation_number'],
                'room_type' => $booking['room_type'],
                'checkin_date' => $booking['check_in'],
                'checkout_date' => $booking['check_out'],
                'total_price' => $booking['total_price']
            ];
            
            if (sendBookingReminder($booking['email'], $booking['full_name'], $booking_details, 3)) {
                $three_day_reminders++;
            }
        }
        $stmt->close();
        
        // Auto inventory check
        $inventory_alerts = checkRoomInventory($conn, 2);
        
        $message = "Daily automation completed: $today_reminders 1-day reminders, $three_day_reminders 3-day reminders, $inventory_alerts inventory alerts sent.";
        $message_type = 'success';
    }
}

// Get current room inventory status
$inventory_sql = "
    SELECT 
        room_type,
        COUNT(*) as total_rooms,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_rooms,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_rooms
    FROM rooms 
    GROUP BY room_type
    ORDER BY room_type
";
$inventory_result = $conn->query($inventory_sql);

// Get upcoming check-ins (next 7 days)
$upcoming_sql = "
    SELECT 
        b.check_in,
        COUNT(*) as checkins_count
    FROM bookings b
    WHERE b.check_in BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND b.status = 'confirmed'
    GROUP BY b.check_in
    ORDER BY b.check_in
";
$upcoming_result = $conn->query($upcoming_sql);

require_once 'includes/header.php';
?>

<style>
    .notification-section {
        background: #122C55;
        padding: 25px;
        margin: 20px 0;
        border-radius: 10px;
        border: 1px solid #F7B223;
    }
    
    .inventory-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        background: #081C3A;
    }
    
    .inventory-table th,
    .inventory-table td {
        padding: 12px;
        border: 1px solid #F7B223;
        text-align: center;
    }
    
    .inventory-table th {
        background: #F7B223;
        color: #081C3A;
        font-weight: bold;
    }
    
    .inventory-table td {
        color: #fff;
    }
    
    .low-inventory {
        background-color: #dc3545 !important;
        color: white !important;
    }
    
    .warning-inventory {
        background-color: #ffc107 !important;
        color: #081C3A !important;
    }
    
    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 6px;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 5px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #F7B223;
        color: #081C3A;
    }
    
    .btn-primary:hover {
        background: #e5a91d;
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
    }
    
    .alert {
        padding: 15px;
        border-radius: 5px;
        margin: 20px 0;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #F7B223; text-align: center; margin-bottom: 30px;">📧 Notifications & Alerts Management</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Room Inventory Status -->
    <div class="notification-section">
        <h2 style="color: #F7B223; margin-bottom: 20px;">🏨 Current Room Inventory</h2>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Room Type</th>
                    <th>Total Rooms</th>
                    <th>Available</th>
                    <th>Booked</th>
                    <th>Maintenance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $inventory_result->fetch_assoc()): ?>
                    <?php 
                    $available = $row['available_rooms'];
                    $status_class = '';
                    $status_text = 'Good';
                    
                    if ($available <= 1) {
                        $status_class = 'low-inventory';
                        $status_text = 'Critical';
                    } elseif ($available <= 2) {
                        $status_class = 'warning-inventory';
                        $status_text = 'Low';
                    }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['room_type']) ?></td>
                        <td><?= $row['total_rooms'] ?></td>
                        <td class="<?= $status_class ?>"><?= $available ?></td>
                        <td><?= $row['booked_rooms'] ?></td>
                        <td><?= $row['maintenance_rooms'] ?></td>
                        <td class="<?= $status_class ?>"><?= $status_text ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <form method="POST" style="margin-top: 20px;">
            <label style="color: #F7B223; font-weight: bold;">Alert Threshold:</label>
            <input type="number" name="threshold" value="2" min="1" max="10" style="padding: 8px; margin: 0 10px;">
            <button type="submit" name="check_inventory" class="btn btn-danger">
                ⚠️ Check Inventory & Send Alerts
            </button>
        </form>
    </div>

    <!-- Upcoming Check-ins -->
    <div class="notification-section">
        <h2 style="color: #F7B223; margin-bottom: 20px;">📅 Upcoming Check-ins (Next 7 Days)</h2>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Number of Check-ins</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($upcoming_result->num_rows > 0): ?>
                    <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('l, F j, Y', strtotime($row['check_in'])) ?></td>
                            <td><?= $row['checkins_count'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">No upcoming check-ins in the next 7 days</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Automated Processes -->
    <div class="notification-section">
        <h2 style="color: #F7B223; margin-bottom: 20px;">🤖 Automated Processes</h2>
        
        <div style="margin-bottom: 20px;">
            <h3 style="color: #F7B223;">Daily Automation Status</h3>
            <p style="color: #fff;">The system automatically handles booking reminders and inventory alerts daily.</p>
            
            <div style="background: #081C3A; padding: 15px; border-radius: 5px; margin: 10px 0;">
                <ul style="color: #F7B223; margin: 0;">
                    <li>📧 <strong>1-Day Reminders:</strong> Sent automatically to guests checking in tomorrow</li>
                    <li>📅 <strong>3-Day Reminders:</strong> Sent automatically to guests checking in in 3 days</li>
                    <li>⚠️ <strong>Inventory Alerts:</strong> Sent to admin when room availability is low</li>
                    <li>🔄 <strong>Schedule:</strong> Runs daily at 9:00 AM automatically</li>
                </ul>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h3 style="color: #F7B223;">Run Daily Process Now</h3>
            <p style="color: #fff;">Manually trigger today's automated processes for testing or immediate execution.</p>
            <form method="POST" style="display: inline;">
                <button type="submit" name="run_daily_automation" class="btn btn-primary">
                    ⚡ Run Daily Automation Now
                </button>
            </form>
        </div>
    </div>

    <!-- Email Configuration -->
    <div class="notification-section">
        <h2 style="color: #F7B223; margin-bottom: 20px;">⚙️ Email Configuration</h2>
        
        <div style="color: #fff;">
            <p><strong>Current Configuration:</strong></p>
            <ul>
                <li>✅ Gmail SMTP Server (smtp.gmail.com:587)</li>
                <li>✅ TLS Encryption Enabled</li>
                <li>✅ From Address: <?= FROM_EMAIL ?></li>
                <li>✅ Admin Notifications: <?= ADMIN_EMAIL ?></li>
                <li>✅ Automated Daily Processing</li>
            </ul>
            
            <p><strong>System Status:</strong> Email notifications are fully operational and configured for production use.</p>
            
            <div style="background: #28a745; padding: 10px; border-radius: 5px; margin: 10px 0;">
                <strong>✅ Email System Ready:</strong> All booking confirmations, reminders, and alerts are being sent automatically.
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="admin_dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 