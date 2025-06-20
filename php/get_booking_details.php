<?php
session_start();
header('Content-Type: application/json');

// Ensure only logged-in users can access this
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';

$booking_id = (int)($_GET['booking_id'] ?? 0);

if ($booking_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.full_name, b.total_price
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($details = $result->fetch_assoc()) {
        echo json_encode($details);
    } else {
        echo json_encode(['error' => 'Booking not found.']);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid Booking ID.']);
}

$conn->close();
?>