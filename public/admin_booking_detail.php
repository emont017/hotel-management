<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    header("Location: /admin_bookings.php");
    exit;
}

$booking_id = intval($_GET['booking_id']);
// Use session for feedback messages to survive redirects
$feedback_message = $_SESSION['feedback_message'] ?? '';
$feedback_type = $_SESSION['feedback_type'] ?? '';
unset($_SESSION['feedback_message'], $_SESSION['feedback_type']);


// Handle POST Actions
// Handle Check-In
if (isset($_POST['check_in_guest'])) {
    $room_id = (int)$_POST['room_id'];
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-in' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'occupied' WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        log_booking_event($conn, $admin_user_id, 'Guest Checked In', $booking_id, "Guest checked in by admin: {$admin_username}");
        $conn->commit();
        $_SESSION['feedback_message'] = "Guest successfully checked in!";
        $_SESSION['feedback_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['feedback_message'] = "Error checking in guest: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'danger';
    }
    header("Location: admin_booking_detail.php?booking_id={$booking_id}");
    exit;
}

// Handle Check-Out
if (isset($_POST['check_out_guest'])) {
    $room_id = (int)$_POST['room_id'];
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-out' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'dirty' WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE folios SET status = 'closed' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        log_booking_event($conn, $admin_user_id, 'Guest Checked Out', $booking_id, "Guest checked out by admin: {$admin_username}");
        $conn->commit();
        $_SESSION['feedback_message'] = "Guest successfully checked out!";
        $_SESSION['feedback_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['feedback_message'] = "Error checking out guest: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'danger';
    }
    header("Location: admin_booking_detail.php?booking_id={$booking_id}");
    exit;
}

