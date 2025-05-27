<?php
$title = "Rooms";
require_once 'includes/header.php';
?>

<h2>Explore Our Rooms</h2>
<p style="text-align: center; max-width: 600px; margin: 0 auto 30px;">
    Whether you're visiting for business, leisure, or an FIU campus event, we offer premium accommodations designed with comfort, style, and convenience in mind.
</p>

<div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center;">

  <!-- Room 1: Double Room -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="https://images.unsplash.com/photo-1673687782286-674e29c9bf9e?q=80&w=2940&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Double Room Interior" style="width: 100%; border-radius: 8px;">
    <h3 style="margin-top: 15px;">Double Room</h3>
    <p>Perfect for solo travelers or friends, our Double Room features two queen beds, a smart TV, work desk, and complimentary Wi-Fi.</p>
    <p><strong>Price:</strong> $120/night</p>
    <a href="bookings.php?type=<?= urlencode('Double Room') ?>" style="color: #F7B223; font-weight: bold;">Book Now</a>
  </div>

  <!-- Room 2: Executive Suite -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="https://images.unsplash.com/photo-1685592437742-3b56edb46b15?q=80&w=2940&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Executive Suite Interior" style="width: 100%; border-radius: 8px;">
    <h3 style="margin-top: 15px;">Executive Suite</h3>
    <p>Relax in style with a king bed, separate sitting area, rainfall shower, and 55" 4K TV. Ideal for extended stays or romantic weekends.</p>
    <p><strong>Price:</strong> $180/night</p>
    <a href="bookings.php?type=<?= urlencode('Executive Suite') ?>" style="color: #F7B223; font-weight: bold;">Book Now</a>
  </div>

  <!-- Room 3: Suite with Balcony -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="https://images.unsplash.com/photo-1705765276125-f2539bc95b0f?q=80&w=3087&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Suite with Balcony Interior" style="width: 100%; border-radius: 8px;">
    <h3 style="margin-top: 15px;">Suite with Balcony</h3>
    <p>Premium experience with private balcony views, king bed, spa tub, espresso machine, and complimentary minibar.</p>
    <p><strong>Price:</strong> $220/night</p>
    <a href="bookings.php?type=<?= urlencode('Suite with Balcony') ?>" style="color: #F7B223; font-weight: bold;">Book Now</a>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
