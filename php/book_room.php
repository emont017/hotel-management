<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Trim inputs
$room_type = trim($_POST['room_type'] ?? '');
$checkin_date = trim($_POST['checkin_date'] ?? '');
$checkout_date = trim($_POST['checkout_date'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Basic validation
if (!$room_type || !$checkin_date || !$checkout_date || !$full_name || !$email) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Please fill all required fields.</p>";
    exit;
}

if ($checkin_date >= $checkout_date) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Check-out date must be after check-in date.</p>";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Invalid email format.</p>";
    exit;
}

if ($phone !== '' && !preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Invalid phone number format.</p>";
    exit;
}

// Determine or create user_id
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $base_username = preg_replace("/[^a-z0-9]/", "", strtolower($full_name));
    $username = $base_username . rand(1000, 9999);

    $check_sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    while ($stmt->num_rows > 0) {
        $username = $base_username . rand(1000, 9999);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
    }
    $stmt->close();

    $password_hash = password_hash(bin2hex(random_bytes(5)), PASSWORD_DEFAULT);
    $role = 'friend';

    $insert_user_sql = "INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_user_sql);
    $stmt->bind_param("ssssss", $username, $password_hash, $full_name, $email, $phone, $role);
    if (!$stmt->execute()) {
        echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Error creating user: " . $stmt->error . "</p>";
        exit;
    }
    $user_id = $stmt->insert_id;
    $stmt->close();

    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = 'friend';
    session_regenerate_id(true);
}

// Find available room
$find_room_sql = "
    SELECT r.id, r.price FROM rooms r
    WHERE r.room_type = ?
    AND r.status != 'maintenance'
    AND r.id NOT IN (
        SELECT b.room_id FROM bookings b
        WHERE b.check_in < ? AND b.check_out > ?
    )
    LIMIT 1
";

$stmt = $conn->prepare($find_room_sql);
$stmt->bind_param("sss", $room_type, $checkout_date, $checkin_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Sorry, no available $room_type rooms for the selected dates.</p>";
    echo "<p style='text-align:center;'><a href='../bookings.php'>Go Back to Booking</a></p>";
    exit;
}

$room = $result->fetch_assoc();
$room_id = $room['id'];
$price = $room['price'];

// Calculate total price
$checkin = new DateTime($checkin_date);
$checkout = new DateTime($checkout_date);
$nights = $checkin->diff($checkout)->days;
$total_price = $nights * $price;

// Generate confirmation number (FIU-YYYYMMDD-XXXX)
$confirmation_number = "FIU-" . date("Ymd") . "-" . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

// Insert booking with confirmation number
$insert_booking_sql = "
    INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, confirmation_number)
    VALUES (?, ?, ?, ?, ?, ?)
";

$insert_stmt = $conn->prepare($insert_booking_sql);
$insert_stmt->bind_param("iissds", $user_id, $room_id, $checkin_date, $checkout_date, $total_price, $confirmation_number);

if (!$insert_stmt->execute()) {
    echo "<p style='color: red; font-weight: bold; text-align:center; margin-top:30px;'>❌ Booking failed: " . $insert_stmt->error . "</p>";
    exit;
}

require_once '../includes/header.php';
?>

<style>
    .confirmation-card {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background-color: rgba(247, 178, 35, 0.1);
        border-radius: 15px;
        color: #FFFFFF; /* WHITE text */
        text-align: center;
        box-shadow: 0 0 20px rgba(247,178,35,0.7);
        font-family: 'Roboto', sans-serif;
    }
    .confirmation-card h2 {
        color: #F7B223;
        font-family: 'Orbitron', sans-serif;
        font-size: 3rem;
        margin-bottom: 10px;
    }
    .checkmark {
        font-size: 4rem;
        color: #4BB543; /* green */
        margin-bottom: 20px;
    }
    .confirmation-details p {
        font-size: 1.2rem;
        margin: 8px 0;
        color: #FFFFFF;  /* white */
    }
    .home-button {
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
    }
    .home-button:hover {
        background-color: #e5a91d;
    }
</style>

<div class="confirmation-card">
    <div class="checkmark">&#10004;</div>
    <h2>Booking Confirmed!</h2>
    <p><strong>Confirmation Number:</strong> <?= htmlspecialchars($confirmation_number) ?></p>
    <div class="confirmation-details">
        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
        <p><strong>Room:</strong> <?= htmlspecialchars($room_type) ?></p>
        <p><strong>Check-in:</strong> <?= htmlspecialchars($checkin_date) ?></p>
        <p><strong>Check-out:</strong> <?= htmlspecialchars($checkout_date) ?></p>
        <p><strong>Total Price:</strong> $<?= number_format($total_price, 2) ?></p>
    </div>
    <a href="../index.php" class="home-button">Return to Homepage</a>
</div>

<?php
$insert_stmt->close();
$stmt->close();
$conn->close();
require_once '../includes/footer.php';
?>