// Handle Re-Check-In
if (isset($_POST['re_check_in_guest'])) {
    $room_id = (int)$_POST['room_id'];
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-in' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'occupied' WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE folios SET status = 'open' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        log_booking_event($conn, $admin_user_id, 'Guest Re-Checked In', $booking_id, "Guest re-checked in by admin: {$admin_username}");
        $conn->commit();
        $_SESSION['feedback_message'] = "Guest successfully re-checked in!";
        $_SESSION['feedback_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['feedback_message'] = "Error re-checking in guest: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'danger';
    }
    header("Location: admin_booking_detail.php?booking_id={$booking_id}");
    exit;
}


    
   if (isset($_POST['check_out_guest'])) {
    $room_id = (int)$_POST['room_id'];
    $conn->begin_transaction();
    try {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-out' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        // Update housekeeping status
        $stmt = $conn->prepare("UPDATE rooms SET housekeeping_status = 'dirty' WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $stmt->close();

        // Update folio status
        $stmt = $conn->prepare("UPDATE folios SET status = 'closed' WHERE booking_id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();

        // Log event
        log_booking_event($conn, $admin_user_id, 'Guest Checked Out', $booking_id, "Guest checked out by admin: {$admin_username}");
        
        $conn->commit();
        $_SESSION['feedback_message'] = "Guest successfully checked out!";
        $_SESSION['feedback_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['feedback_message'] = "Error checking out guest: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'danger';
    }
    header("Location: admin_booking_detail.php?booking_id={$booking_id}");
    exit;
}

    
    if (isset($_POST['re_check_in_guest'])) {
        $room_id = (int)$_POST['room_id'];
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE bookings SET status = 'checked-in' WHERE id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
            $conn->prepare("UPDATE rooms SET housekeeping_status = 'occupied' WHERE id = ?")->execute([$room_id]);
            $conn->prepare("UPDATE folios SET status = 'open' WHERE booking_id = ?")->execute([$booking_id]);
            log_booking_event($conn, $admin_user_id, 'Guest Re-Checked In', $booking_id, "Guest re-checked in by admin: {$admin_username}");
            $conn->commit();
            $_SESSION['feedback_message'] = "Guest successfully re-checked in!";
            $_SESSION['feedback_type'] = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['feedback_message'] = "Error re-checking in guest: " . $e->getMessage();
            $_SESSION['feedback_type'] = 'danger';
        }
        header("Location: admin_booking_detail.php?booking_id={$booking_id}");
        exit;
    }

    // --- NEW: Handle Folio Transactions ---
    if (isset($_POST['post_charge']) || isset($_POST['post_payment'])) {
        $folio_id = (int)$_POST['folio_id'];
        $description = trim($_POST['description']);
        $amount = (float)$_POST['amount'];
        $is_payment = isset($_POST['post_payment']);

        if ($folio_id > 0 && !empty($description) && $amount > 0) {
            $transaction_amount = $is_payment ? -$amount : $amount;
            $log_action = $is_payment ? 'Payment Posted' : 'Charge Posted';
            $log_details = ($is_payment ? 'Payment' : 'Charge') . " of $" . number_format($amount, 2) . " for '{$description}' posted by {$admin_username}";

            $conn->begin_transaction();
            try {
                // 1. Insert the new item into the folio
                $stmt_item = $conn->prepare("INSERT INTO folio_items (folio_id, description, amount) VALUES (?, ?, ?)");
                $stmt_item->bind_param("isd", $folio_id, $description, $transaction_amount);
                $stmt_item->execute();
                $stmt_item->close();

                // 2. Update the total balance on the folio
                $stmt_balance = $conn->prepare("UPDATE folios SET balance = balance + ? WHERE id = ?");
                $stmt_balance->bind_param("di", $transaction_amount, $folio_id);
                $stmt_balance->execute();
                $stmt_balance->close();
                
                // 3. Log the financial event
                log_financial_event($conn, $admin_user_id, $log_action, 'folios', $folio_id, $log_details);
                
                $conn->commit();
                $_SESSION['feedback_message'] = ($is_payment ? 'Payment' : 'Charge') . " posted successfully!";
                $_SESSION['feedback_type'] = 'success';

            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['feedback_message'] = "Error posting transaction: " . $e->getMessage();
                $_SESSION['feedback_type'] = 'danger';
            }
        } else {
            $_SESSION['feedback_message'] = "Invalid input. Please provide a description and a positive amount.";
            $_SESSION['feedback_type'] = 'danger';
        }
        header("Location: admin_booking_detail.php?booking_id={$booking_id}");
        exit;
    }

// --- Data Fetching ---
// Fetch Booking, User, and Room details
$sql_booking = "SELECT b.id AS booking_id, b.room_id, u.id AS user_id, u.username, u.full_name, u.email, u.phone, r.room_number, r.room_type, r.housekeeping_status, b.check_in, b.check_out, b.total_price, b.status FROM bookings b JOIN users u ON b.user_id = u.id JOIN rooms r ON b.room_id = r.id WHERE b.id = ?";
$stmt_booking = $conn->prepare($sql_booking);
$stmt_booking->bind_param("i", $booking_id);
$stmt_booking->execute();
$booking = $stmt_booking->get_result()->fetch_assoc();
$stmt_booking->close();

// Fetch Folio and Folio Items
$folio = null;
$folio_items = [];
if ($booking) {
    $stmt_folio = $conn->prepare("SELECT * FROM folios WHERE booking_id = ?");
    $stmt_folio->bind_param("i", $booking_id);
    $stmt_folio->execute();
    $folio = $stmt_folio->get_result()->fetch_assoc();
    $stmt_folio->close();

    if ($folio) {
        $stmt_items = $conn->prepare("SELECT * FROM folio_items WHERE folio_id = ? ORDER BY timestamp ASC");
        $stmt_items->bind_param("i", $folio['id']);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $folio_items[] = $item;
        }
        $stmt_items->close();
    }
}

$title = "Booking Details";
require_once __DIR__ . '/../includes/header.php';

if (!$booking) {
    echo "<p class='alert alert-danger'>Booking not found.</p>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<a href="/admin_bookings.php" class="btn btn-secondary mb-20">← Back to Manage Bookings</a>

<?php if ($feedback_message): ?>
    <div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($feedback_message) ?>
    </div>
<?php endif; ?>

<div class="detail-grid">
    <div class="detail-main">
        <div class="card">
            <h2>Booking Details #<?= $booking['booking_id'] ?></h2>
            <table class="details-table" style="margin-top: 20px;">
                <tr><th>Guest Name:</th><td><a href="admin_guest_profile.php?guest_id=<?= $booking['user_id'] ?>" class="btn-link-style"><?= htmlspecialchars($booking['full_name']) ?></a></td></tr>
                <tr><th>Email:</th><td><?= htmlspecialchars($booking['email']) ?></td></tr>
                <tr><th>Phone:</th><td><?= htmlspecialchars($booking['phone']) ?></td></tr>
                <tr><th>Booking Status:</th><td class="text-capitalize"><b><?= htmlspecialchars($booking['status']) ?></b></td></tr>
                <tr><th>Room Number:</th><td><?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type']) ?>)</td></tr>
                <tr><th>Housekeeping:</th><td class="text-capitalize"><?= htmlspecialchars($booking['housekeeping_status']) ?></td></tr>
                <tr><th>Check-in Date:</th><td><?= htmlspecialchars($booking['check_in']) ?></td></tr>
                <tr><th>Check-out Date:</th><td><?= htmlspecialchars($booking['check_out']) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="detail-sidebar">
        <div class="card">
            <h3>Actions</h3>
            <div style="display: flex; flex-direction:column; gap: 10px; margin-top: 15px;">
                <?php if ($booking['status'] === 'confirmed'): ?>
                    <form method="POST" onsubmit="return confirm('Check in this guest?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="check_in_guest" class="btn btn-primary" style="width:100%;">Check-in Guest</button>
                    </form>
                <?php endif; ?>

                <?php if ($booking['status'] === 'checked-in'): ?>
                    <form method="POST" onsubmit="return confirm('Check out this guest?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="check_out_guest" class="btn btn-danger" style="width:100%;">Check-out Guest</button>
                    </form>
                <?php endif; ?>
                
                <?php if ($booking['status'] === 'checked-out'): ?>
                    <form method="POST" onsubmit="return confirm('Revert to checked-in status?');">
                        <input type="hidden" name="room_id" value="<?= $booking['room_id'] ?>">
                        <button type="submit" name="re_check_in_guest" class="btn btn-secondary" style="width:100%;">Re-Check-in</button>
                    </form>
                <?php endif; ?>
                
                <a href="admin_booking_edit.php?booking_id=<?= $booking['booking_id'] ?>" class="btn btn-secondary">Edit Booking Dates</a>
            </div>
        </div>

        <div class="card mt-30">
            <h3>Folio Summary</h3>
            <?php if ($folio): ?>
                <table class="folio-summary-table">
                    <?php foreach($folio_items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td class="amount" style="color: <?= $item['amount'] < 0 ? '#2ecc71' : '#ffffff' ?>;">
                                $<?= number_format(abs($item['amount']), 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <div class="folio-balance">
                    <span>Balance Due:</span> $<?= number_format($folio['balance'] ?? 0.00, 2) ?>
                </div>

                <?php // --- NEW: Folio Transaction Form --- ?>
                <?php if ($folio['status'] === 'open'): ?>
                <div class="folio-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #122C55;">
                    <h4>Post Transaction</h4>
                    <form method="POST">
                        <input type="hidden" name="folio_id" value="<?= $folio['id'] ?>">
                        <div style="margin-bottom: 10px;">
                            <label for="description" class="form-label">Description:</label>
                            <input type="text" id="description" name="description" class="form-input" placeholder="e.g., Room Service, Parking" required>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="amount" class="form-label">Amount ($):</label>
                            <input type="number" id="amount" name="amount" class="form-input" step="0.01" min="0.01" placeholder="50.00" required>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="post_charge" class="btn btn-primary" style="flex: 1;">Post Charge</button>
                            <button type="submit" name="post_payment" class="btn btn-secondary" style="flex: 1;">Post Payment</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                    <p style="text-align: center; margin-top: 20px; color: #aaa; font-style: italic;">Folio is closed. No new transactions allowed.</p>
                <?php endif; ?>

            <?php else: ?>
                <p>No folio found for this booking.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>