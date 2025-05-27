<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    echo json_encode([]);
    exit;
}

$room_id = intval($_GET['room_id']);


$bookings_sql = "
    SELECT check_in, check_out
    FROM bookings
    WHERE room_id = ?
";

$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param('i', $room_id);
$stmt->execute();
$result = $stmt->get_result();

$events = [];


while ($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => 'Booked',
        'start' => $row['check_in'],
        'end' => date('Y-m-d', strtotime($row['check_out'] . ' +1 day')),
        'status' => 'booked',
    ];
}


$room_sql = "SELECT status FROM rooms WHERE id = ?";
$stmt2 = $conn->prepare($room_sql);
$stmt2->bind_param('i', $room_id);
$stmt2->execute();
$stmt2->bind_result($room_status);
$stmt2->fetch();
$stmt2->close();

if ($room_status === 'maintenance') {
    $today = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+1 month'));
    $events[] = [
        'title' => 'Maintenance',
        'start' => $today,
        'end' => $endDate,
        'status' => 'maintenance',
    ];
}

echo json_encode($events);
