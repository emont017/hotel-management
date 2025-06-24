<?php
/**
 * Email Functions for Hotel Management System
 * Handles booking confirmations, cancellations, and reminders
 */

require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';

/**
 * Send email using SMTP
 */
function sendEmail($to_email, $to_name, $subject, $html_body) {
    $mail = new PHPMailer();
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->From = FROM_EMAIL;
        $mail->FromName = FROM_NAME;
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->IsHTML = true;
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        
        $result = $mail->send();
        if (!$result) {
            error_log("Email sending failed - check SMTP configuration");
        }
        return $result;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send booking confirmation email
 */
function sendBookingConfirmation($email, $name, $booking_details) {
    $subject = "Booking Confirmation - " . $booking_details['confirmation_number'];
    
    $message = "
    <html>
    <head>
        <title>Booking Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #081C3A; color: #F7B223; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { color: #333; line-height: 1.6; }
            .booking-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Hotel Management System</h1>
                <h2>Booking Confirmation</h2>
            </div>
            
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                
                <p>Thank you for your booking! Your reservation has been confirmed.</p>
                
                <div class='booking-details'>
                    <h3>📋 Booking Details</h3>
                    <p><strong>Confirmation Number:</strong> " . htmlspecialchars($booking_details['confirmation_number']) . "</p>
                    <p><strong>Room Type:</strong> " . htmlspecialchars($booking_details['room_type']) . "</p>
                    <p><strong>Check-in Date:</strong> " . htmlspecialchars($booking_details['checkin_date']) . "</p>
                    <p><strong>Check-out Date:</strong> " . htmlspecialchars($booking_details['checkout_date']) . "</p>
                    <p><strong>Total Price:</strong> $" . number_format($booking_details['total_price'], 2) . "</p>
                </div>
                
                <p>We look forward to welcoming you to our hotel!</p>
                
                <p>If you have any questions, please don't hesitate to contact us.</p>
                
                <p><strong>Contact Information:</strong><br>
                Email: <?= FROM_EMAIL ?><br>
                Professional Hotel Management System</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Hotel Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $name, $subject, $message);
}

/**
 * Send booking cancellation email
 */
function sendCancellationEmail($email, $name, $booking_details) {
    $subject = "Booking Cancelled - " . $booking_details['confirmation_number'];
    
    $message = "
    <html>
    <head>
        <title>Booking Cancellation</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { color: #333; line-height: 1.6; }
            .booking-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Hotel Management System</h1>
                <h2>Booking Cancellation</h2>
            </div>
            
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                
                <p>Your booking has been cancelled as requested.</p>
                
                <div class='booking-details'>
                    <h3>📋 Cancelled Booking Details</h3>
                    <p><strong>Confirmation Number:</strong> " . htmlspecialchars($booking_details['confirmation_number']) . "</p>
                    <p><strong>Room Type:</strong> " . htmlspecialchars($booking_details['room_type']) . "</p>
                    <p><strong>Check-in Date:</strong> " . htmlspecialchars($booking_details['checkin_date']) . "</p>
                    <p><strong>Check-out Date:</strong> " . htmlspecialchars($booking_details['checkout_date']) . "</p>
                </div>
                
                <p>If you cancelled by mistake or have any questions, please contact us immediately.</p>
                
                <p>We hope to serve you again in the future!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Hotel Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $name, $subject, $message);
}

/**
 * Send booking reminder email
 */
