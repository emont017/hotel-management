<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: /index.php");
    exit;
}

$title = "Manage Payments";
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
        $stmt_insert = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, transaction_id, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_insert->bind_param("idsssi", $booking_id, $amount, $payment_method, $transaction_id, $notes, $recorded_by);
        
        if ($stmt_insert->execute()) {
            $payment_id = $stmt_insert->insert_id;
            
            // Log payment creation
            log_payment_event($conn, $recorded_by, 'Payment Recorded', $payment_id, 
                "Payment of $" . number_format($amount, 2) . " via {$payment_method} for booking #{$booking_id}");
            
            $message = "Payment recorded successfully!";
        } else {
            $error = "Error recording payment: " . $stmt_insert->error;
        }
        $stmt_insert->close();
    }
}

require_once __DIR__ . '/../includes/header.php';

// Handle Search
$search_query = $_GET['search'] ?? '';
$sql_params = [];
$sql_search_condition = ''; // FIX: Initialize the variable

if (!empty($search_query)) {
    $sql_search_condition = "WHERE b.id LIKE ? OR u.full_name LIKE ?";
    $search_term = "%{$search_query}%";
    array_push($sql_params, $search_term, $search_term);
}

// Fetch all payments
$payments_sql = "SELECT p.id AS payment_id, p.booking_id, p.amount, p.payment_method, p.payment_date, p.transaction_id, p.notes, u.full_name AS guest_name, recorder.username AS recorded_by_username FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN users u ON b.user_id = u.id LEFT JOIN users recorder ON p.recorded_by = recorder.id {$sql_search_condition} ORDER BY p.payment_date DESC";
$stmt_fetch = $conn->prepare($payments_sql);
if (!empty($sql_params)) {
    $stmt_fetch->bind_param(str_repeat('s', count($sql_params)), ...$sql_params);
}
$stmt_fetch->execute();
$payments_result = $stmt_fetch->get_result();
?>

<h2>Manage Payments</h2>

<?php if ($message): ?><p class="alert alert-success"><?= htmlspecialchars($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="alert alert-danger"><?= htmlspecialchars($error) ?></p><?php endif; ?>

<form id="payment-form" method="post" action="payments.php" class="card mt-30 mb-20">
    <h3>Record New Payment</h3>
    <div style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 15px;">
        <div style="flex-grow: 1;">
            <label for="booking_id" class="form-label">Booking ID:</label>
            <input type="text" id="booking_id" name="booking_id" class="form-input" required>
        </div>
        <button type="button" id="fetch-details-btn" class="btn btn-primary">Fetch Details</button>
    </div>
    <div style="display: flex; gap: 15px; margin-bottom: 15px;">
        <div style="flex-basis: 50%;">
            <label for="guest_name" class="form-label">Guest Name:</label>
            <input type="text" id="guest_name" name="guest_name" class="form-input" readonly>
        </div>
        <div style="flex-basis: 50%;">
            <label for="amount" class="form-label">Amount ($):</label>
            <input type="text" id="amount" name="amount" placeholder="e.g., 120.50" class="form-input" required>
        </div>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="payment_method" class="form-label">Payment Method:</label>
        <select id="payment_method" name="payment_method" class="form-select" required>
            <option value="Credit Card">Credit Card</option>
            <option value="Debit Card">Debit Card</option>
            <option value="Cash">Cash</option>
            <option value="Bank Transfer">Bank Transfer</option>
        </select>
    </div>
    <div style="margin-bottom: 15px;">
        <label for="transaction_id" class="form-label">Transaction ID (Optional):</label>
        <input type="text" id="transaction_id" name="transaction_id" class="form-input">
    </div>
     <div style="margin-bottom: 20px;">
        <label for="notes" class="form-label">Notes (Optional):</label>
        <textarea id="notes" name="notes" rows="3" class="form-input"></textarea>
    </div>
    <input type="submit" name="create_payment" value="Record Payment" class="btn btn-primary" style="font-size: 1.1rem;">
</form>

<h3>Payment History</h3>
<form method="get" action="payments.php" class="mb-20">
    <input type="text" name="search" placeholder="Search by Booking ID or Guest Name..." value="<?= htmlspecialchars($search_query) ?>" class="form-input" style="width: 300px; display: inline-block;">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<div style="overflow-x: auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Payment ID</th> <th>Booking ID</th> <th>Guest Name</th> <th>Amount</th> <th>Method</th> <th>Transaction ID</th> <th>Notes</th> <th>Recorded By</th> <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($payments_result && $payments_result->num_rows > 0): ?>
                <?php while ($row = $payments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['payment_id'] ?></td>
                        <td><a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>"><?= $row['booking_id'] ?></a></td>
                        <td><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                        <td>$<?= number_format($row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['payment_method'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['transaction_id'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['notes'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['recorded_by_username'] ?? 'N/A') ?></td>
                        <td><?= date("Y-m-d H:i", strtotime($row['payment_date'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No payments found.</td>
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

    // Corrected URL to point to the new API endpoint
    fetch(`/api/get_booking_details.php?booking_id=${bookingId}`)
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>