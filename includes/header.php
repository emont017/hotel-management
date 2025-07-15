<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);

// Check if the current page is any of the hotel info-related pages
$is_hotel_info_page = in_array($current_page, ['hotel.php', 'rooms.php', 'bookings.php']);

// Check if the current page is any of the housekeeping-related pages
$is_housekeeping_page = in_array($current_page, ['housekeeping.php', 'housekeeping_tasks.php', 'admin_housekeeping_assign.php']);

// Check if the current page is any of the accounting-related pages
$is_accounting_page = in_array($current_page, ['payments.php', 'reports.php']);

// Check if the current page is any of the audit-related pages
$is_audit_page = in_array($current_page, ['night_audit.php', 'audit_log_viewer.php']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= isset($title) ? htmlspecialchars($title) : "FIU Hotel Management"; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:wght@700&family=Orbitron:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="/assets/css/style.css"> 
</head>
<body class="<?= $current_page == 'index.php' ? 'homepage' : '' ?>">

<header class="main-header <?= $current_page == 'index.php' ? 'homepage-header' : '' ?>">
    <div class="header-content">
        <div class="logo">
            <a href="/index.php">
                <img src="/assets/images/FIU-Panthers-Logo-2009.png" alt="FIU Logo">
            </a>
        </div>
        <nav>
            <a href="/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a>

            <!-- Hotel Info Dropdown -->
            <div class="nav-item dropdown">
                <a href="/hotel.php" class="dropdown-toggle <?= $is_hotel_info_page ? 'active' : '' ?>">Hotel Info</a>
                <div class="dropdown-menu">
                    <a href="/hotel.php">About Us</a>
                    <a href="/rooms.php">Rooms & Suites</a>
                    <a href="/bookings.php">Book a Room</a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $role = $_SESSION['role']; ?>

                <?php if (in_array($role, ['admin', 'manager'])): ?>
                    <a href="/admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="/room_plan.php" class="<?= $current_page == 'room_plan.php' ? 'active' : '' ?>">Room Plan</a>
                    <a href="/admin_bookings.php" class="<?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">Bookings</a>
                    
                    <!-- Housekeeping Dropdown -->
                    <div class="nav-item dropdown">
                        <a href="/housekeeping.php" class="dropdown-toggle <?= $is_housekeeping_page ? 'active' : '' ?>">Housekeeping</a>
                        <div class="dropdown-menu">
                            <a href="/housekeeping.php">Master List</a>
                            <a href="/housekeeping_tasks.php">Daily Tasks</a>
                            <a href="/admin_housekeeping_assign.php">Assign Tasks</a>
                        </div>
                    </div>
                    
                    <!-- Accounting Dropdown -->
                    <div class="nav-item dropdown">
                        <a href="/payments.php" class="dropdown-toggle <?= $is_accounting_page ? 'active' : '' ?>">Accounting</a>
                        <div class="dropdown-menu">
                            <a href="/payments.php">Payments</a>
                            <a href="/reports.php">Reports</a>
                        </div>
                    </div>

                    <!-- Night Audit Dropdown -->
                    <div class="nav-item dropdown">
                        <a href="/night_audit.php" class="dropdown-toggle <?= $is_audit_page ? 'active' : '' ?>">Night Audit</a>
                        <div class="dropdown-menu">
                            <a href="/night_audit.php">Run Audit</a>
                            <a href="/audit_log_viewer.php">Audit Logs</a>
                        </div>
                    </div>

					<a href="/users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">Staff</a>
                    
                <?php elseif ($role === 'guest'): ?>
                    <a href="/manage_reservations.php" class="<?= $current_page == 'manage_reservations.php' ? 'active' : '' ?>">My Reservations</a>
                <?php endif; ?>

                <a href="/api/logout.php">Logout</a>
            <?php else: ?>
                <a href="/login.php" class="<?= $current_page == 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="/register.php" class="<?= $current_page == 'register.php' ? 'active' : '' ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
