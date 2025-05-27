<?php
require_once 'php/db.php';

$room_types = [];
$res = $conn->query("SELECT DISTINCT room_type FROM rooms WHERE status != 'maintenance'");
while ($row = $res->fetch_assoc()) {
    $room_types[] = $row['room_type'];
}

$selectedType = $_GET['type'] ?? '';
$selectedTypeNormalized = strtolower(trim($selectedType));

$title = "Book a Room";
require_once 'includes/header.php';
?>

<h1 style="text-align: center; margin-top: 30px;">🛎️ Book Your Stay</h1>

<div style="
    max-width: 500px;
    margin: 30px auto 60px;
    padding: 30px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 15px;
    box-shadow: 0 0 25px rgba(247, 178, 35, 0.8);
    color: #ffffff;
">
    <form action="php/book_room.php" method="post" style="display: flex; flex-direction: column; gap: 20px;">
        <label for="room_type" style="font-weight: bold; color: #F7B223;">Room Type:</label>
        <select id="room_type" name="room_type" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">
            <option value="" disabled <?= $selectedTypeNormalized === '' ? 'selected' : '' ?>>Select a room type</option>
            <?php foreach ($room_types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>" <?= (strtolower($type) === $selectedTypeNormalized) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($type) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="checkin_date" style="font-weight: bold; color: #F7B223;">Check-in Date:</label>
        <input type="date" id="checkin_date" name="checkin_date" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="checkout_date" style="font-weight: bold; color: #F7B223;">Check-out Date:</label>
        <input type="date" id="checkout_date" name="checkout_date" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="full_name" style="font-weight: bold; color: #F7B223;">Full Name:</label>
        <input type="text" id="full_name" name="full_name" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="email" style="font-weight: bold; color: #F7B223;">Email:</label>
        <input type="email" id="email" name="email" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="phone" style="font-weight: bold; color: #F7B223;">Phone Number:</label>
        <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s]+" placeholder="e.g. +1 555-555-5555" style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <button type="submit" style="
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
            Book Now
        </button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
