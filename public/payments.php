<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'accountant'])) {
    header("Location: /index.php");
    exit;
}

$title = "Payment Management";
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

// Calculate total payments for display
$total_payments = $payments_result->num_rows;
?>

<div class="dashboard-header">
    <div>
        <h1>Payment Management</h1>
        <p>Record new payments, track transaction history, and manage financial records for guest bookings.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom: 30px;">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom: 30px;">
        <span class="alert-icon">⚠️</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
<?php endif; ?>

<!-- Main Content Grid -->
<div class="detail-grid" style="margin-top: 20px;">
    <!-- Payment Form -->
    <div class="card">
        <h3 style="margin-bottom: 10px; color: #B6862C;">Record New Payment</h3>
        <p style="color: #8892a7; margin-bottom: 25px; font-size: 0.9rem;">Process and record payments for guest bookings with automatic details retrieval.</p>
        
        <form id="payment-form" method="post" action="payments.php">
            <div style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
                <div style="flex-grow: 1;">
                    <label for="booking_id" class="form-label">Booking ID:</label>
                    <input type="text" id="booking_id" name="booking_id" class="form-input" placeholder="Enter booking number" required>
                </div>
                <button type="button" id="fetch-details-btn" class="btn btn-secondary">Fetch Details</button>
            </div>

            <label for="guest_name" class="form-label">Guest Name:</label>
            <input type="text" id="guest_name" name="guest_name" class="form-input" placeholder="Will be filled automatically" readonly style="background-color: #f8f9fa;">

            <label for="amount" class="form-label">Payment Amount ($):</label>
            <input type="text" id="amount" name="amount" placeholder="0.00" class="form-input" required>

            <label for="payment_method" class="form-label">Payment Method:</label>
            <select id="payment_method" name="payment_method" class="form-select" required>
                <option value="">Select payment method</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Debit Card">Debit Card</option>
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>

            <label for="transaction_id" class="form-label">Transaction ID:</label>
            <input type="text" id="transaction_id" name="transaction_id" class="form-input" placeholder="Optional - for card payments">

            <label for="notes" class="form-label">Notes:</label>
            <textarea id="notes" name="notes" rows="3" class="form-input" placeholder="Optional notes about this payment"></textarea>

            <button type="submit" name="create_payment" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Record Payment</button>
        </form>
    </div>

    <!-- Quick Stats -->
    <div class="card">
        <h3 style="margin-bottom: 10px; color: #B6862C;">Payment Overview</h3>
        <p style="color: #8892a7; margin-bottom: 20px; font-size: 0.9rem;">Current payment activity and totals.</p>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                <span style="color: #fff; font-size: 0.9rem;">Total Payments</span>
                <span style="color: #B6862C; font-weight: 600; font-size: 0.9rem;"><?= $total_payments ?></span>
            </div>
            
            <?php 
            // Get payment method breakdown
            $method_stats = $conn->query("SELECT payment_method, COUNT(*) as count FROM payments GROUP BY payment_method ORDER BY count DESC LIMIT 4");
            while($method = $method_stats->fetch_assoc()): 
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($method['payment_method']) ?></span>
                    <span style="color: #8892a7; font-weight: 500; font-size: 0.9rem;"><?= $method['count'] ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="card" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="margin: 0; color: #B6862C;">Payment History</h3>
            <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                View and search all recorded payments and transactions
            </p>
        </div>
        <div style="color: #8892a7; font-size: 0.85rem; font-weight: 500;">
            <?= number_format($total_payments) ?> payment<?= $total_payments !== 1 ? 's' : '' ?> recorded
        </div>
    </div>

    <form method="get" action="payments.php" style="margin-bottom: 20px;">
        <label for="search" class="form-label">Search Payments:</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" id="search" placeholder="Search by Booking ID or Guest Name..." 
                   value="<?= htmlspecialchars($search_query) ?>" class="form-input" style="flex: 1;">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="payments.php" class="btn btn-secondary" style="background-color: #6c757d;">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="table-container" style="max-height: 600px; overflow-y: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Payment ID</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Booking ID</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Guest Name</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Amount</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Method</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Transaction ID</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Notes</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Recorded By</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments_result && $payments_result->num_rows > 0): ?>
                    <?php 
                    $payments_result->data_seek(0); // Reset pointer
                    while ($row = $payments_result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?= $row['payment_id'] ?></td>
                            <td>
                                <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>" 
                                   style="color: #B6862C; font-weight: 600;">
                                    #<?= $row['booking_id'] ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                            <td>
                                <span style="font-family: monospace; font-weight: 600; color: #2ecc71;">
                                    $<?= number_format($row['amount'], 2) ?>
                                </span>
                            </td>
                            <td>
                                <span class="role-badge" style="background-color: #3498db; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                    <?= strtoupper(str_replace(' ', '', htmlspecialchars($row['payment_method'] ?? ''))) ?>
                                </span>
                            </td>
                            <td style="color: #8892a7; font-family: monospace; font-size: 0.85rem;">
                                <?= htmlspecialchars($row['transaction_id'] ?? 'N/A') ?>
                            </td>
                            <td style="color: #8892a7; font-size: 0.9rem;">
                                <?= htmlspecialchars($row['notes'] ?? 'N/A') ?>
                            </td>
                            <td style="color: #8892a7;"><?= htmlspecialchars($row['recorded_by_username'] ?? 'N/A') ?></td>
                            <td style="font-size: 0.9rem;">
                                <?= date("M j, Y", strtotime($row['payment_date'])) ?><br>
                                <span style="color: #8892a7; font-size: 0.8rem;">
                                    <?= date("H:i", strtotime($row['payment_date'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: #8892a7;">
                            <div style="font-size: 2rem; margin-bottom: 10px;">💳</div>
                            <div style="font-size: 1.1rem; margin-bottom: 8px;">
                                <?= !empty($search_query) ? 'No payments found matching your search.' : 'No payments recorded yet.' ?>
                            </div>
                            <?php if (empty($search_query)): ?>
                                <div style="font-size: 0.9rem;">Use the form above to record your first payment.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('fetch-details-btn').addEventListener('click', function() {
    const bookingId = document.getElementById('booking_id').value;
    if (!bookingId) {
        alert('Please enter a Booking ID.');
        return;
    }

    // Show loading state
    this.textContent = 'Loading...';
    this.disabled = true;

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
        .catch(error => {
            console.error('Error fetching booking details:', error);
            alert('Error fetching booking details. Please try again.');
        })
        .finally(() => {
            // Reset button state
            this.textContent = 'Fetch Details';
            this.disabled = false;
        });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>