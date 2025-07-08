</div> <footer class="main-footer">
    <div class="footer-content">
        <div class="footer-links">
            <a href="/index.php">Home</a>
            <a href="/rooms.php">Rooms</a>
            <a href="/bookings.php">Book Your Stay</a>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="/login.php">Staff Login</a>
            <?php endif; ?>

        </div>
        <div class="footer-info">
            <p><strong>The Capstone Team:</strong> Carmine Talarico, Jonathan Gonzalez, Edward Montes, Alberto Enrique Santalo Jr</p>
            <p class="copyright">&copy; <?php echo date("Y"); ?> FIU Hotel Management System | All Rights Reserved</p>
        </div>
    </div>
</footer>

</body>
</html>