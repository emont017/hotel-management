<?php
session_start();
$title = "Booking Confirmation";
require_once '../includes/header.php';
require_once 'db.php';
require_once '../includes/email_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$room_type = trim($_POST['room_type'] ?? '');
$checkin_date = trim($_POST['checkin_date'] ?? '');
$checkout_date = trim($_POST['checkout_date'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

function showError($message) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ $message</p>";
    echo "<p style='text-align:center;'><a href='../bookings.php'>Go Back to Booking</a></p>";
    require_once '../includes/footer.php';
    exit;
}

if (!$room_type || !$checkin_date || !$checkout_date || !$full_name || !$email) {
    showError("Please fill all required fields.");
}

if ($checkin_date >= $checkout_date) {
    showError("Check-out date must be after check-in date.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showError("Invalid email format.");
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
    showError("Invalid phone number format.");
}

// Auto-register guest if not logged in
if (!isset($_SESSION['user_id'])) {
    $username = preg_replace("/[^a-z0-9]/", "", strtolower($full_name)) . rand(1000, 9999);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    while ($stmt->num_rows > 0) {
        $username = preg_replace("/[^a-z0-9]/", "", strtolower($full_name)) . rand(1000, 9999);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
    }
    $stmt->close();

    $hashed_password = password_hash(bin2hex(random_bytes(5)), PASSWORD_DEFAULT);
    $role = 'friend';

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $role);
    if (!$stmt->execute()) {
        showError("Error creating user: " . $stmt->error);
    }
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['role'] = $role;
    session_regenerate_id(true);
    $stmt->close();
}

$user_id = $_SESSION['user_id'];

// Check for available room
$stmt = $conn->prepare("
    SELECT r.id, r.price FROM rooms r
    WHERE r.room_type = ? AND r.status != 'maintenance'
    AND r.id NOT IN (
        SELECT b.room_id FROM bookings b
        WHERE b.check_in < ? AND b.check_out > ?
    )
    LIMIT 1
");
$stmt->bind_param("sss", $room_type, $checkout_date, $checkin_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    showError("Sorry, no available $room_type rooms for the selected dates.");
}

$room = $result->fetch_assoc();
$room_id = $room['id'];
$price = $room['price'];

$checkin = new DateTime($checkin_date);
$checkout = new DateTime($checkout_date);
$nights = $checkin->diff($checkout)->days;
$total_price = $nights * $price;

$confirmation_number = "FIU-" . date("Ymd") . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

$stmt = $conn->prepare("
    INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, confirmation_number)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("iissds", $user_id, $room_id, $checkin_date, $checkout_date, $total_price, $confirmation_number);

if (!$stmt->execute()) {
    showError("Booking failed: " . $stmt->error);
}

// Send booking confirmation email to customer
$booking_details = [
    'confirmation_number' => $confirmation_number,
    'room_type' => $room_type,
    'checkin_date' => $checkin_date,
    'checkout_date' => $checkout_date,
    'total_price' => $total_price
];

$customer_details = [
    'name' => $full_name,
    'email' => $email,
    'phone' => $phone ?: 'Not provided'
];

$email_sent = sendBookingConfirmation($email, $full_name, $booking_details);

// Send booking notification to hotel management
$hotel_notified = sendBookingNotificationToHotel($booking_details, $customer_details);

$stmt->close();
$conn->close();
?>

<div style="
    max-width: 600px;
    margin: 40px auto;
    padding: 30px;
    background-color: rgba(247, 178, 35, 0.1);
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 0 20px rgba(247,178,35,0.7);
    font-family: 'Roboto', sans-serif;
">
    <div style="font-size: 4rem; color: green; margin-bottom: 20px;">✔️</div>
    <h2 style="color: #081C3A; font-family: 'Orbitron', sans-serif; font-size: 2.5rem;">Booking Confirmed!</h2>
    <p style="font-size: 1.1rem;"><strong>Confirmation Number:</strong> <?= htmlspecialchars($confirmation_number) ?></p>
    <?php if ($email_sent): ?>
        <p style="color: green; font-size: 1rem;">📧 Confirmation email sent to <?= htmlspecialchars($email) ?></p>
        <p style="color: blue; font-size: 0.9rem;">✨ Check your inbox for the confirmation email!</p>
    <?php else: ?>
        <p style="color: orange; font-size: 1rem;">⚠️ Booking confirmed, but email could not be sent</p>
        <p style="color: gray; font-size: 0.9rem;">Please contact us to confirm your booking details</p>
    <?php endif; ?>
    
    <?php if ($hotel_notified): ?>
        <p style="color: green; font-size: 0.9rem;">🏨 Hotel management has been notified</p>
    <?php endif; ?>
    <div style="margin-top: 20px; font-size: 1.1rem; text-align: left;">
        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
        <p><strong>Room:</strong> <?= htmlspecialchars($room_type) ?></p>
        <p><strong>Check-in:</strong> <?= htmlspecialchars($checkin_date) ?></p>
        <p><strong>Check-out:</strong> <?= htmlspecialchars($checkout_date) ?></p>
        <p><strong>Total Price:</strong> $<?= number_format($total_price, 2) ?></p>
    </div>
    <a href="../index.php" style="
        display: inline-block;
        margin-top: 25px;
        padding: 12px 25px;
        background-color: #F7B223;
        color: #081C3A;
        font-weight: bold;
        font-size: 1.2rem;
        border-radius: 12px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">Return to Homepage</a>
</div>

<?php require_once '../includes/footer.php'; ?>
