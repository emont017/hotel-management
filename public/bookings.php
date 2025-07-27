<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$is_staff_booking = false;

if (isset($_GET['staff']) && $_GET['staff'] == 1) {
    $is_staff_booking = true;
    
    // Get parameters from the URL
    $staff_room_id = intval($_GET['room_id'] ?? 0);
    $staff_check_in = $_GET['check_in'] ?? '';
    $staff_check_out = $_GET['check_out'] ?? '';

    // Fetch the room type from the database
    if ($staff_room_id > 0) {
        $stmt = $conn->prepare("SELECT room_type FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $staff_room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $preselected_room_type = $row['room_type'];
        }
        $stmt->close();
    }
}


// Staff booking detection
$is_staff_booking = isset($_GET['staff']) && $_GET['staff'] == '1';
$staff_room_id = $_GET['room_id'] ?? null;
$staff_check_in = $_GET['check_in'] ?? null;
$staff_check_out = $_GET['check_out'] ?? null;

// Check if a room type was pre-selected from rooms.php
$preselected_room_type = isset($_GET['type']) ? urldecode($_GET['type']) : null;

// If staff booking, fetch room type for the given room_id
if ($is_staff_booking && $staff_room_id) {
    $stmt = $conn->prepare("SELECT room_type FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $staff_room_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $preselected_room_type = $row['room_type'];
    }
    $stmt->close();
}

// If a room type is preselected, fetch its details
$room_details = null;
if ($preselected_room_type) {
    $sql = "SELECT room_type, price FROM room_rates WHERE rate_name = 'Standard Rate' AND room_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $preselected_room_type);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $room_details = $row;
    }
}

// Fetch logged-in user's profile data for auto-population
$user_profile = null;
if (isset($_SESSION['user_id'])) {
    // First try to use session data (faster)
    if (isset($_SESSION['full_name']) && isset($_SESSION['email'])) {
        $user_profile = [
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'phone' => $_SESSION['phone'] ?? ''
        ];
    } else {
        // Fallback to database query for older sessions
        $stmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $user_profile = $row;
            // Update session for future use
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['phone'] = $row['phone'];
        }
        $stmt->close();
    }
}

$title = "Book Your Stay";
require_once __DIR__ . '/../includes/header.php';

// Add Flatpickr CSS and JS for professional date blocking
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">';
echo '<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>';
?>

<div class="booking-container">
    <?php if ($preselected_room_type && $room_details): ?>
        <!-- Pre-selected Room Header -->
        <div class="card" style="background: linear-gradient(135deg, #081C3A 0%, #122C55 100%); border: 2px solid #B6862C;">
            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 0 0 auto;">
                    <?php
                    $image_map = [
                        'Double Room' => 'room_double.jpg',
                        'Executive Suite' => 'room_executive.jpg',
                        'Suite with Balcony' => 'room_balcony.jpg'
                    ];
                    $image_file = $image_map[$preselected_room_type] ?? 'room_double.jpg';
                    ?>
                    <img src="assets/images/<?= $image_file ?>" alt="<?= htmlspecialchars($preselected_room_type) ?>" 
                         style="width: 120px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #B6862C;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <h2 style="margin-bottom: 5px; color: #B6862C;">Selected: <?= htmlspecialchars($preselected_room_type) ?></h2>
                    <p style="margin-bottom: 5px; font-size: 1.1rem;">Starting at <strong>$<?= number_format($room_details['price'], 2) ?></strong> per night</p>
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc;">✓ Room type selected - Complete booking below</p>
                </div>
                <div style="flex: 0 0 auto;">
                    <a href="rooms.php" class="btn btn-secondary btn-sm">Change Room</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Regular Booking Header -->
        <div>
            <h1>Book Your Stay</h1>
            <p>Select your dates to see available rooms and pricing.</p>
        </div>
    <?php endif; ?>

    <!-- Date Selection Form -->
<div class="card" <?= $is_staff_booking ? 'style="display:none;"' : '' ?>>

        <h3>
    <?= $is_staff_booking 
        ? 'Complete Your Booking' 
        : ($preselected_room_type ? 'Choose Your Dates' : 'Step 1: Select Your Dates') ?>
