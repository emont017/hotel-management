<?php
session_start();
$title = "Hotel Info";
require_once 'includes/header.php';
?>

<h1 style="text-align: center; margin-top: 30px;">About Our Hotel</h1>
<p style="text-align: center; max-width: 700px; margin: 10px auto 30px;">
    Nestled on the coast just minutes from campus, our hotel blends luxury and convenience for FIU guests, students, and visitors.
    Whether you're here for a short stay or long-term comfort, our accommodations offer the perfect balance of elegance and functionality.
</p>

<!-- Hotel Amenities -->
<div style="
    max-width: 700px;
    margin: 30px auto;
    padding: 25px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(247, 178, 35, 0.7);
">
    <h2 style="color: #F7B223;">Hotel Amenities</h2>
    <ul style="line-height: 2; font-size: 1.1rem; padding-left: 20px;">
        <li>✅ Free High-Speed Wi-Fi</li>
        <li>🥐 Complimentary Breakfast Buffet</li>
        <li>🏊‍♂️ Outdoor Pool & Full-Service Spa</li>
        <li>🧺 Daily Housekeeping</li>
        <li>🅿️ On-site Parking</li>
        <li>🛎️ 24/7 Concierge & Room Service</li>
        <li>🏋️‍♀️ Fitness Center with Peloton Bikes</li>
    </ul>
</div>

<!-- Location Info -->
<div style="
    max-width: 700px;
    margin: 30px auto;
    padding: 25px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(247, 178, 35, 0.7);
">
    <h2 style="color: #F7B223;">Our Location</h2>
    <p style="font-size: 1.05rem;">
        Located just blocks from Florida International University, we’re the top choice for academic visitors, conferences, and families visiting students.
    </p>

    <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3586.795207713045!2d-80.37747888497263!3d25.757188983638085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x88d9b9746dcffedf%3A0x1fc88930b580e1ae!2sFlorida%20International%20University!5e0!3m2!1sen!2sus!4v1684444608300!5m2!1sen!2sus"
        width="100%" height="300"
        style="border:0; border-radius: 10px; margin-top: 20px;"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>

<?php require_once 'includes/footer.php'; ?>
