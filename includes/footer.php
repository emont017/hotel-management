</div>

<footer class="main-footer">
<div class="footer-content">
    <div class="footer-logo">
        <a href="/index.php">
            <img src="/assets/images/FIULogoFooter.png" alt="FIU Footer Logo">
        </a>
    </div>

    <div class="footer-main-content">
        <div class="footer-links">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $role = $_SESSION['role']; ?>

                <?php // --- Admin & Manager Footer ---
                if (in_array($role, ['admin', 'manager'])): ?>
                    <a href="/index.php">Home</a>
                    <a href="/admin_dashboard.php">Admin Dashboard</a>
                    <a href="/housekeeping.php">Housekeeping</a>
                    <a href="/payments.php">Accounting</a>
                    <a href="/api/logout.php">Logout</a>

                <?php // --- Accountant Footer ---
                elseif ($role === 'accountant'): ?>
                    <a href="/index.php">Home</a>
                    <a href="/payments.php">Accounting</a>
                    <a href="/api/logout.php">Logout</a>

                <?php // --- Housekeeping Footer ---
                elseif ($role === 'housekeeping'): ?>
                    <a href="/index.php">Home</a>
                    <a href="/housekeeping.php">Housekeeping</a>
                    <a href="/api/logout.php">Logout</a>

                <?php // --- Guest Footer ---
                else: ?>
                    <a href="/index.php">Home</a>
                    <a href="/rooms.php">Rooms</a>
                    <a href="/bookings.php">Book Your Stay</a>
                    <a href="/manage_reservations.php">My Reservations</a>
                    <a href="/api/logout.php">Logout</a>
                <?php endif; ?>

            <?php else: // --- Not Logged In Footer --- ?>
                <a href="/index.php">Home</a>
                <a href="/rooms.php">Rooms</a>
                <a href="/bookings.php">Book Your Stay</a>
                <a href="/login.php">Staff Login</a>
            <?php endif; ?>
        </div>
        <div class="footer-info">
            <p><strong>Development Team:</strong> Carmine Talarico, Jonathan Gonzalez, Edward Montes, Alberto Enrique Santalo Jr</p>
            <p class="copyright">&copy; <?php echo date("Y"); ?> FIU Hotel Management System | All Rights Reserved</p>
        </div>
    </div>
</div>
</footer>

</body>
</html>
