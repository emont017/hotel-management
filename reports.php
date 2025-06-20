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

    if (empty($booking_id) || empty($amount) || empty($payment_method)) {
        $error = "Booking ID, Amount, and Payment Method are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, transaction_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $booking_id, $amount, $payment_method, $transaction_id);
        
        if ($stmt->execute()) {
            $message = "Payment recorded successfully!";
        } else {
            $error = "Error recording payment: " . $stmt->error;
        }
        $stmt->close();
    }
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
        u.full_name AS guest_name
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    ORDER BY p.payment_date DESC
";
$payments_result = $conn->query($payments_sql);
?>

<h2 style="color: #F7B223;">💰 Manage Payments</h2>

<!-- Status Messages -->
<?php if ($message): ?>
    <p style="color: green; font-weight: bold;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<?php if ($error): ?>
    <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>


<!-- Create Payment Form -->
<form method="post" action="payments.php" style="margin-top: 30px; margin-bottom: 50px; max-width: 600px; background-color: rgba(255,255,255,0.05); padding: 20px; border-radius: 10px;">
    <h3 style="color: #fff; margin-top: 0;">➕ Record New Payment</h3>
    <input type="number" name="booking_id" placeholder="Booking ID" required style="width: 100%; padding: 10px; margin: 8px 0; border-radius: 6px; border: none;"><br>
    <input type="number" step="0.01" name="amount" placeholder="Amount (e.g., 120.50)" required style="width: 100%; padding: 10px; margin: 8px 0; border-radius: 6px; border: none;"><br>
    <input type="text" name="payment_method" placeholder="Payment Method (e.g., Credit Card)" required style="width: 100%; padding: 10px; margin: 8px 0; border-radius: 6px; border: none;"><br>
    <input type="text" name="transaction_id" placeholder="Transaction ID (Optional)" style="width: 100%; padding: 10px; margin: 8px 0; border-radius: 6px; border: none;"><br>
    <input type="submit" name="create_payment" value="Record Payment" style="padding: 10px 20px; background-color: #F7B223; border: none; color: #081C3A; font-weight: bold; cursor: pointer; border-radius: 6px;">
</form>

<!-- Payments Table -->
<h3 style="color: #F7B223;">📋 Payment History</h3>
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
                <th style="padding: 12px; border: 1px solid #ddd;">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($payments_result && $payments_result->num_rows > 0): ?>
                <?php $i = 0; while ($row = $payments_result->fetch_assoc()): ?>
                    <?php $bg = ($i++ % 2 === 0) ? "#f8f9fa" : "#ffffff"; ?>
                    <tr style="background-color: <?= $bg ?>; color: #081E3F;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='<?= $bg ?>'">
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['payment_id'] ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">
                            <a href="admin_booking_detail.php?booking_id=<?= $row['booking_id'] ?>"><?= $row['booking_id'] ?></a>
                        </td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['guest_name'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">$<?= number_format($row['amount'], 2) ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['payment_method'] ?? '') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['transaction_id'] ?? 'N/A') ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?= date("Y-m-d H:i", strtotime($row['payment_date'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr style="background-color: #122C55; color: #fff;">
                    <td colspan="7" style="padding: 15px; border: 1px solid #081E3F; text-align: center;">No payments found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>