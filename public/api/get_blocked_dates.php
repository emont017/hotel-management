<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

// Basic validation
if (!isset($_GET['room_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Room type is required.']);
    exit;
}

$room_type = $_GET['room_type'];

try {
    // Get all dates that are blocked for this room type
    // A date is blocked if ALL rooms of this type are booked on that date
    
    // First, get the total number of rooms for this room type
    $count_sql = "SELECT COUNT(*) as total_rooms FROM rooms WHERE room_type = ? AND status = 'available'";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("s", $room_type);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rooms = $count_result->fetch_assoc()['total_rooms'];
    $count_stmt->close();
    
    if ($total_rooms == 0) {
        echo json_encode(['blocked_dates' => [], 'total_rooms' => 0]);
        exit;
    }
    
    // Get all bookings for this room type for the next 2 years
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+2 years'));
    
    $sql = "
        SELECT b.check_in, b.check_out 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE r.room_type = ? 
        AND b.status IN ('confirmed', 'checked-in') 
        AND b.check_out > ? 
        AND b.check_in <= ?
        ORDER BY b.check_in
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $room_type, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Collect all booking date ranges
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    $stmt->close();
    
    // Generate a list of all dates and count bookings per date
    $blocked_dates = [];
    $date_booking_count = [];
    
    foreach ($bookings as $booking) {
        $current_date = new DateTime($booking['check_in']);
        $end_date_obj = new DateTime($booking['check_out']);
        
        // Iterate through each date in the booking range (excluding checkout date)
        while ($current_date < $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            
            if (!isset($date_booking_count[$date_str])) {
                $date_booking_count[$date_str] = 0;
            }
            $date_booking_count[$date_str]++;
            
            $current_date->modify('+1 day');
        }
    }
    
    // Find dates where all rooms are booked
    foreach ($date_booking_count as $date => $count) {
        if ($count >= $total_rooms) {
            $blocked_dates[] = $date;
        }
    }
    
    // Also check for rooms under maintenance
    $maintenance_sql = "SELECT COUNT(*) as maintenance_rooms FROM rooms WHERE room_type = ? AND status = 'maintenance'";
    $maintenance_stmt = $conn->prepare($maintenance_sql);
    $maintenance_stmt->bind_param("s", $room_type);
    $maintenance_stmt->execute();
    $maintenance_result = $maintenance_stmt->get_result();
    $maintenance_rooms = $maintenance_result->fetch_assoc()['maintenance_rooms'];
    $maintenance_stmt->close();
    
    // Adjust available rooms count
    $available_rooms = $total_rooms - $maintenance_rooms;
    
    // Re-check blocked dates with adjusted room count
    $final_blocked_dates = [];
    foreach ($date_booking_count as $date => $count) {
        if ($count >= $available_rooms) {
            $final_blocked_dates[] = $date;
        }
    }
    
    echo json_encode([
        'blocked_dates' => $final_blocked_dates,
        'total_rooms' => $total_rooms,
        'available_rooms' => $available_rooms,
        'room_type' => $room_type
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'A server error occurred. Please try again later.']);
    error_log("Blocked dates API error: " . $e->getMessage());
}
?> 