<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$bookingId = $data['bookingId'] ?? null;
$newRoomId = $data['newRoomId'] ?? null;
$newStartDate = $data['newStartDate'] ?? null;

if (!$bookingId || !$newRoomId || !$newStartDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$stmt = $conn->prepare("SELECT check_in, check_out FROM bookings WHERE id = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}
$booking = $result->fetch_assoc();
$stmt->close();

$checkIn = new DateTime($booking['check_in']);
$checkOut = new DateTime($booking['check_out']);
$duration = $checkIn->diff($checkOut)->days;

$newCheckIn = new DateTime($newStartDate);
$newCheckOut = clone $newCheckIn;
$newCheckOut->modify("+$duration days");

$newCheckInStr = $newCheckIn->format('Y-m-d');
$newCheckOutStr = $newCheckOut->format('Y-m-d');

$updateStmt = $conn->prepare("UPDATE bookings SET room_id = ?, check_in = ?, check_out = ? WHERE id = ?");
$updateStmt->bind_param("issi", $newRoomId, $newCheckInStr, $newCheckOutStr, $bookingId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update booking']);
}

$updateStmt->close();
$conn->close();
