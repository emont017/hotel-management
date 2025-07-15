<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Security: Restrict access to admin/manager roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

$title = "Night Audit";
$user_id = $_SESSION['user_id'];
$feedback_message = $_SESSION['feedback_message'] ?? '';
$feedback_type = $_SESSION['feedback_type'] ?? '';
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);

// --- 1. Fetch Current State Data ---

// Get the current business date from the new settings table
$result = $conn->query("SELECT setting_value FROM settings WHERE setting_name = 'business_date'");
$current_business_date = $result->fetch_assoc()['setting_value'];

// Get the last audit run details from the audit log
$last_audit_res = $conn->query("SELECT l.timestamp, u.full_name FROM audit_logs l JOIN users u ON l.user_id = u.id WHERE l.action = 'Night Audit' ORDER BY l.timestamp DESC LIMIT 1");
$last_audit = $last_audit_res->fetch_assoc();
$last_audit_date = $last_audit['timestamp'] ?? 'N/A';
$last_audit_by = $last_audit['full_name'] ?? 'N/A';

// Get pre-audit checklist numbers
$stmt_departures = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE check_out = ? AND status = 'checked-in'");
$stmt_departures->bind_param("s", $current_business_date);
$stmt_departures->execute();
$pending_departures = $stmt_departures->get_result()->fetch_assoc()['count'];
$stmt_departures->close();

$stmt_noshows = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE check_in = ? AND status = 'confirmed'");
$stmt_noshows->bind_param("s", $current_business_date);
$stmt_noshows->execute();
$potential_no_shows = $stmt_noshows->get_result()->fetch_assoc()['count'];
$stmt_noshows->close();


// --- 2. Handle the "Run Audit" POST Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_audit'])) {
    $conn->begin_transaction();
    try {
        // Step A: Process No-Shows
        $stmt_process_noshows = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE check_in = ? AND status = 'confirmed'");
        $stmt_process_noshows->bind_param("s", $current_business_date);
        $stmt_process_noshows->execute();
        $stmt_process_noshows->close();

        // Step B: Post Room & Tax Charges for all currently checked-in guests
        $stayover_sql = "SELECT b.id as booking_id, f.id as folio_id, rr.price FROM bookings b JOIN folios f ON b.id = f.booking_id JOIN rooms r ON b.room_id = r.id JOIN room_rates rr ON r.room_type = rr.room_type WHERE b.status = 'checked-in' AND ? BETWEEN rr.date_start AND rr.date_end";
        $stmt_stayovers = $conn->prepare($stayover_sql);
        $stmt_stayovers->bind_param("s", $current_business_date);
        $stmt_stayovers->execute();
        $stayovers_result = $stmt_stayovers->get_result();

        $stmt_post_charge = $conn->prepare("INSERT INTO folio_items (folio_id, description, amount) VALUES (?, ?, ?)");
        $stmt_update_balance = $conn->prepare("UPDATE folios SET balance = balance + ? WHERE id = ?");
        
        while ($guest = $stayovers_result->fetch_assoc()) {
            $charge_desc = "Room & Tax: " . $current_business_date;
            $stmt_post_charge->bind_param("isd", $guest['folio_id'], $charge_desc, $guest['price']);
            $stmt_post_charge->execute();

            $stmt_update_balance->bind_param("di", $guest['price'], $guest['folio_id']);
            $stmt_update_balance->execute();
        }
        $stmt_post_charge->close();
        $stmt_update_balance->close();
        $stmt_stayovers->close();

        // Step C: Advance the business date by one day
        $stmt_advance_date = $conn->prepare("UPDATE settings SET setting_value = DATE_ADD(setting_value, INTERVAL 1 DAY) WHERE setting_name = 'business_date'");
        $stmt_advance_date->execute();
        $stmt_advance_date->close();
        
        // Step D: Log the successful audit
        $log_details = "Audit for business date " . $current_business_date . " completed.";
        $stmt_log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'Night Audit', ?)");
        $stmt_log->bind_param("is", $user_id, $log_details);
        $stmt_log->execute();
        $stmt_log->close();

        // If all steps succeeded, commit the transaction
        $conn->commit();
        $_SESSION['feedback_message'] = "Night Audit for " . date("F j, Y", strtotime($current_business_date)) . " completed successfully!";
        $_SESSION['feedback_type'] = 'success';

    } catch (Exception $e) {
        // If any step fails, roll back all changes
        $conn->rollback();
        $_SESSION['feedback_message'] = "Error running Night Audit: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'danger';
    }

    header("Location: night_audit.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 30px auto;">
    <h2 class="text-center">Night Audit</h2>
    <p class="text-center" style="font-size: 1.1rem; max-width: 600px; margin: 10px auto 30px;">
        Finalize the day's business and advance the hotel to the next calendar day.
    </p>

    <?php if ($feedback_message): ?>
        <div class="alert alert-<?= htmlspecialchars($feedback_type) ?>">
            <?= htmlspecialchars($feedback_message) ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-around; text-align: center; margin-bottom: 30px; background-color: #081C3A; padding: 20px; border-radius: 8px;">
        <div>
            <h4 style="color:#B6862C; margin-bottom: 5px;">Current Business Date</h4>
            <p style="color: #fff; font-size: 1.5rem; font-weight: bold; margin:0;"><?= date("F j, Y", strtotime($current_business_date)) ?></p>
        </div>
        <div>
            <h4 style="color:#B6862C; margin-bottom: 5px;">Last Audit Run</h4>
            <p style="color: #fff; font-size: 1.5rem; font-weight: bold; margin:0;"><?= ($last_audit_date !== 'N/A') ? date("M j, Y H:i", strtotime($last_audit_date)) : 'N/A' ?></p>
        </div>
        <div>
            <h4 style="color:#B6862C; margin-bottom: 5px;">Last Audit By</h4>
            <p style="color: #fff; font-size: 1.5rem; font-weight: bold; margin:0;"><?= htmlspecialchars($last_audit_by) ?></p>
        </div>
    </div>
    
    <h3>Pre-Audit Checklist for <?= date("F j, Y", strtotime($current_business_date)) ?></h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Item to Verify</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pending Departures to Process</td>
                <td><strong><?= $pending_departures ?></strong></td>
            </tr>
            <tr>
                <td>Potential No-Shows to Process</td>
                <td><strong><?= $potential_no_shows ?></strong></td>
            </tr>
             <tr>
                <td>Reconcile Daily Payments</td>
                <td><strong style="color: <?= ($pending_departures == 0 && $potential_no_shows == 0) ? '#2ecc71' : '#B6862C' ?>;"><?= ($pending_departures == 0 && $potential_no_shows == 0) ? 'Ready' : 'Pending' ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="mt-30" style="text-align: center; padding: 25px; background: #2E4053; border: 1px solid #B6862C; border-radius: 8px;">
        <p style="font-weight: bold; margin-top: 0; color: #B6862C; font-size: 1.1rem;">This process is IRREVERSIBLE. Confirm all daily tasks are complete.</p>
        <form method="POST" onsubmit="return confirm('This action cannot be undone. Are you absolutely sure you want to run the Night Audit?');">
            <button type="submit" name="run_audit" class="btn btn-danger" style="font-size: 1.2rem; padding: 12px 30px;">RUN NIGHT AUDIT</button>
        </form>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>