</h3>

        <div id="date-selection" class="date-selection-form" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label for="checkin_date" class="form-label">Check-in Date</label>
                <input type="text" id="checkin_date" name="checkin_date" class="form-input date-picker" placeholder="Select check-in date" readonly required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label for="checkout_date" class="form-label">Check-out Date</label>
                <input type="text" id="checkout_date" name="checkout_date" class="form-input date-picker" placeholder="Select check-out date" readonly required>
            </div>
            <div style="flex: 0 0 auto; align-self: flex-end;">
                <button id="check-availability-btn" class="btn btn-primary" style="height: 40px; padding: 0 20px; transform: translateY(-22px);">
                    <?= $preselected_room_type ? 'Check Availability' : 'Check Availability' ?>
                </button>
            </div>
        </div>
    </div>
    <div id="booking-step-2" style="display: none;">
    <div id="results-loader" style="display:none;">Loading...</div>
    <p id="results-message"></p>
    <div id="availability-results"></div>
</div>

    
<div id="booking-step-2" style="<?= $is_staff_booking ? 'display:block;' : 'display:none;' ?>"></div>



        <!-- Guest Details Form -->
        <form id="guest-details-form" action="/api/submit_booking.php" method="post" class="card mt-30">
            <?php
            // Check if user is logged in and has complete profile
            $has_complete_profile = $user_profile && 
                                  !empty($user_profile['full_name']) && 
                                  !empty($user_profile['email']);
            
            if ($has_complete_profile):
            ?>
                <h3><?= $preselected_room_type ? 'Complete Your Booking' : 'Step 3: Confirm Your Details' ?></h3>
                
                <div style="background: #06172D; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2ecc71;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="color: #2ecc71; font-size: 1.2rem;">✓</div>
                        <h4 style="margin: 0; color: #2ecc71;">Information Auto-Filled</h4>
                    </div>
                    <p style="margin: 0; color: #ccc; font-size: 0.9rem;">
                        We've pre-filled your details from your account. You can edit any information below if needed.
                    </p>
                </div>
            <?php else: ?>
                <h3><?= $preselected_room_type ? 'Complete Your Booking' : 'Step 3: Enter Your Details' ?></h3>
            <?php endif; ?>
            
            <input type="hidden" id="selected_room_type" name="room_type" value="<?= htmlspecialchars($preselected_room_type ?? '') ?>">
            <input type="hidden" id="form_checkin_date" name="checkin_date">
            <input type="hidden" id="form_checkout_date" name="checkout_date">

            <?php if ($is_staff_booking && isset($_GET['room_id'])): ?>
    <input type="hidden" name="room_id" value="<?= htmlspecialchars($_GET['room_id']) ?>">
