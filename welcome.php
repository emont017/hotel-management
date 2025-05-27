<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$title = "Welcome";
require_once 'includes/header.php';

$username = htmlspecialchars($_SESSION['username'] ?? 'Guest');
$role = $_SESSION['role'] ?? 'friend';
?>

<div style="
    max-width: 800px;
    margin: 60px auto;
    padding: 40px;
    background-color: rgba(255,255,255,0.05);
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    text-align: center;
">
    <h2 style="margin-bottom: 20px;">🎉 Welcome to the FIU Hotel Management System</h2>
    <p style="font-size: 1.2em; margin-bottom: 30px;">
        You’re now logged in as <strong><?= ucfirst($role) ?></strong>. Use the navigation bar above to manage room reservations, access your dashboard, or explore hotel information.
    </p>

    <?php if ($role === 'admin' || $role === 'guest'): ?>
        <a href="admin_dashboard.php" style="
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
        ">Admin Dashboard</a>
    <?php endif; ?>

    <?php if ($role === 'friend'): ?>
        <a href="manage_reservations.php" style="
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
        ">Manage My Reservations</a>
        <a href="bookings.php" style="
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
        ">Book a Room</a>
    <?php endif; ?>

    <a href="logout.php" style="
        display: inline-block;
        margin-top: 30px;
        padding: 10px 20px;
        background-color: #f44336;
        color: white;
        text-decoration: none;
        border-radius: 8px;
    ">Logout</a>
</div>

<?php require_once 'includes/footer.php'; ?>
