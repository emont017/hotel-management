<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: index.php");
    exit;
}

$title = "Manage Payments";
require_once 'includes/header.php';
require_once 'php/db.php';

$message = '';
$error = '';

// Handle Create Payment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_payment'])) {
    $booking_id = (int)$_POST['booking_id'];
    $amount = (float)$_POST['amount'];
    $payment_method = trim($_POST['payment_method']);
    $transaction_id = !empty($_POST['transaction_id']) ? trim($_POST['transaction_id']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    $recorded_by = (int)$_SESSION['user_id'];

    if (empty($booking_id) || empty($amount) || empty($payment_method)) {
        $error = "Booking ID, Amount, and Payment Method are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, transaction_id, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", $booking_id, $amount, $payment_method, $transaction_id, $notes, $recorded_by);
        
        if ($stmt->execute()) {
            $message = "Payment recorded successfully!";
        } else {
            $error = "Error recording payment: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Search
$search_query = $_GET['search'] ?? '';
$sql_search_condition = '';
if (!empty($search_query)) {
    $sql_search_condition = "WHERE b.id LIKE ? OR u.full_name LIKE ?";
}

// Fetch all payments with booking and user details
$payments_sql = "
    SELECT 
        p.id AS payment_id,
        p.booking_id,
        p.amount,
        p.payment_method,
        p.payment_date,
        p.transaction_id,
        p.notes,
        u.full_name AS guest_name,
        recorder.username AS recorded_by_username
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    LEFT JOIN users recorder ON p.recorded_by = recorder.id
    {$sql_search_condition}
    ORDER BY p.payment_date DESC
";
$stmt = $conn->prepare($payments_sql);
if (!empty($search_query)) {
    $search_term = "%{$search_query}%";
    $stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$payments_result = $stmt->get_result();
?>

<h2 style="color: #F7B223;">💰 Manage Payments</h2>

<!-- Status Messages -->
<?php if ($message): ?><p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<?php if ($error): ?><p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<!-- Create Payment Form -->
<form id="payment-form" method="post" action="payments.php" style="margin-top: 30px; margin-bottom: 50px; max-width: 700px; background-color: rgba(255,255,255,0.05); padding: 25px; border-radius: 10px;">
    <h3 style="color: #fff; margin-top: 0;">➕ Record New Payment</h3>
    <div style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 15px;">
        <div style="flex-grow: 1;">
            <label for="booking_id" style="font-weight: bold;">Booking ID:</label>
            <input type="text" id="booking_id" name="booking_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: none;">
        </div>
        <button type="button" id="fetch-details-btn" style="padding: 10px 15px; background-color: #081C3A; color: #F7B223; border: 1px solid #F7B223; font-weight: bold; cursor: pointer; border-radius: 6px;">Fetch Details</button>
    </div>
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div style="flex-basis: 50%;">
            <label for="guest_name" style="font-weight: bold;">Guest Name:</label>
            <input type="text" id="guest_name" name="guest_name" readonly style="width: 100%; padding: 10px; border-radius: 6px; border: none; background-color: #e9ecef;">
        </div>
        <div style="flex-basis: 50%;">
            <label for="amount" style="font-weight: bold;">Amount ($):</label>
            <input type="text" id="amount" name="amount" placeholder="e.g., 120.50" required style="width: 100%; padding: 10px; border-radius: 6px; border: none;">
        </div>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="payment_method" style="font-weight: bold;">Payment Method:</label>
        <select id="payment_method" name="payment_method" required style="width: 100%; padding: 10px; border-radius: 6px; border: none;">
            <option value="Credit Card">Credit Card</option>
            <option value="Debit Card">Debit Card</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
        </select>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="transaction_id" style="font-weight: bold;">Transaction ID (Optional):</label>
        <input type="text" id="transaction_id" name="transaction_id" style="width: 100%; padding: 10px; border-radius: 6px; border: none;">
    </div>
     <div style="margin-bottom: 20px;">
        <label for="notes" style="font-weight: bold;">Notes (Optional):</label>
        <textarea id="notes" name="notes" rows="3" style="width: 100%; padding: 10px; border-radius: 6px; border: none;"></textarea>
    </div>
    <input type="submit" name="create_payment" value="Record Payment" style="padding: 12px 25px; background-color: #F7B223; border: none; color: #081C3A; font-weight: bold; cursor: pointer; border-radius: 6px; font-size: 1.1rem;">
</form>

<!-- Payments Table -->
<h3 style="color: #F7B223;">📋 Payment History</h3>
<form method="get" action="payments.php" style="margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Search by Booking ID or Guest Name..." value="<?= htmlspecialchars($search_query) ?>" style="width: 300px; padding: 10px; border-radius: 6px; border: none;">
    <button type="submit" style="padding: 10px 15px; background-color: #F7B223; border: none; color: #081C3A; font-weight: bold; cursor: pointer; border-radius: 6px;">Search</button>
</form>

<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <thead>
            <tr style="background-color: #081E3F; color: white; text-align: left;">
                <th style="padding: 12px; border: 1px solid #ddd;">Payment ID</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Booking ID</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Guest Name</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Amount</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Method</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Transaction ID</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Notes</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Recorded By</th>
                <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($payments_result && $payments_result->num_rows > 0): ?>
                <?php $i = 0; while ($row = $payments_result->fetch_assoc()): ?>
                    <?php $bg = ($i++ % 2 === 0) ? "#f8f9fa" : "#ffffff"; ?>
                    <tr style="background-color: <?= $bg ?>; color: #081E3F;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='<?= $bg ?>'">
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['payment_id'] ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>"><?= $row['booking_id'] ?></a></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">$<?= number_format($row['amount'], 2) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['payment_method'] ?? '') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['transaction_id'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['notes'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['recorded_by_username'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= date("Y-m-d H:i", strtotime($row['payment_date'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr style="background-color: #122C55; color: #fff;">
                    <td colspan="9" style="padding: 15px; border: 1px solid #081E3F; text-align: center;">No payments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('fetch-details-btn').addEventListener('click', function() {
    const bookingId = document.getElementById('booking_id').value;
    if (!bookingId) {
        alert('Please enter a Booking ID.');
        return;
    }

    fetch(`php/get_booking_details.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                document.getElementById('guest_name').value = '';
                document.getElementById('amount').value = '';
            } else {
                document.getElementById('guest_name').value = data.full_name;
                document.getElementById('amount').value = data.total_price;
            }
        })
        .catch(error => console.error('Error fetching booking details:', error));
});
</script>

<?php require_once 'includes/footer.php'; ?>