<?php endif; ?>



            <?php if ($preselected_room_type): ?>
                <!-- Show booking summary for preselected room -->
                <div style="background: #06172D; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #B6862C;">
                    <h4 style="margin-bottom: 10px; color: #B6862C;">Booking Summary</h4>
                    <div id="booking-summary-content" style="display: none;">
                        <!-- Will be populated by JavaScript -->
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-input" 
                       value="<?= $has_complete_profile ? htmlspecialchars($user_profile['full_name']) : '' ?>" required>
            </div>
            <div>
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?= $has_complete_profile ? htmlspecialchars($user_profile['email']) : '' ?>" required>
            </div>
            <div>
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-input" 
                       value="<?= $has_complete_profile ? htmlspecialchars($user_profile['phone'] ?? '') : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Complete Booking</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Flags and Data ---
    const isStaff = <?= $is_staff_booking ? 'true' : 'false' ?>;
    const staffCheckIn = "<?= $staff_check_in ?>";
    const staffCheckOut = "<?= $staff_check_out ?>";
    const preselectedRoomType = "<?= htmlspecialchars($preselected_room_type ?? '') ?>";
    const price = <?= isset($room_details['price']) ? $room_details['price'] : 0 ?>;
    const hasCompleteProfile = <?= $has_complete_profile ? 'true' : 'false' ?>;

    // --- Elements ---
    const formCheckinInput = document.getElementById('form_checkin_date');
    const formCheckoutInput = document.getElementById('form_checkout_date');
    const bookingSummaryContent = document.getElementById('booking-summary-content');
    const checkBtn = document.getElementById('check-availability-btn');
    const resultsDiv = document.getElementById('availability-results');
    const loader = document.getElementById('results-loader');
    const messageP = document.getElementById('results-message');
    const step2Div = document.getElementById('booking-step-2');
    const guestForm = document.getElementById('guest-details-form');
    const selectedRoomInput = document.getElementById('selected_room_type');

    // --- Staff Pre-fill ---
    if (isStaff) {
        formCheckinInput.value = staffCheckIn;
        formCheckoutInput.value = staffCheckOut;
        bookingSummaryContent.style.display = 'block';

        if (preselectedRoomType && staffCheckIn && staffCheckOut) {
            updateBookingSummary(preselectedRoomType, staffCheckIn, staffCheckOut, price);
        }

        guestForm.style.display = 'block';
        step2Div.style.display = 'block';
    }

    // --- Date Picker Initialization ---
    let blockedDates = [];
    let checkinPicker, checkoutPicker;
    let selectedCheckin = null;
    let selectedCheckout = null;

    setTimeout(() => {
        if (preselectedRoomType) {
            loadBlockedDates(preselectedRoomType);
        } else {
            initializeDatePickers([]);
        }
    }, 100);

    // --- Fetch Blocked Dates ---
    function loadBlockedDates(roomType) {
        fetch(`/api/get_blocked_dates.php?room_type=${encodeURIComponent(roomType)}`)
            .then(response => response.json())
            .then(data => {
                blockedDates = data.blocked_dates || [];
                initializeDatePickers(blockedDates);
            })
            .catch(() => initializeDatePickers([]));
    }

    // --- Initialize Flatpickr ---
    function initializeDatePickers(disabledDates) {
        const today = new Date();
        const checkinEl = document.getElementById('checkin_date');
        const checkoutEl = document.getElementById('checkout_date');

        if (!checkinEl || !checkoutEl) return;

        const disabledDateObjects = disabledDates.map(date => new Date(date + 'T00:00:00'));

        if (checkinPicker) checkinPicker.destroy();
        if (checkoutPicker) checkoutPicker.destroy();

        const baseConfig = {
            minDate: today,
            disable: disabledDateObjects,
            dateFormat: "Y-m-d",
            allowInput: false,
        };

        checkinPicker = flatpickr(checkinEl, {
            ...baseConfig,
            onChange: (selectedDates, dateStr) => {
                selectedCheckin = dateStr;
                formCheckinInput.value = dateStr;
                adjustCheckoutPicker(dateStr);
                autoUpdateAvailability();
            }
        });

        checkoutPicker = flatpickr(checkoutEl, {
            ...baseConfig,
            onChange: (selectedDates, dateStr) => {
                selectedCheckout = dateStr;
                formCheckoutInput.value = dateStr;
                autoUpdateAvailability();
            }
        });
    }

    function adjustCheckoutPicker(checkinDate) {
        if (checkoutPicker && checkinDate) {
            const nextDay = new Date(checkinDate + 'T00:00:00');
            nextDay.setDate(nextDay.getDate() + 1);
            checkoutPicker.set('minDate', nextDay);
        }
    }

    // --- Auto Update Availability ---
    function autoUpdateAvailability() {
        if (!selectedCheckin || !selectedCheckout || step2Div.style.display === 'none') return;
        if (selectedCheckin < selectedCheckout) {
            clearTimeout(window.autoUpdateTimeout);
            window.autoUpdateTimeout = setTimeout(() => {
                performAvailabilityCheck();
            }, 300);
        }
    }

    // --- Perform Availability Check ---
    function performAvailabilityCheck() {
        if (!resultsDiv || !loader || !messageP || !step2Div || !guestForm) {
            console.error("One or more elements not found.");
            return;
        }

        const checkin = document.getElementById('checkin_date').value;
        const checkout = document.getElementById('checkout_date').value;

        if (!checkin || !checkout || checkin >= checkout) {
            messageP.textContent = 'Please select valid check-in and check-out dates.';
            messageP.style.color = 'orange';
            step2Div.style.display = 'block';
            resultsDiv.innerHTML = '';
            loader.style.display = 'none';
            return;
        }

        loader.style.display = 'block';
        resultsDiv.innerHTML = '';
        messageP.textContent = '';
        step2Div.style.display = 'block';
        guestForm.style.display = 'none';

        fetch(`/api/check_availability.php?check_in=${checkin}&check_out=${checkout}`)
            .then(response => response.json())
            .then(data => {
                loader.style.display = 'none';
                console.log('Availability Data:', data);
                renderAvailability(data, checkin, checkout);
            })
            .catch(error => {
                loader.style.display = 'none';
                messageP.textContent = `Error: ${error.message}`;
                messageP.style.color = 'red';
                console.error('Availability check failed:', error);
            });
    }

