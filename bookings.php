<?php
require_once 'php/db.php';

// Fetch distinct room types dynamically for dropdown
$room_types = [];
$res = $conn->query("SELECT DISTINCT room_type FROM rooms WHERE status != 'maintenance'");
while ($row = $res->fetch_assoc()) {
    $room_types[] = $row['room_type'];
}

// Get selected room type from URL, if any
$selectedType = $_GET['type'] ?? '';
$selectedTypeNormalized = strtolower(trim($selectedType));

$title = "Book a Room";
require_once 'includes/header.php';
?>

<h1 style="text-align:center; margin-bottom: 30px;">Book Your Stay</h1>

<form action="php/book_room.php" method="post" style="
    max-width: 500px;
    margin: 0 auto 60px;
    padding: 30px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(247, 178, 35, 0.8);
    color: #F7B223;
    font-size: 1.1rem;
">
    <label for="room_type" style="display:block; margin-bottom:8px;">Room Type:</label>
    <select id="room_type" name="room_type" required style="
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: none;
        font-size: 1rem;
        margin-bottom: 20px;
        color: #081C3A;
    ">
        <option value="" disabled <?= $selectedTypeNormalized === '' ? 'selected' : '' ?>>Select a room type</option>
        <?php foreach ($room_types as $type): ?>
            <option value="<?= htmlspecialchars($type) ?>" <?= (strtolower($type) === $selectedTypeNormalized) ? 'selected' : '' ?>>
                <?= htmlspecialchars($type) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="checkin_date" style="display:block; margin-bottom:8px;">Check-in Date:</label>
    <input type="date" id="checkin_date" name="checkin_date" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px; color: #081C3A;">

    <label for="checkout_date" style="display:block; margin-bottom:8px;">Check-out Date:</label>
    <input type="date" id="checkout_date" name="checkout_date" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px; color: #081C3A;">

    <label for="full_name" style="display:block; margin-bottom:8px;">Full Name:</label>
    <input type="text" id="full_name" name="full_name" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px; color: #081C3A;">

    <label for="email" style="display:block; margin-bottom:8px;">Email:</label>
    <input type="email" id="email" name="email" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px; color: #081C3A;">

    <label for="phone" style="display:block; margin-bottom:8px;">Phone Number:</label>
    <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s]+" placeholder="e.g. +1 555-555-5555"
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 30px; color: #081C3A;">

    <button type="submit" style="
        width: 100%;
        padding: 12px;
        background-color: #F7B223;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.3rem;
        color: #081C3A;
        cursor: pointer;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='#e5a91d'"
    onmouseout="this.style.backgroundColor='#F7B223'">
        Book Now
    </button>
</form>

<?php
require_once 'includes/footer.php';
?>
