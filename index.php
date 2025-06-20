<?php
session_start();
$title = "Home - Hotel Management System";
require_once 'includes/header.php';
?>

<style>
    /* Override default container padding for a full-width hero experience */
    .container {
        padding: 0;
        max-width: 100%;
        background-color: transparent;
        box-shadow: none;
    }

    #hero {
        position: relative;
        text-align: center;
        color: #fff;
        padding: 120px 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 70vh;
        overflow: hidden; /* Ensures canvas doesn't bleed out */
    }

    #particles-js {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: #081C3A;
        background-image: linear-gradient(to bottom right, #081C3A, #0E1E40);
        z-index: -1;
    }

    #hero h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: clamp(2.5rem, 5vw, 4rem);
        color: #F7B223;
        margin: 0;
        text-shadow: 0 0 15px rgba(247, 178, 35, 0.5);
    }
    
    #hero .subtitle {
        font-size: 1.25rem;
        margin-top: 15px;
        max-width: 700px;
        line-height: 1.6;
        opacity: 0.9;
        border-left: 3px solid #F7B223;
        padding-left: 15px;
        text-align: left;
    }

    .cta-button {
        display: inline-block;
        margin-top: 40px;
        padding: 15px 35px;
        background: #F7B223;
        color: #081C3A;
        font-weight: bold;
        text-decoration: none;
        border-radius: 50px;
        font-size: 1.2rem;
        box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 0 25px rgba(247, 178, 35, 0.6);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .cta-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 25px rgba(0,0,0,0.3), 0 0 35px rgba(247, 178, 35, 0.8);
    }
    
    .content-section {
        padding: 60px 20px;
        max-width: 1000px;
        margin: 0 auto;
    }

    .team-section {
        text-align: center;
    }

    .team-section h2 {
        font-size: 2.5rem;
        color: #F7B223;
        margin-bottom: 40px;
    }
    
    .team-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }
    
    .team-card {
        background: #081E3F;
        padding: 25px;
        border-radius: 10px;
        border: 1px solid #122C55;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .team-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }

    .team-card p {
        margin: 0;
        font-size: 1.1rem;
        font-weight: bold;
    }

</style>

<!-- Hero Section -->
<section id="hero">
    <div id="particles-js"></div>
    <h1>Hotel Management System</h1>
    <p class="subtitle">
        This system was developed as part of our Senior Capstone Project for Florida International University’s College of Engineering & Computing. It provides core functionalities for administrators to manage room inventory, monitor bookings, and support day-to-day operations, inspired by enterprise-level tools such as Hilton’s OnQ platform.
    </p>
    <a href="bookings.php" class="cta-button">Book a Room</a>
</section>

<!-- Content Sections -->
<div class="content-section">
    <div class="team-section">
        <h2>Development Team</h2>
        <div class="team-grid">
            <div class="team-card"><p>Carmine Talarico</p></div>
            <div class="team-card"><p>Jonathan Gonzalez</p></div>
            <div class="team-card"><p>Edward Montes</p></div>
            <div class="team-card"><p>Alberto Enrique Santalo Jr</p></div>
        </div>
    </div>
</div>

<!-- Particle.js library for the animated background -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        particlesJS('particles-js', {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#F7B223" },
                "shape": { "type": "circle" },
                "opacity": { "value": 0.5, "random": true },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#F7B223", "opacity": 0.2, "width": 1 },
                "move": { "enable": true, "speed": 1, "direction": "none", "random": false, "straight": false, "out_mode": "out" }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": false }, "resize": true },
                "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 0.5 } } }
            },
            "retina_detect": true
        });
    });
</script>

<?php
// We still need the main container div for the footer alignment
echo '<div class="container">'; 
require_once 'includes/footer.php';
?>