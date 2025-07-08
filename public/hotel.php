<?php
session_start();
$title = "Hotel Info";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card" style="max-width: 800px; margin: 30px auto;">
    <h1 class="text-center">About Our Hotel</h1>
    <p class="text-center" style="font-size: 1.1rem; max-width: 700px; margin: 10px auto 30px;">
        Nestled on the coast just minutes from campus, our hotel blends luxury and convenience for FIU guests, students, and visitors. Whether you're here for a short stay or long-term comfort, our accommodations offer the perfect balance of elegance and functionality.
    </p>

    <div class="mt-30">
        <h2>Hotel Amenities</h2>
        <ul style="line-height: 2; font-size: 1.1rem; padding-left: 0; list-style: none;">
            <li>✅ Free High-Speed Wi-Fi</li>
            <li>🥐 Complimentary Breakfast Buffet</li>
            <li>🏊‍♂️ Outdoor Pool & Full-Service Spa</li>
            <li>🧺 Daily Housekeeping</li>
            <li>🅿️ On-site Parking</li>
            <li>🛎️ 24/7 Concierge & Room Service</li>
            <li>🏋️‍♀️ Fitness Center</li>
        </ul>
    </div>

    <div class="mt-30">
        <h2>Our Location</h2>
        <p>
            Located just blocks from Florida International University, we’re the top choice for academic visitors, conferences, and families visiting students.
        </p>

        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3593.684496152866!2d-80.3759929849793!3d25.7483321836423!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x88d9c1b72e5a5079%3A0x6a3861c3327a58a7!2sFlorida%20International%20University!5e0!3m2!1sen!2sus!4v1689182697812!5m2!1sen!2sus"
            width="100%" height="350"
            style="border:0; border-radius: 10px; margin-top: 20px;"
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>