<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Determine if the current page is the homepage
$is_homepage = basename($_SERVER['PHP_SELF']) == 'index.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= isset($title) ? htmlspecialchars($title) : "FIU Hotel Management"; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">

  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      background: #0E1E40; /* Fallback background */
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #ffffff;
    }
    .main-header {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      background-color: transparent; /* Default transparent for homepage */
      transition: background-color 0.3s ease;
    }
    /* Add a background color to the header on non-homepage pages */
    .main-header:not(.homepage-header) {
        background-color: #0E1E40;
        position: static; /* Let it sit in the normal document flow */
    }
    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 30px;
        max-width: 1400px;
        margin: 0 auto;
    }
    .logo a {
        font-family: 'Orbitron', sans-serif;
        font-size: 1.8rem;
        color: #F7B223;
        text-decoration: none;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.5);
    }
    nav a {
      color: #fff;
      text-decoration: none;
      margin: 0 15px;
      padding: 8px 0;
      font-weight: bold;
      display: inline-block;
      position: relative;
      transition: color 0.3s ease;
    }
    nav a::after {
        content: '';
        position: absolute;
        width: 100%;
        transform: scaleX(0);
        height: 2px;
        bottom: 0;
        left: 0;
        background-color: #F7B223;
        transform-origin: bottom right;
        transition: transform 0.25s ease-out;
    }
    nav a:hover, nav a.active {
        color: #F7B223;
    }
    nav a.active::after {
        transform: scaleX(1);
        transform-origin: bottom left;
    }
    .container {
      flex-grow: 1;
      max-width: 1200px;
      margin: 30px auto 60px;
      padding: 20px;
      /* On non-homepages, restore the original container style */
      background-color: rgba(7, 28, 58, 0.85);
      border-radius: 15px;
      box-shadow: 0 0 25px rgba(247, 178, 35, 0.8);
    }
    /* Remove the background for the homepage's main content area */
    body.homepage .container {
        background-color: transparent;
        box-shadow: none;
        max-width: 100%;
        padding: 0;
        margin: 0;
    }
    @media (max-width: 992px) {
        .header-content { flex-direction: column; gap: 15px; }
        nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 0 15px; }
    }
  </style>
</head>
<body class="<?= $is_homepage ? 'homepage' : '' ?>">

<header class="main-header <?= $is_homepage ? 'homepage-header' : '' ?>">
    <div class="header-content">
        <div class="logo">
            <a href="index.php">FIU</a>
        </div>
        <nav>
            <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">Home</a>
            <a href="rooms.php" class="<?= $current_page == 'rooms.php' ? 'active' : '' ?>">Rooms</a>
            <a href="hotel.php" class="<?= $current_page == 'hotel.php' ? 'active' : '' ?>">Hotel Info</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $role = $_SESSION['role']; ?>
                
                <a href="bookings.php" class="<?= $current_page == 'bookings.php' ? 'active' : '' ?>">Book a Room</a>

                <?php if (in_array($role, ['admin', 'manager'])): ?>
                    <a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="room_plan.php" class="<?= $current_page == 'room_plan.php' ? 'active' : '' ?>">Room Plan</a>
                    <a href="admin_bookings.php" class="<?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">Bookings</a>
                    <a href="housekeeping.php" class="<?= $current_page == 'housekeeping.php' ? 'active' : '' ?>">Housekeeping</a>
                    <a href="payments.php" class="<?= $current_page == 'payments.php' ? 'active' : '' ?>">Payments</a>
                    <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">Reports</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">Users</a>
                    <?php endif; ?>
                <?php elseif ($role === 'front_desk'): ?>
                    <a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                    <a href="room_plan.php" class="<?= $current_page == 'room_plan.php' ? 'active' : '' ?>">Room Plan</a>
                    <a href="admin_bookings.php" class="<?= $current_page == 'admin_bookings.php' ? 'active' : '' ?>">Bookings</a>
                <?php elseif ($role === 'housekeeping'): ?>
                    <a href="housekeeping.php" class="<?= $current_page == 'housekeeping.php' ? 'active' : '' ?>">Housekeeping</a>
                <?php elseif ($role === 'accountant'): ?>
                     <a href="payments.php" class="<?= $current_page == 'payments.php' ? 'active' : '' ?>">Payments</a>
                     <a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">Reports</a>
                <?php endif; ?>

                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="<?= $current_page == 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="register.php" class="<?= $current_page == 'register.php' ? 'active' : '' ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<div class="container">
