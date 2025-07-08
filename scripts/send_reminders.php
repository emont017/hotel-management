<?php
/**
 * Automated Booking Reminder & Alert System
 * Hotel Management System - FIU Capstone Project
 * * This script runs daily to automatically send:
 * - 1-day booking reminders
 * - 3-day booking reminders  
 * - Low inventory alerts to admin
 * * USAGE (from your project's root directory):
 * php scripts/send_reminders.php
 */

// Define the project root to make includes reliable from any context
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/config/db.php';
require_once PROJECT_ROOT . '/includes/email_functions.php';

// Set timezone to ensure date calculations are correct
date_default_timezone_set('America/New_York');

/**
 * Send reminders for bookings that are 1 day away
 */
function sendDailyReminders($conn) {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $sql = "
        SELECT 
            b.id,
            b.confirmation_number,
            b.check_in,
            b.check_out,
            b.total_price,
            u.full_name,
            u.email,
            r.room_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        WHERE b.check_in = ? 
        AND b.status = 'confirmed'
        AND u.email IS NOT NULL
        AND u.email != ''
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $tomorrow);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reminders_sent = 0;
    
    while ($booking = $result->fetch_assoc()) {
        $booking_details = [
            'confirmation_number' => $booking['confirmation_number'],
            'room_type' => $booking['room_type'],
            'checkin_date' => $booking['check_in'],
            'checkout_date' => $booking['check_out'],
            'total_price' => $booking['total_price']
        ];
        
        if (sendBookingReminder($booking['email'], $booking['full_name'], $booking_details, 1)) {
            $reminders_sent++;
            echo "1-Day Reminder sent to: " . $booking['email'] . "\n";
        } else {
            echo "Failed to send 1-day reminder to: " . $booking['email'] . "\n";
        }
    }
    
    $stmt->close();
    return $reminders_sent;
}

/**
 * Send reminders for bookings that are 3 days away
 */
function sendThreeDayReminders($conn) {
    $three_days_ahead = date('Y-m-d', strtotime('+3 days'));
    
    $sql = "
        SELECT 
            b.id,
            b.confirmation_number,
            b.check_in,
            b.check_out,
            b.total_price,
            u.full_name,
            u.email,
            r.room_type
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        WHERE b.check_in = ? 
        AND b.status = 'confirmed'
        AND u.email IS NOT NULL
        AND u.email != ''
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $three_days_ahead);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reminders_sent = 0;
    
    while ($booking = $result->fetch_assoc()) {
        $booking_details = [
            'confirmation_number' => $booking['confirmation_number'],
            'room_type' => $booking['room_type'],
            'checkin_date' => $booking['check_in'],
            'checkout_date' => $booking['check_out'],
            'total_price' => $booking['total_price']
        ];
        
        if (sendBookingReminder($booking['email'], $booking['full_name'], $booking_details, 3)) {
            $reminders_sent++;
            echo "3-Day Reminder sent to: " . $booking['email'] . "\n";
        } else {
            echo "Failed to send 3-day reminder to: " . $booking['email'] . "\n";
        }
    }
    
    $stmt->close();
    return $reminders_sent;
}

// Main execution block
echo "=========================================\n";
echo "Starting Hotel Automation Script...\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n\n";

try {
    // Send 1-day reminders
    echo "--- Sending 1-Day Reminders ---\n";
    $daily_reminders = sendDailyReminders($conn);
    echo "Total 1-day reminders sent: $daily_reminders\n\n";
    
    // Send 3-day reminders
    echo "--- Sending 3-Day Reminders ---\n";
    $three_day_reminders = sendThreeDayReminders($conn);
    echo "Total 3-day reminders sent: $three_day_reminders\n\n";
    
    // Check room inventory and send alerts if needed
    echo "--- Checking Room Inventory ---\n";
    $inventory_alerts = checkRoomInventory($conn, 2); // Alert when 2 or fewer rooms available
    echo "Inventory alerts sent: $inventory_alerts\n\n";
    
    echo "=========================================\n";
    echo "Script completed successfully!\n";
    echo "Total Reminders Sent: " . ($daily_reminders + $three_day_reminders) . "\n";
    echo "=========================================\n";
    
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
}

$conn->close();
?>