<?php
session_start();
$title = "Home";
require_once 'includes/header.php';  
?>

<h1 style="text-align: center; margin-top: 30px;">🎓 Welcome to Our FIU Capstone Project</h1>

<p style="text-align: center; max-width: 700px; margin: 20px auto; font-size: 1.1rem;">
    This system was developed as part of our Senior Capstone Project for Florida International University’s College of Engineering & Computing. Our Hotel Management System provides core functionalities for administrators to manage room inventory, monitor bookings, and support day-to-day operations, inspired by enterprise-level tools such as Hilton’s OnQ platform.
</p>

<div style="text-align: center; margin-top: 40px;">
    <h3 style="color: #F7B223;">👨‍💻 Development Team</h3>
    <p style="font-size: 1.05rem; line-height: 1.6;">
        Carmine Talarico<br>
        Jonathan Gonzalez<br>
        Edward Montes<br>
        Alberto Enrique Santalo Jr
    </p>
</div>

<div style="text-align:center; margin: 60px 0;">
    <a href="bookings.php" style="
        background-color: #F7B223;
        color: #081C3A;
        padding: 15px 30px;
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        font-size: 1.2rem;
        box-shadow: 0 0 12px rgba(247, 178, 35, 0.7);
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
        Explore the System
    </a>
</div>

<?php
require_once 'includes/footer.php';  
?>
