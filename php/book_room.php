<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/email_functions.php';
require_once __DIR__ . '/../includes/audit_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/index.php");
    exit;
}

function showError($message) {
    $title = "Booking Error";
    require_once __DIR__ . '/../includes/header.php';
    echo "<div class='container text-center'><p class='alert alert-danger'>❌ $message</p>";
    echo "<a href='/bookings.php' class='btn btn-primary'>Go Back to Booking</a></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$conn->begin_transaction();

try {
    $room_type = trim($_POST['room_type'] ?? '');
    $checkin_date = trim($_POST['checkin_date'] ?? '');
    $checkout_date = trim($_POST['checkout_date'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$room_type || !$checkin_date || !$checkout_date || !$full_name || !$email) {
        throw new Exception("Please fill all required fields.");
    }
    if ($checkin_date >= $checkout_date) {
        throw new Exception("Check-out date must be after check-in date.");
    }

    // =========================================================================
    //  DEFINITIVE LOGIC CORRECTION
    //  This block now ensures a NEW guest user is created for this booking,
    //  regardless of who is currently logged in. This keeps public bookings
    //  separate from staff accounts.
    // =========================================================================
    
    // Create a unique username and a random, secure password for the new guest.
    $username = preg_replace("/[^a-z0-9]/i", "", strtolower($full_name)) . rand(100,999);
    $hashed_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $role = 'guest';

    // Insert the new user into the database.
    $stmt_user = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_user->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $role);
    $stmt_user->execute();
    
    // Get the ID of the user we just created.
    $user_id = $stmt_user->insert_id; 
    $stmt_user->close();
    
    // --- End of User Creation Logic ---

    // Find an available room
    $stmt_room = $conn->prepare("SELECT id FROM rooms WHERE room_type = ? AND status = 'available' AND id NOT IN (SELECT room_id FROM bookings WHERE status != 'cancelled' AND ? < check_out AND ? > check_in) LIMIT 1");
    $stmt_room->bind_param("sss", $room_type, $checkin_date, $checkout_date);
    $stmt_room->execute();
    $room_result = $stmt_room->get_result();
    if ($room_result->num_rows === 0) {
        throw new Exception("Sorry, no available rooms of type '$room_type' for the selected dates.");
    }
    $room = $room_result->fetch_assoc();
    $room_id = $room['id'];
    $stmt_room->close();

    // Get the price for the room
    $stmt_rate = $conn->prepare("SELECT price FROM room_rates WHERE room_type = ? AND ? BETWEEN date_start AND date_end ORDER BY rate_name LIMIT 1");
    $stmt_rate->bind_param("ss", $room_type, $checkin_date);
    $stmt_rate->execute();
    $rate_result = $stmt_rate->get_result();
    if($rate_result->num_rows === 0) {
        throw new Exception("No valid pricing found for this room type and date. Please contact the hotel.");
    }
    $rate = $rate_result->fetch_assoc();
    $price_per_night = $rate['price'];
    $stmt_rate->close();

    // Calculate nights and total price
    $checkin_obj = new DateTime($checkin_date);
    $checkout_obj = new DateTime($checkout_date);
    $interval = $checkin_obj->diff($checkout_obj);
    $nights = $interval->days;
    $total_price = $nights * $price_per_night;
    
    // Create the booking using the new guest's user_id
    $confirmation_number = "FIU-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(2)));
    $stmt_booking = $conn->prepare("INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, confirmation_number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt_booking->bind_param("iissds", $user_id, $room_id, $checkin_date, $checkout_date, $total_price, $confirmation_number);
    $stmt_booking->execute();
    $booking_id = $stmt_booking->insert_id;
    $stmt_booking->close();

    // Create the folio for the booking
    $stmt_folio = $conn->prepare("INSERT INTO folios (booking_id, balance) VALUES (?, ?)");
    $stmt_folio->bind_param("id", $booking_id, $total_price);
    $stmt_folio->execute();
    $folio_id = $stmt_folio->insert_id;
    $stmt_folio->close();
    
    // Add the initial charge to the folio
    $initial_charge_desc = "Room & Tax Charge ($nights nights)";
    $stmt_folio_item = $conn->prepare("INSERT INTO folio_items (folio_id, description, amount) VALUES (?, ?, ?)");
    $stmt_folio_item->bind_param("isd", $folio_id, $initial_charge_desc, $total_price);
    $stmt_folio_item->execute();
    $stmt_folio_item->close();

    // Log the booking creation
    log_booking_event($conn, $user_id, 'Booking Created', $booking_id, 
        "New booking: {$confirmation_number}, Room: {$room_type}, Dates: {$checkin_date} to {$checkout_date}, Total: $" . number_format($total_price, 2));

    $conn->commit();
    
    // Automatically log in the newly created guest user so they can view their reservation
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;

} catch (Exception $e) {
    $conn->rollback();
    showError($e->getMessage());
}

// Send confirmation emails
$booking_details = ['confirmation_number' => $confirmation_number, 'room_type' => $room_type, 'checkin_date' => $checkin_date, 'checkout_date' => $checkout_date, 'total_price' => $total_price];
$customer_details = ['name' => $full_name, 'email' => $email, 'phone' => $phone ?: 'Not provided'];
$email_sent = sendBookingConfirmation($email, $full_name, $booking_details);
sendBookingNotificationToHotel($booking_details, $customer_details);

// Display success page
$title = "Booking Confirmation";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card text-center" style="max-width: 600px; margin: 60px auto;">
    <div style="font-size: 4rem; color: #2ecc71; margin-bottom: 20px;">✔️</div>
    <h2>Booking Confirmed!</h2>
    <p><strong>Confirmation Number:</strong> <?= htmlspecialchars($confirmation_number) ?></p>
    
    <?php if ($email_sent): ?>
        <p class="alert alert-success">📧 Confirmation email sent to <?= htmlspecialchars($email) ?>.</p>
    <?php else: ?>
        <p class="alert alert-danger">⚠️ Booking confirmed, but the confirmation email could not be sent.</p>
    <?php endif; ?>

    <div class="details-table" style="text-align: left; margin-top: 20px; border: none;">
        <table>
            <tr><th>Name:</th><td><?= htmlspecialchars($full_name) ?></td></tr>
            <tr><th>Room:</th><td><?= htmlspecialchars($room_type) ?></td></tr>
            <tr><th>Check-in:</th><td><?= htmlspecialchars($checkin_date) ?></td></tr>
            <tr><th>Check-out:</th><td><?= htmlspecialchars($checkout_date) ?></td></tr>
            <tr><th>Total Price:</th><td>$<?= number_format($total_price, 2) ?></td></tr>
        </table>
    </div>
    <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
        <a href="/index.php" class="btn btn-primary">Return to Homepage</a>
        <a href="/manage_reservations.php" class="btn btn-secondary">View My Reservations</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>