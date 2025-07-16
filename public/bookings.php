<?php
session_start();
$title = "Book Your Stay";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="booking-container">
    <div>
        <h1>Book Your Stay</h1>
        <p>Select your dates to see available rooms and pricing.</p>
    </div>

    <div class="card">
        <h3>Step 1: Select Your Dates</h3>
        <div id="date-selection" class="date-selection-form" style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label for="checkin_date" class="form-label">Check-in Date</label>
                <input type="date" id="checkin_date" name="checkin_date" class="form-input" required>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label for="checkout_date" class="form-label">Check-out Date</label>
                <input type="date" id="checkout_date" name="checkout_date" class="form-input" required>
            </div>
            <div style="flex: 0 0 auto; align-self: flex-end;">
                <button id="check-availability-btn" class="btn btn-primary" style="height: 40px; padding: 0 20px; transform: translateY(-22px);">Check Availability</button>
            </div>
        </div>
    </div>

    <div id="booking-step-2" style="display: none;">
        <div class="card">
            <h3>Step 2: Choose Your Room</h3>
            <div id="availability-results" class="mt-30"></div>
            <div id="results-loader" style="display: none;" class="loader"></div>
            <p id="results-message"></p>
        </div>

        <form id="guest-details-form" action="/api/submit_booking.php" method="post" class="card mt-30">
            <h3>Step 3: Enter Your Details</h3>
            
            <input type="hidden" id="selected_room_type" name="room_type">
            <input type="hidden" id="form_checkin_date" name="checkin_date">
            <input type="hidden" id="form_checkout_date" name="checkout_date">

            <div>
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-input" required>
            </div>
            <div>
                <label for="email" class="form-label">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" required>
            </div>
            <div>
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-input">
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Complete Booking</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkBtn = document.getElementById('check-availability-btn');
    const resultsDiv = document.getElementById('availability-results');
    const loader = document.getElementById('results-loader');
    const messageP = document.getElementById('results-message');
    const step2Div = document.getElementById('booking-step-2');
    const guestForm = document.getElementById('guest-details-form');
    const selectedRoomInput = document.getElementById('selected_room_type');
    const formCheckinInput = document.getElementById('form_checkin_date');
    const formCheckoutInput = document.getElementById('form_checkout_date');

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('checkin_date').setAttribute('min', today);
    document.getElementById('checkout_date').setAttribute('min', today);

    checkBtn.addEventListener('click', function() {
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
            .then(response => {
                if (!response.ok) throw new Error(`Server responded with status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                loader.style.display = 'none';
                if (data.error) throw new Error(data.error);
                
                if (data.length === 0) {
                    messageP.textContent = 'No rooms available for the selected dates. Please try different dates.';
                    messageP.style.color = 'white';
                } else {
                    const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
                    data.forEach(room => {
                        const totalPrice = (room.price_per_night * nights).toFixed(2);
                        
                        let imageFile = '';
                        switch (room.room_type) {
                            case 'Double Room':
                                imageFile = 'room_double.jpg';
                                break;
                            case 'Executive Suite':
                                imageFile = 'room_executive.jpg';
                                break;
                            case 'Suite with Balcony':
                                imageFile = 'room_balcony.jpg';
                                break;
                            default:
                                imageFile = ''; 
                        }
                        const roomImage = `assets/images/${imageFile}`;

                        const cardHTML = `
                            <div class="room-card-select">
                                <img src="${roomImage}" alt="${room.room_type}" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                                <div class="room-card-no-img" style="display:none; text-align:center; padding: 70px 20px; background: #06172D;">${room.room_type}</div>
                                <div class="card-content">
                                    <h3>${room.room_type}</h3>
                                    <p>Capacity: ${room.capacity} guests</p>
                                    <div class="price">$${totalPrice} <span>for ${nights} nights</span></div>
                                    <button class="btn btn-primary mt-30 select-room-btn" data-room-type="${room.room_type}">Select Room</button>
                                </div>
                            </div>
                        `;
                        resultsDiv.insertAdjacentHTML('beforeend', cardHTML);
                    });

                    document.querySelectorAll('.select-room-btn').forEach(button => {
                        button.addEventListener('click', function() {
                            const selectedType = this.getAttribute('data-room-type');
                            selectedRoomInput.value = selectedType;
                            formCheckinInput.value = checkin;
                            formCheckoutInput.value = checkout;

                            guestForm.style.display = 'block';
                            guestForm.scrollIntoView({ behavior: 'smooth' });
                            
                            document.querySelectorAll('.room-card-select').forEach(c => c.style.border = '1px solid #122C55');
                            this.closest('.room-card-select').style.border = '2px solid #F7B223';
                        });
                    });
                }
            })
            .catch(error => {
                loader.style.display = 'none';
                messageP.textContent = `Error: ${error.message}. Please check the browser console for more details.`;
                messageP.style.color = 'red';
                console.error('Availability check failed:', error);
            });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>