function sendBookingReminder($email, $name, $booking_details, $days_until_checkin) {
    $subject = "Booking Reminder - Check-in in " . $days_until_checkin . " day(s)";
    
    $message = "
    <html>
    <head>
        <title>Booking Reminder</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #F7B223; color: #081C3A; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { color: #333; line-height: 1.6; }
            .booking-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            .reminder-box { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Hotel Management System</h1>
                <h2>Booking Reminder</h2>
            </div>
            
            <div class='content'>
                <p>Dear " . htmlspecialchars($name) . ",</p>
                
                <div class='reminder-box'>
                    <h3>⏰ Reminder: Your check-in is in " . $days_until_checkin . " day(s)!</h3>
                </div>
                
                <p>We wanted to remind you about your upcoming stay with us.</p>
                
                <div class='booking-details'>
                    <h3>📋 Your Booking Details</h3>
                    <p><strong>Confirmation Number:</strong> " . htmlspecialchars($booking_details['confirmation_number']) . "</p>
                    <p><strong>Room Type:</strong> " . htmlspecialchars($booking_details['room_type']) . "</p>
                    <p><strong>Check-in Date:</strong> " . htmlspecialchars($booking_details['checkin_date']) . "</p>
                    <p><strong>Check-out Date:</strong> " . htmlspecialchars($booking_details['checkout_date']) . "</p>
                    <p><strong>Total Price:</strong> $" . number_format($booking_details['total_price'], 2) . "</p>
                </div>
                
                <p><strong>Check-in Information:</strong></p>
                <ul>
                    <li>Check-in time: 3:00 PM</li>
                    <li>Check-out time: 11:00 AM</li>
                    <li>Please bring a valid ID</li>
                </ul>
                
                <p>We're excited to welcome you!</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Hotel Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $name, $subject, $message);
}

/**
 * Send low inventory alert to admin
 */
function sendLowInventoryAlert($admin_email, $room_type, $available_count, $threshold) {
    $subject = "⚠️ Low Room Inventory Alert - " . $room_type;
    
    $message = "
    <html>
    <head>
        <title>Low Inventory Alert</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { color: #333; line-height: 1.6; }
            .alert-box { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Hotel Management System</h1>
                <h2>Low Inventory Alert</h2>
            </div>
            
            <div class='content'>
                <div class='alert-box'>
                    <h3>⚠️ Low Room Inventory Warning</h3>
                    <p><strong>Room Type:</strong> " . htmlspecialchars($room_type) . "</p>
                    <p><strong>Available Rooms:</strong> " . $available_count . "</p>
                    <p><strong>Alert Threshold:</strong> " . $threshold . "</p>
                </div>
                
                <p>The available inventory for <strong>" . htmlspecialchars($room_type) . "</strong> has fallen below the alert threshold.</p>
                
                <p><strong>Recommended Actions:</strong></p>
                <ul>
                    <li>Check room maintenance status</li>
                    <li>Review upcoming bookings</li>
                    <li>Consider adjusting room availability</li>
                    <li>Monitor booking patterns</li>
                </ul>
                
                <p>Please log into the admin dashboard to review the current room status.</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Hotel Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($admin_email, 'Hotel Administrator', $subject, $message);
}

/**
 * Get admin email for notifications
 */
function getAdminEmail($conn) {
    // First try to get admin email from database
    $stmt = $conn->prepare("SELECT email FROM users WHERE role = 'admin' AND email IS NOT NULL LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    // Return database email if exists, otherwise use configured admin email
    return $admin ? $admin['email'] : ADMIN_EMAIL;
}

/**
 * Check for low room inventory and send alerts
 */
function checkRoomInventory($conn, $threshold = 2) {
    // Get available room counts by type
    $sql = "
        SELECT 
            room_type,
            COUNT(*) as total_rooms,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_rooms
        FROM rooms 
        WHERE status != 'maintenance'
        GROUP BY room_type
        HAVING available_rooms <= ?
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $threshold);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $admin_email = getAdminEmail($conn);
    $alerts_sent = 0;
    
    while ($row = $result->fetch_assoc()) {
        if (sendLowInventoryAlert($admin_email, $row['room_type'], $row['available_rooms'], $threshold)) {
            $alerts_sent++;
        }
    }
    
    $stmt->close();
    return $alerts_sent;
}

/**
 * Send new booking notification to hotel management
 */
function sendBookingNotificationToHotel($booking_details, $customer_details) {
    $admin_email = ADMIN_EMAIL;
    $subject = "🏨 New Booking Received - " . $booking_details['confirmation_number'];
    
    $message = "
    <html>
    <head>
        <title>New Booking Notification</title>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #081C3A; color: #F7B223; padding: 20px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
            .content { color: #333; line-height: 1.6; }
            .booking-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .customer-details { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            .highlight { background: #28a745; color: white; padding: 10px; border-radius: 5px; text-align: center; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏨 Hotel Management System</h1>
                <h2>New Booking Received</h2>
            </div>
            
            <div class='content'>
                <div class='highlight'>
                    <h3>📋 New Reservation Alert</h3>
                    <p>A new booking has been confirmed and requires your attention.</p>
                </div>
                
                <div class='booking-details'>
                    <h3>📅 Booking Information</h3>
                    <p><strong>Confirmation Number:</strong> " . htmlspecialchars($booking_details['confirmation_number']) . "</p>
                    <p><strong>Room Type:</strong> " . htmlspecialchars($booking_details['room_type']) . "</p>
                    <p><strong>Check-in Date:</strong> " . htmlspecialchars($booking_details['checkin_date']) . "</p>
                    <p><strong>Check-out Date:</strong> " . htmlspecialchars($booking_details['checkout_date']) . "</p>
                    <p><strong>Total Price:</strong> $" . number_format($booking_details['total_price'], 2) . "</p>
                    <p><strong>Booking Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
                
                <div class='customer-details'>
                    <h3>👤 Customer Information</h3>
                    <p><strong>Name:</strong> " . htmlspecialchars($customer_details['name']) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($customer_details['email']) . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($customer_details['phone']) . "</p>
                </div>
                
                <p><strong>Next Steps:</strong></p>
                <ul>
                    <li>✅ Customer confirmation email has been sent automatically</li>
                    <li>📋 Review booking details in the admin dashboard</li>
                    <li>🏨 Prepare room for guest arrival</li>
                    <li>📧 Automated reminders will be sent to guest</li>
                </ul>
                
                <p><strong>Admin Dashboard:</strong> Log in to review and manage this booking.</p>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Hotel Management System - Automated Notification</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($admin_email, 'Hotel Management', $subject, $message);
}
?> 