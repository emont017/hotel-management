</div> <!-- end .container -->

<style>
    .main-footer {
        background-color: #06172D;
        color: #ccc;
        padding: 40px 30px;
        margin-top: auto; /* Pushes footer to the bottom */
        border-top: 3px solid #F7B223;
    }
    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
    }
    .footer-column h4 {
        color: #fff;
        font-family: 'Orbitron', sans-serif;
        margin-top: 0;
        margin-bottom: 15px;
    }
    .footer-column p, .footer-column a {
        color: #ccc;
        text-decoration: none;
        font-size: 0.95rem;
        line-height: 1.8;
    }
    .footer-column a:hover {
        color: #F7B223;
        text-decoration: underline;
    }
    .footer-bottom {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #122C55;
        font-size: 0.9em;
        color: #888;
    }
</style>

<footer class="main-footer">
    <div class="footer-content">
        <div class="footer-column">
            <h4>About FIU Hotel Systems</h4>
            <p>
                A comprehensive management solution designed to emulate enterprise-level platforms like Hilton's OnQ. This system is a proud submission for the FIU Senior Capstone Project.
            </p>
        </div>
        <div class="footer-column">
            <h4>Quick Links</h4>
            <a href="index.php">Home</a><br>
            <a href="rooms.php">Rooms & Suites</a><br>
            <a href="bookings.php">Book Your Stay</a><br>
            <a href="login.php">Staff Login</a>
        </div>
        <div class="footer-column">
            <h4>The Capstone Team</h4>
            <p>
                Carmine Talarico<br>
                Jonathan Gonzalez<br>
                Edward Montes<br>
                Alberto Enrique Santalo Jr
            </p>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo date("Y"); ?> FIU Hotel Management System | All Rights Reserved
    </div>
</footer>

</body>
</html>