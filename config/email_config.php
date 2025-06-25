<?php
/**
 * Email Configuration for Hotel Management System
 * Capstone Project - FIU
 */

// SMTP Configuration - Use environment variables for security
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? 'your-app-password');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');

// Email Settings
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'your-email@gmail.com');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'Hotel Management System');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'your-email@gmail.com');

// Email Templates
define('EMAIL_FOOTER', 'This is an automated message from the Hotel Management System');
?> 