<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$title = "Welcome";
require_once __DIR__ . '/../includes/header.php';

$username = htmlspecialchars($_SESSION['username'] ?? 'Guest');
$role = $_SESSION['role'] ?? 'guest'; // Standardized role
?>

<div class="card text-center" style="max-width: 800px; margin: 60px auto;">
    <h2>🎉 Welcome to the FIU Hotel Management System</h2>
    <p style="font-size: 1.2em;">
        You’re now logged in as <strong><?= ucfirst($role) ?></strong>. Use the navigation bar above to manage room reservations, access your dashboard, or explore hotel information.
    </p>

    <div class="mt-30">
        <?php if (in_array($role, ['admin', 'manager', 'front_desk'])): ?>
            <a href="admin_dashboard.php" class="btn btn-primary">Admin Dashboard</a>
        <?php endif; ?>

        <?php if ($role === 'guest'): // Standardized role check ?>
            <a href="manage_reservations.php" class="btn btn-primary">Manage My Reservations</a>
            <a href="bookings.php" class="btn btn-primary">Book a Room</a>
        <?php endif; ?>
    </div>
    
    <a href="/hotel-management/php/logout.php" class="btn btn-danger mt-30">Logout</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>