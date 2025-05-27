<?php
session_start();

$title = "Hotel Info";
require_once 'includes/header.php';
?>

<h2>About Our Hotel</h2>

<p>
    Nestled on the coast just minutes from campus, our hotel blends luxury and convenience for FIU guests, students, and visitors.
    Whether you're here for a short stay or long-term comfort, our accommodations offer the perfect balance of elegance and functionality.
</p>

<h3>Hotel Amenities</h3>
<ul style="line-height: 1.8; font-size: 1.1em;">
    <li>✅ Free High-Speed Wi-Fi</li>
    <li>🥐 Complimentary Breakfast Buffet</li>
    <li>🏊‍♂️ Outdoor Pool & Full-Service Spa</li>
    <li>🧺 Daily Housekeeping</li>
    <li>🅿️ On-site Parking</li>
    <li>🛎️ 24/7 Concierge & Room Service</li>
    <li>🏋️‍♀️ Fitness Center with Peloton Bikes</li>
</ul>

<h3>Our Location</h3>
<p>
    Located just blocks from Florida International University, we’re the top choice for academic visitors, conferences, and families visiting students.
</p>

<iframe
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3582.336447373591!2d-80.37701828497626!3d25.75722461390995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x88d9b997b91b32e5%3A0xf2829f7fd9f4b33f!2sFlorida%20International%20University!5e0!3m2!1sen!2sus!4v1682623453947!5m2!1sen!2sus"
    width="100%" height="300" style="border:0; border-radius: 10px; margin-top: 20px;"
    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
</iframe>

<?php require_once 'includes/footer.php'; ?>
