<?php
$title = "Rooms";
require_once 'includes/header.php';
?>

<h2 style="text-align: center; margin-top: 30px;">Explore Our Rooms</h2>
<p style="text-align: center; max-width: 600px; margin: 10px auto 30px;">
    Whether you're visiting for business, leisure, or an FIU campus event, we offer premium accommodations designed with comfort, style, and convenience in mind.
</p>

<div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; padding: 20px;">

  <!-- Room 1: Double Room -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="assets/images/room_double.jpg" alt="Double Room" style="
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 10px;
        image-rendering: auto;
        display: block;
    ">
    <h3 style="margin-top: 15px;">Double Room</h3>
    <p>Perfect for solo travelers or friends, our Double Room features two queen beds, a smart TV, work desk, and complimentary Wi-Fi.</p>
    <p><strong>Price:</strong> $120/night</p>
    <a href="bookings.php?type=<?= urlencode('Double Room') ?>" style="
        display: inline-block;
        margin-top: 10px;
        padding: 10px 20px;
        background-color: #F7B223;
        color: #081C3A;
        font-weight: bold;
        border-radius: 8px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">Book Now</a>
  </div>

  <!-- Room 2: Executive Suite -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="assets/images/room_executive.jpg" alt="Executive Suite" style="
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 10px;
        image-rendering: auto;
        display: block;
    ">
    <h3 style="margin-top: 15px;">Executive Suite</h3>
    <p>Relax in style with a king bed, separate sitting area, rainfall shower, and 55&quot; 4K TV. Ideal for extended stays or romantic weekends.</p>
    <p><strong>Price:</strong> $180/night</p>
    <a href="bookings.php?type=<?= urlencode('Executive Suite') ?>" style="
        display: inline-block;
        margin-top: 10px;
        padding: 10px 20px;
        background-color: #F7B223;
        color: #081C3A;
        font-weight: bold;
        border-radius: 8px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">Book Now</a>
  </div>

  <!-- Room 3: Suite with Balcony -->
  <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; width: 320px; box-shadow: 0 0 10px rgba(0,0,0,0.4);">
    <img src="assets/images/room_balcony.jpg" alt="Suite with Balcony" style="
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        border-radius: 10px;
        image-rendering: auto;
        display: block;
    ">
    <h3 style="margin-top: 15px;">Suite with Balcony</h3>
    <p>Premium experience with private balcony views, king bed, spa tub, espresso machine, and complimentary minibar.</p>
    <p><strong>Price:</strong> $220/night</p>
    <a href="bookings.php?type=<?= urlencode('Suite with Balcony') ?>" style="
        display: inline-block;
        margin-top: 10px;
        padding: 10px 20px;
        background-color: #F7B223;
        color: #081C3A;
        font-weight: bold;
        border-radius: 8px;
        text-decoration: none;
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">Book Now</a>
  </div>

</div>

<?php require_once 'includes/footer.php'; ?>
