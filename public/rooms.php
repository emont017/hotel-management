<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Fetch the standard rates for display
$rates = [];
$sql = "SELECT room_type, price FROM room_rates WHERE rate_name = 'Standard Rate'";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rates[$row['room_type']] = $row['price'];
    }
}

$title = "Rooms & Suites";
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="text-center mt-30">Explore Our Rooms</h2>
<p class="text-center" style="max-width: 600px; margin: 10px auto 30px;">
    Whether you're visiting for business, leisure, or an FIU campus event, we offer premium accommodations designed with comfort, style, and convenience in mind.
</p>

<div style="display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; padding: 20px;">

  <div class="card" style="width: 320px; display: flex; flex-direction: column;">
    <img src="assets/images/room_double.jpg" alt="Double Room" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
    <div style="flex-grow: 1; padding-top: 15px;">
        <h3>Double Room</h3>
        <p>Perfect for solo travelers or friends, our Double Room features two queen beds, a smart TV, work desk, and complimentary Wi-Fi.</p>
    </div>
    <p><strong>Price:</strong> $<?= number_format($rates['Double Room'] ?? 0, 2) ?>/night</p>
    <a href="bookings.php?type=<?= urlencode('Double Room') ?>" class="btn btn-primary mt-30">Book Now</a>
  </div>

  <div class="card" style="width: 320px; display: flex; flex-direction: column;">
    <img src="assets/images/room_executive.jpg" alt="Executive Suite" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
    <div style="flex-grow: 1; padding-top: 15px;">
      <h3>Executive Suite</h3>
      <p>Relax in style with a king bed, separate sitting area, rainfall shower, and 55" 4K TV. Ideal for extended stays or romantic weekends.</p>
    </div>
    <p><strong>Price:</strong> $<?= number_format($rates['Executive Suite'] ?? 0, 2) ?>/night</p>
    <a href="bookings.php?type=<?= urlencode('Executive Suite') ?>" class="btn btn-primary mt-30">Book Now</a>
  </div>

  <div class="card" style="width: 320px; display: flex; flex-direction: column;">
    <img src="assets/images/room_balcony.jpg" alt="Suite with Balcony" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px;">
    <div style="flex-grow: 1; padding-top: 15px;">
      <h3>Suite with Balcony</h3>
      <p>Premium experience with private balcony views, king bed, spa tub, espresso machine, and complimentary minibar.</p>
    </div>
    <p><strong>Price:</strong> $<?= number_format($rates['Suite with Balcony'] ?? 0, 2) ?>/night</p>
    <a href="bookings.php?type=<?= urlencode('Suite with Balcony') ?>" class="btn btn-primary mt-30">Book Now</a>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>