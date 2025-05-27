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
    <img src="https:
    <h3 style="margin-top: 15px;">Double Room</h3>
    <p>Perfect for solo travelers or friends, our Double Room features two queen beds, a smart TV, work desk, and complimentary Wi-Fi.</p>
    <p><strong>Price:</strong> $120/night</p>
    <a href="bookings.php?type=<?= urlencode('Double Room') ?>" style="color: 
  </div>

  <!-- Room 2: Executive Suite -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="https:
    <h3 style="margin-top: 15px;">Executive Suite</h3>
    <p>Relax in style with a king bed, separate sitting area, rainfall shower, and 55" 4K TV. Ideal for extended stays or romantic weekends.</p>
    <p><strong>Price:</strong> $180/night</p>
    <a href="bookings.php?type=<?= urlencode('Executive Suite') ?>" style="color: 
  </div>

  <!-- Room 3: Suite with Balcony -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="https:
    <h3 style="margin-top: 15px;">Suite with Balcony</h3>
    <p>Premium experience with private balcony views, king bed, spa tub, espresso machine, and complimentary minibar.</p>
    <p><strong>Price:</strong> $220/night</p>
    <a href="bookings.php?type=<?= urlencode('Suite with Balcony') ?>" style="color: 
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
