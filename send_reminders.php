<?php
/**
 * Automated Booking Reminder & Alert System
 * Hotel Management System - FIU Capstone Project
 * 
 * This script runs daily to automatically send:
 * - 1-day booking reminders
 * - 3-day booking reminders  
 * - Low inventory alerts to admin
 * 
 * WINDOWS AUTOMATION SETUP:
 * 1. Open Task Scheduler (taskschd.msc)
 * 2. Create Basic Task
 * 3. Name: "Hotel Booking Reminders"
 * 4. Trigger: Daily at 9:00 AM
 * 5. Action: Start a program
 * 6. Program: C:\xampp\php\php.exe
 * 7. Arguments: C:\xampp\htdocs\hotel-management\send_reminders.php
 * 
 * Usage: php send_reminders.php
 */

require_once 'db_connection.php';
require_once 'includes/email_functions.php';

// Set timezone
date_default_timezone_set('America/New_York'); // Adjust as needed

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
            echo "Reminder sent to: " . $booking['email'] . " for booking " . $booking['confirmation_number'] . "\n";
        } else {
            echo "Failed to send reminder to: " . $booking['email'] . "\n";
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
            echo "3-day reminder sent to: " . $booking['email'] . " for booking " . $booking['confirmation_number'] . "\n";
        } else {
            echo "Failed to send 3-day reminder to: " . $booking['email'] . "\n";
        }
    }
    
    $stmt->close();
    return $reminders_sent;
}

// Main execution
echo "Starting booking reminder process...\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Send 1-day reminders
    echo "Sending 1-day reminders...\n";
    $daily_reminders = sendDailyReminders($conn);
    echo "1-day reminders sent: $daily_reminders\n\n";
    
    // Send 3-day reminders
    echo "Sending 3-day reminders...\n";
    $three_day_reminders = sendThreeDayReminders($conn);
    echo "3-day reminders sent: $three_day_reminders\n\n";
    
    // Check room inventory and send alerts if needed
    echo "Checking room inventory...\n";
    $inventory_alerts = checkRoomInventory($conn, 2); // Alert when 2 or fewer rooms available
    echo "Inventory alerts sent: $inventory_alerts\n\n";
    
    echo "Reminder process completed successfully!\n";
    echo "Total reminders sent: " . ($daily_reminders + $three_day_reminders) . "\n";
    echo "Inventory alerts sent: $inventory_alerts\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?> 