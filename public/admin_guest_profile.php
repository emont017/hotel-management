<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Security Check: Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

// Validate guest_id parameter
if (!isset($_GET['guest_id']) || !is_numeric($_GET['guest_id'])) {
    header("Location: users.php");
    exit;
}

$guest_id = intval($_GET['guest_id']);
$feedback_message = '';
$feedback_type = '';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle preferences form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    if (isset($_POST['update_preferences'])) {
        $preferred_room_type = $_POST['preferred_room_type'] ?? null;
        $floor_preference = $_POST['floor_preference'] ?? null;
        $bed_preference = $_POST['bed_preference'] ?? 'no-preference';
        $amenities_requested = trim($_POST['amenities_requested']) ?: null;
        $special_requests = trim($_POST['special_requests']) ?: null;
        $dietary_restrictions = trim($_POST['dietary_restrictions']) ?: null;
        $accessibility_needs = trim($_POST['accessibility_needs']) ?: null;
        $marketing_consent = isset($_POST['marketing_consent']) ? 1 : 0;
        $notes = trim($_POST['notes']) ?: null;

        // Check if preferences already exist
        $check_stmt = $conn->prepare("SELECT id FROM guest_preferences WHERE user_id = ?");
        $check_stmt->bind_param("i", $guest_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing) {
            // Update existing preferences
            $update_stmt = $conn->prepare("UPDATE guest_preferences SET 
                preferred_room_type = ?, floor_preference = ?, 
                bed_preference = ?, amenities_requested = ?, special_requests = ?, 
                dietary_restrictions = ?, accessibility_needs = ?, marketing_consent = ?, 
                notes = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = ?");
            $update_stmt->bind_param("sssssssssi", $preferred_room_type, $floor_preference, 
                $bed_preference, $amenities_requested, $special_requests,
                $dietary_restrictions, $accessibility_needs, $marketing_consent, $notes, $guest_id);
            
            if ($update_stmt->execute()) {
                $feedback_message = "Guest preferences updated successfully!";
                $feedback_type = 'success';
                log_audit_event($conn, $_SESSION['user_id'], 'Guest Preferences Updated', 'guest_preferences', $existing['id'], "Updated preferences for guest ID: {$guest_id}");
            } else {
                $feedback_message = "Error updating preferences: " . $update_stmt->error;
                $feedback_type = 'danger';
            }
            $update_stmt->close();
        } else {
            // Insert new preferences
            $insert_stmt = $conn->prepare("INSERT INTO guest_preferences 
                (user_id, preferred_room_type, floor_preference, 
                bed_preference, amenities_requested, special_requests, dietary_restrictions, 
                accessibility_needs, marketing_consent, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("isssssssss", $guest_id, $preferred_room_type, $floor_preference, 
                $bed_preference, $amenities_requested, $special_requests,
                $dietary_restrictions, $accessibility_needs, $marketing_consent, $notes);
            
            if ($insert_stmt->execute()) {
                $feedback_message = "Guest preferences created successfully!";
                $feedback_type = 'success';
                log_audit_event($conn, $_SESSION['user_id'], 'Guest Preferences Created', 'guest_preferences', $insert_stmt->insert_id, "Created preferences for guest ID: {$guest_id}");
            } else {
                $feedback_message = "Error creating preferences: " . $insert_stmt->error;
                $feedback_type = 'danger';
            }
            $insert_stmt->close();
        }
    }
}

// Fetch guest details
$guest_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'guest'");
$guest_stmt->bind_param("i", $guest_id);
$guest_stmt->execute();
$guest = $guest_stmt->get_result()->fetch_assoc();
$guest_stmt->close();

if (!$guest) {
    header("Location: users.php");
    exit;
}

// Fetch guest preferences
$prefs_stmt = $conn->prepare("SELECT * FROM guest_preferences WHERE user_id = ?");
$prefs_stmt->bind_param("i", $guest_id);
$prefs_stmt->execute();
$preferences = $prefs_stmt->get_result()->fetch_assoc();
$prefs_stmt->close();

// Fetch booking history
$bookings_stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.room_type, r.image_path,
           DATEDIFF(b.check_out, b.check_in) as nights_stayed
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.check_in DESC
");
$bookings_stmt->bind_param("i", $guest_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

// Calculate summary statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(b.id) as total_bookings,
        COALESCE(SUM(p.amount), 0) as total_spent,
        COALESCE(SUM(DATEDIFF(b.check_out, b.check_in)), 0) as total_nights,
        AVG(DATEDIFF(b.check_out, b.check_in)) as avg_stay_length,
        MAX(b.check_out) as last_stay,
        MIN(b.check_in) as first_stay
    FROM bookings b 
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.user_id = ?
");
$stats_stmt->bind_param("i", $guest_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Calculate customer lifetime value and tier
$total_spent = floatval($stats['total_spent']);
$customer_tier = 'Bronze';
if ($total_spent >= 5000) {
    $customer_tier = 'Platinum';
} elseif ($total_spent >= 2000) {
    $customer_tier = 'Gold';
} elseif ($total_spent >= 500) {
    $customer_tier = 'Silver';
}

$title = "Guest Profile - " . htmlspecialchars($guest['full_name'] ?: $guest['username']);
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Guest Profile</h2>
<nav style="margin-bottom: 20px;">
    <a href="users.php" class="btn btn-outline">← Back to User Management</a>
</nav>

<?php if ($feedback_message): ?>
<div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($feedback_message) ?>
</div>
<?php endif; ?>

<!-- Guest Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <!-- Guest Details Card -->
    <div class="card">
        <h3>Guest Information</h3>
        <div style="line-height: 1.6;">
            <p><strong>Name:</strong> <?= htmlspecialchars($guest['full_name'] ?: 'N/A') ?></p>
            <p><strong>Username:</strong> <?= htmlspecialchars($guest['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($guest['email'] ?: 'N/A') ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($guest['phone'] ?: 'N/A') ?></p>
            <p><strong>First Stay:</strong> <?= $stats['first_stay'] ? date('M j, Y', strtotime($stats['first_stay'])) : 'No bookings yet' ?></p>
            <p><strong>Customer Tier:</strong> 
                <span class="badge badge-<?= strtolower($customer_tier) ?>"><?= $customer_tier ?></span>
            </p>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="card">
        <h3>Guest Statistics</h3>
        <div style="line-height: 1.6;">
            <p><strong>Total Bookings:</strong> <?= $stats['total_bookings'] ?></p>
            <p><strong>Total Spent:</strong> $<?= number_format($total_spent, 2) ?></p>
            <p><strong>Total Nights:</strong> <?= $stats['total_nights'] ?></p>
            <p><strong>Avg Stay Length:</strong> <?= $stats['avg_stay_length'] ? round($stats['avg_stay_length'], 1) . ' nights' : 'N/A' ?></p>
            <p><strong>First Stay:</strong> <?= $stats['first_stay'] ? date('M j, Y', strtotime($stats['first_stay'])) : 'N/A' ?></p>
            <p><strong>Last Stay:</strong> <?= $stats['last_stay'] ? date('M j, Y', strtotime($stats['last_stay'])) : 'Never' ?></p>
        </div>
    </div>
</div>

<!-- Guest Preferences Section -->
<div class="card" style="margin-bottom: 30px;">
    <h3>Guest Preferences</h3>
    
    <form method="post" action="admin_guest_profile.php?guest_id=<?= $guest_id ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
            <div>
                <label class="form-label">Preferred Room Type:</label>
                <select name="preferred_room_type" class="form-select">
                    <option value="">No Preference</option>
                    <option value="Double Room" <?= ($preferences['preferred_room_type'] ?? '') === 'Double Room' ? 'selected' : '' ?>>Double Room</option>
                    <option value="Executive Suite" <?= ($preferences['preferred_room_type'] ?? '') === 'Executive Suite' ? 'selected' : '' ?>>Executive Suite</option>
                    <option value="Suite with Balcony" <?= ($preferences['preferred_room_type'] ?? '') === 'Suite with Balcony' ? 'selected' : '' ?>>Suite with Balcony</option>
                </select>
            </div>
            
            <div>
                <label class="form-label">Floor Preference:</label>
                <select name="floor_preference" class="form-select">
                    <option value="">No Preference</option>
                    <option value="low" <?= ($preferences['floor_preference'] ?? '') === 'low' ? 'selected' : '' ?>>Lower Floors (1-2)</option>
                    <option value="mid" <?= ($preferences['floor_preference'] ?? '') === 'mid' ? 'selected' : '' ?>>Middle Floors (2-3)</option>
                    <option value="high" <?= ($preferences['floor_preference'] ?? '') === 'high' ? 'selected' : '' ?>>Higher Floors (3+)</option>
                </select>
            </div>

            <div>
                <label class="form-label">Bed Preference:</label>
                <select name="bed_preference" class="form-select">
                    <option value="no-preference" <?= ($preferences['bed_preference'] ?? '') === 'no-preference' ? 'selected' : '' ?>>No Preference</option>
                    <option value="single" <?= ($preferences['bed_preference'] ?? '') === 'single' ? 'selected' : '' ?>>Single</option>
                    <option value="double" <?= ($preferences['bed_preference'] ?? '') === 'double' ? 'selected' : '' ?>>Double</option>
                    <option value="queen" <?= ($preferences['bed_preference'] ?? '') === 'queen' ? 'selected' : '' ?>>Queen</option>
                    <option value="king" <?= ($preferences['bed_preference'] ?? '') === 'king' ? 'selected' : '' ?>>King</option>
                    <option value="twin" <?= ($preferences['bed_preference'] ?? '') === 'twin' ? 'selected' : '' ?>>Twin</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label class="form-label">Amenities Requested:</label>
                <textarea name="amenities_requested" class="form-input" rows="3" placeholder="e.g., WiFi, Coffee Machine, Balcony"><?= htmlspecialchars($preferences['amenities_requested'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="form-label">Special Requests:</label>
                <textarea name="special_requests" class="form-input" rows="3" placeholder="e.g., Late checkout, Extra towels, Quiet room"><?= htmlspecialchars($preferences['special_requests'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label class="form-label">Dietary Restrictions:</label>
                <textarea name="dietary_restrictions" class="form-input" rows="3" placeholder="e.g., Vegetarian, Gluten-free, Allergies"><?= htmlspecialchars($preferences['dietary_restrictions'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="form-label">Accessibility Needs:</label>
                <textarea name="accessibility_needs" class="form-input" rows="3" placeholder="e.g., Wheelchair accessible, Ground floor, Wide doors"><?= htmlspecialchars($preferences['accessibility_needs'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label class="form-label">
                <input type="checkbox" name="marketing_consent" value="1" <?= ($preferences['marketing_consent'] ?? 0) ? 'checked' : '' ?>>
                Marketing Communications Consent
            </label>
        </div>

        <div style="margin-bottom: 20px;">
            <label class="form-label">Staff Notes:</label>
            <textarea name="notes" class="form-input" rows="4" placeholder="Internal notes about this guest's preferences, behavior, or special considerations"><?= htmlspecialchars($preferences['notes'] ?? '') ?></textarea>
        </div>

        <button type="submit" name="update_preferences" class="btn btn-primary">Update Preferences</button>
    </form>
</div>

<!-- Booking History Section -->
<div class="card">
    <h3>Booking History</h3>
    
    <?php if ($bookings_result->num_rows > 0): ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Room</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Nights</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $booking['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($booking['room_number']) ?><br>
                            <small><?= htmlspecialchars($booking['room_type']) ?></small>
                        </td>
                        <td><?= date('M j, Y', strtotime($booking['check_in'])) ?></td>
                        <td><?= date('M j, Y', strtotime($booking['check_out'])) ?></td>
                        <td><?= $booking['nights_stayed'] ?></td>
                        <td>$<?= number_format($booking['total_price'], 2) ?></td>
                        <td>
                            <span class="badge badge-<?= $booking['status'] === 'completed' ? 'success' : ($booking['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="admin_booking_detail.php?booking_id=<?= $booking['id'] ?>" class="btn-link-style">View Details</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No booking history found for this guest.</p>
    <?php endif; ?>
</div>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}
.badge-bronze { background-color: #cd7f32; color: white; }
.badge-silver { background-color: #c0c0c0; color: black; }
.badge-gold { background-color: #ffd700; color: black; }
.badge-platinum { background-color: #e5e4e2; color: black; }
.badge-success { background-color: #28a745; color: white; }
.badge-warning { background-color: #ffc107; color: black; }
.badge-danger { background-color: #dc3545; color: white; }
</style>

<?php
$bookings_stmt->close();
require_once __DIR__ . '/../includes/footer.php';
?> 