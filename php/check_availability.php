<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// Basic validation
if (!isset($_GET['check_in']) || !isset($_GET['check_out'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Check-in and check-out dates are required.']);
    exit;
}

$check_in = $_GET['check_in'];
$check_out = $_GET['check_out'];

if ($check_in >= $check_out) {
    http_response_code(400);
    echo json_encode(['error' => 'Check-out date must be after check-in date.']);
    exit;
}

try {
    // Find room types that have at least one room available for the entire date range
    $sql = "
        SELECT
            r.room_type,
            r.capacity,
            (SELECT rr.price FROM room_rates rr WHERE rr.room_type = r.room_type AND ? BETWEEN rr.date_start AND rr.date_end LIMIT 1) AS price_per_night
        FROM rooms r
        WHERE r.status = 'available' AND r.id NOT IN (
            SELECT b.room_id FROM bookings b
            WHERE b.status != 'cancelled' AND ? < b.check_out AND ? > b.check_in
        )
        GROUP BY r.room_type, r.capacity
        HAVING price_per_night IS NOT NULL
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $check_in, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result();

    $available_rooms = [];
    while ($row = $result->fetch_assoc()) {
        $available_rooms[] = $row;
    }
    $stmt->close();
    $conn->close();

    echo json_encode($available_rooms);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'A server error occurred. Please try again later.']);
}
?>