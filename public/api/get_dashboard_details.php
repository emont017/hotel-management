<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$metric = $_GET['metric'] ?? '';
$response = ['title' => 'Details', 'data' => []];

switch ($metric) {
    case 'occupied_rooms':
        $response['title'] = 'Currently Occupied Rooms';
        $sql = "SELECT r.room_number, u.full_name, b.check_in, b.check_out, b.id as booking_id FROM bookings b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE b.status = 'checked-in' ORDER BY r.room_number ASC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $response['data'][] = $row;
            }
        }
        break;

    case 'arrivals':
        $response['title'] = "Today's Arrivals";
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT u.full_name, r.room_number, b.status, b.id as booking_id FROM bookings b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE b.check_in = ? AND b.status = 'confirmed'");
        $stmt->bind_param("s", $today);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
        $stmt->close();
        break;

    case 'revenue_chart':
        $response['title'] = 'Revenue Last 7 Days';
        $labels = [];
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('M j', strtotime($date));
            
            $stmt = $conn->prepare("SELECT SUM(total_price / GREATEST(1, DATEDIFF(check_out, check_in))) as daily_revenue FROM bookings WHERE status = 'checked-in' AND ? BETWEEN check_in AND DATE_SUB(check_out, INTERVAL 1 DAY)");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $data[] = $result['daily_revenue'] ?? 0;
        }
        $response['data'] = ['labels' => $labels, 'datasets' => [['label' => 'Daily Revenue', 'data' => $data, 'borderColor' => '#F7B223', 'backgroundColor' => 'rgba(247, 178, 35, 0.2)', 'fill' => true, 'tension' => 0.4]]];
        break;
}

$conn->close();
echo json_encode($response);
?>