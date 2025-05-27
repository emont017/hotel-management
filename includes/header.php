<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= isset($title) ? htmlspecialchars($title) : "FIU Hotel Management"; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https:

  <style>
    
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      background: linear-gradient(180deg, 
      color: 
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      padding: 40px 20px 20px;
      text-align: center;
      background-color: 
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
      user-select: none;
    }

    header h1 {
      font-family: 'Orbitron', sans-serif;
      font-size: 2.8rem;
      margin: 0;
      color: 
      text-shadow: 2px 2px 8px 
    }

    nav {
      background-color: 
      text-align: center;
      padding: 12px 0 18px;
      box-shadow: inset 0 -2px 5px rgba(0, 0, 0, 0.4);
      user-select: none;
      font-weight: 700;
      font-size: 1.1rem;
      letter-spacing: 0.03em;
    }

    nav a {
      color: 
      text-decoration: none;
      margin: 0 20px;
      padding: 6px 12px;
      border-radius: 8px;
      transition: background-color 0.3s ease, color 0.3s ease;
      display: inline-block;
    }

    nav a:hover, nav a:focus {
      background-color: 
      color: 
      outline: none;
    }

    .container {
      flex-grow: 1;
      max-width: 1000px;
      margin: 30px auto 60px;
      padding: 0 20px;
      background-color: rgba(7, 28, 58, 0.85);
      border-radius: 15px;
      box-shadow: 0 0 25px rgba(247, 178, 35, 0.8);
      color: 
      min-height: 400px;
      user-select: text;
    }

    @media (max-width: 600px) {
      nav a {
        display: block;
        margin: 10px 0;
        font-size: 1rem;
      }

      .container {
        margin: 20px 15px 40px;
        padding: 20px;
      }
    }
  </style>

</head>
<body>

<header>
  <h1>FIU Hotel Management</h1>
</header>

<nav>
  <a href="index.php">Home</a>
  <a href="rooms.php">Rooms</a>
  <a href="hotel.php">Hotel Info</a>

  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="bookings.php">Book a Room</a>

    <?php if (in_array($_SESSION['role'], ['admin', 'guest'])): ?>
      <a href="admin_dashboard.php">Dashboard</a>
      <a href="admin_rooms.php">Manage Rooms</a>
      <a href="admin_bookings.php">Manage Bookings</a>
      <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="users.php">Manage Users</a>
      <?php endif; ?>
    <?php elseif ($_SESSION['role'] === 'friend'): ?>
      <a href="manage_reservations.php">Manage Reservations</a>
    <?php endif; ?>

    <a href="logout.php">Logout</a>
  <?php else: ?>
    <a href="bookings.php">Book a Room</a>
    <a href="login.php">Login</a>
    <a href="register.php">Register</a>
  <?php endif; ?>
</nav>

<div class="container">
