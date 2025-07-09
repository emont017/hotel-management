<?php
$title = "Home - Hotel Management System";
require_once __DIR__ . '/../includes/header.php'; // This file starts the session for us
?>

<section id="hero">
    <h1>Hotel Management System</h1>
    <p class="subtitle">
        This system was developed as part of our Senior Capstone Project for Florida International University’s College of Engineering & Computing. It provides core functionalities for administrators to manage room inventory, monitor bookings, and support day-to-day operations, inspired by enterprise-level tools such as Hilton’s OnQ platform.
		<h4>Development Team: Carmine Talarico, Jonathan Gonzalez, Edward Montes, Alberto Enrique Santalo Jr</h4>
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="welcome.php" class="cta-button">Go to Dashboard</a>
    <?php else: ?>
        <a href="login.php" class="cta-button">Staff Login</a>
    <?php endif; ?>

</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>