<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= isset($title) ? htmlspecialchars($title) : "FIU Hotel Management"; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css"> 
</head>
<body class="<?= $current_page == 'index.php' ? 'homepage' : '' ?>">

<header class="main-header <?= $current_page == 'index.php' ? 'homepage-header' : '' ?>">
    <div class="header-content">
        <div class="logo">
            <a href="/index.php">FIU</a>
        </div>
        <nav>
            <a href="/index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="/rooms.php" class="<?= $current_page == 'rooms.php' ? 'active' : '' ?>">Rooms</a>
            <a href="/hotel.php" class="<?= $current_page == 'hotel.php' ? 'active' : '' ?>">Hotel Info</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $role = $_SESSION['role']; ?>
                
                <a href="/bookings.php" class="<?= $current_page == 'bookings.php' ? 'active' : '' ?>">Book a Room</a>

                <?php if (in_array($role, ['admin', 'manager'])): ?>
                    <a href="/admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="/room_plan.php" class="<?= $current_page == 'room_plan.php' ? 'active' : '' ?>">Room Plan</a>
                    <a href="/admin_bookings.php" class="<?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">Bookings</a>
                    <a href="/housekeeping.php" class="<?= $current_page == 'housekeeping.php' ? 'active' : '' ?>">Housekeeping</a>
                    <a href="/payments.php" class="<?= $current_page == 'payments.php' ? 'active' : '' ?>">Payments</a>
                    <a href="/reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">Reports</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="/users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">Users</a>
                    <?php endif; ?>
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