function renderAvailability(availableRooms, checkin, checkout) {
    resultsDiv.innerHTML = ''; // Clear previous results

    if (!availableRooms || availableRooms.length === 0) {
        messageP.textContent = 'No rooms available for the selected dates.';
        messageP.style.color = 'white';
        return;
    }

    const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));

    availableRooms.forEach(room => {
        const totalPrice = (parseFloat(room.price_per_night) * nights).toFixed(2);

        // Map room_type to an image file
        let roomImage = 'default_room.jpg'; // fallback
        if (room.room_type === 'Double Room') roomImage = 'room_double.jpg';
        if (room.room_type === 'Executive Suite') roomImage = 'room_executive.jpg';
        if (room.room_type === 'Suite with Balcony') roomImage = 'room_balcony.jpg';

        const card = document.createElement('div');
        card.classList.add('room-card');
        card.style = 'background: #06172D; padding: 15px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #122C55;';

        card.innerHTML = `
            <img src="assets/images/${roomImage}" alt="${room.room_type}" 
                 style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 10px;">
            <h3 style="color: #B6862C; margin-bottom: 10px;">${room.room_type}</h3>
            <p>Capacity: ${room.capacity}</p>
            <p>Price: $${parseFloat(room.price_per_night).toFixed(2)} per night</p>
            <p>Total: $${totalPrice} (${nights} night${nights > 1 ? 's' : ''})</p>
            <button class="select-room-btn" data-room="${room.room_type}" 
                style="margin-top: 10px; background: #B6862C; border: none; color: white; padding: 8px 12px; cursor: pointer; border-radius: 5px;">
                Select Room
            </button>
        `;

        resultsDiv.appendChild(card);
    });

    messageP.textContent = ''; 

    // Add event listeners to "Select Room" buttons
    document.querySelectorAll('.select-room-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const selectedRoom = this.getAttribute('data-room');
            selectedRoomInput.value = selectedRoom; // Hidden input
            step2Div.style.display = 'none'; // Hide step 2
            guestForm.style.display = 'block'; // Show guest details form
        });
    });
}



    // --- Update Booking Summary ---
    function updateBookingSummary(roomType, checkin, checkout, pricePerNight) {
        if (!bookingSummaryContent) return;
        const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
        const totalPrice = (pricePerNight * nights).toFixed(2);
        bookingSummaryContent.innerHTML = `
            <div><strong>Room:</strong> ${roomType}</div>
            <div><strong>Check-in:</strong> ${checkin}</div>
            <div><strong>Check-out:</strong> ${checkout}</div>
            <div><strong>Total:</strong> $${totalPrice} (${nights} nights)</div>`;
        bookingSummaryContent.style.display = 'block';
    }

    // --- Button Event ---
    if (checkBtn) {
        checkBtn.addEventListener('click', performAvailabilityCheck);
    }
});
</script>


<?php ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>