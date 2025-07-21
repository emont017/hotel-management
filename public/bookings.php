<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if a room type was pre-selected from rooms.php
$preselected_room_type = isset($_GET['type']) ? urldecode($_GET['type']) : null;

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
                    <p style="margin: 0; font-size: 0.9rem; color: #ccc;">✓ Room type selected - Now choose your dates below</p>
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
    <div class="card">
        <h3><?= $preselected_room_type ? 'Choose Your Dates' : 'Step 1: Select Your Dates' ?></h3>
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

    <!-- Results Section -->
    <div id="booking-step-2" style="display: none;">
        <div class="card">
            <h3><?= $preselected_room_type ? 'Availability Confirmation' : 'Step 2: Choose Your Room' ?></h3>
            <div id="availability-results" class="mt-30"></div>
            <div id="results-loader" style="display: none;" class="loader"></div>
            <p id="results-message"></p>
        </div>

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
    const bookingSummaryContent = document.getElementById('booking-summary-content');
    
    // Check if we have a preselected room type
    const preselectedRoomType = selectedRoomInput.value;
    let blockedDates = [];
    let checkinPicker, checkoutPicker;
    let selectedCheckin = null;
    let selectedCheckout = null;
    
    // Check if user has complete profile for auto-show guest details
    const hasCompleteProfile = <?= $has_complete_profile ? 'true' : 'false' ?>;

    // Small delay to ensure DOM is ready
    setTimeout(() => {
        // Load blocked dates and initialize date pickers
        if (preselectedRoomType) {
            loadBlockedDates(preselectedRoomType);
        } else {
            initializeDatePickers([]);
        }
    }, 100);

    function loadBlockedDates(roomType) {
        fetch(`/api/get_blocked_dates.php?room_type=${encodeURIComponent(roomType)}`)
            .then(response => response.json())
            .then(data => {
                if (data.blocked_dates) {
                    blockedDates = data.blocked_dates;
                    initializeDatePickers(blockedDates);
                }
            })
            .catch(error => {
                initializeDatePickers([]);
            });
    }

    function initializeDatePickers(disabledDates) {
        const today = new Date();
        
        // Check if elements exist
        const checkinEl = document.getElementById('checkin_date');
        const checkoutEl = document.getElementById('checkout_date');
        
        if (!checkinEl || !checkoutEl) {
            console.error('Date input elements not found');
            return;
        }
        
        // Convert blocked dates to Date objects for Flatpickr
        const disabledDateObjects = disabledDates.map(date => new Date(date + 'T00:00:00'));
        
        // Destroy existing pickers if they exist
        if (checkinPicker) {
            checkinPicker.destroy();
        }
        if (checkoutPicker) {
            checkoutPicker.destroy();
        }
        
        // Base configuration for both pickers
        const baseConfig = {
            minDate: today,
            disable: disabledDateObjects,
            dateFormat: "Y-m-d",
            allowInput: false,
            clickOpens: true,
            static: false
        };

        // Check-in date picker
        checkinPicker = flatpickr(checkinEl, {
            ...baseConfig,
            onChange: function(selectedDates, dateStr, instance) {
                selectedCheckin = dateStr;
                
                // Update checkout picker constraints
                if (checkoutPicker && dateStr) {
                    const nextDay = new Date(dateStr + 'T00:00:00');
                    nextDay.setDate(nextDay.getDate() + 1);
                    checkoutPicker.set('minDate', nextDay);
                    
                    // Clear checkout if it's invalid
                    const currentCheckout = checkoutPicker.selectedDates[0];
                    if (currentCheckout && currentCheckout <= selectedDates[0]) {
                        checkoutPicker.clear();
                        selectedCheckout = null;
                    }
                    
                    // Update checkout calendar if it's open
                    if (checkoutPicker.isOpen) {
                        setTimeout(() => markCheckinInCheckoutCalendar(checkoutPicker), 100);
                    }
                }
                
                // Update visual feedback
                updateDateSelectionFeedback();
                
                // Auto-update availability if section is already visible and we have both dates
                autoUpdateAvailability();
            },
            onOpen: function(selectedDates, dateStr, instance) {
                // Mark checkout date when checkin calendar opens
                setTimeout(() => markCheckoutInCheckinCalendar(instance), 100);
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                // Re-mark checkout date when month changes
                setTimeout(() => markCheckoutInCheckinCalendar(instance), 100);
            },
            onYearChange: function(selectedDates, dateStr, instance) {
                // Re-mark checkout date when year changes
                setTimeout(() => markCheckoutInCheckinCalendar(instance), 100);
            }
        });

        // Check-out date picker
        checkoutPicker = flatpickr(checkoutEl, {
            ...baseConfig,
            onChange: function(selectedDates, dateStr, instance) {
                selectedCheckout = dateStr;
                
                // Update checkin calendar if it's open
                if (checkinPicker && checkinPicker.isOpen) {
                    setTimeout(() => markCheckoutInCheckinCalendar(checkinPicker), 100);
                }
                
                updateDateSelectionFeedback();
                
                // Auto-update availability if section is already visible and we have both dates
                autoUpdateAvailability();
            },
            onOpen: function(selectedDates, dateStr, instance) {
                // Mark checkin date when checkout calendar opens
                setTimeout(() => markCheckinInCheckoutCalendar(instance), 100);
            },
            onMonthChange: function(selectedDates, dateStr, instance) {
                // Re-mark checkin date when month changes
                setTimeout(() => markCheckinInCheckoutCalendar(instance), 100);
            },
            onYearChange: function(selectedDates, dateStr, instance) {
                // Re-mark checkin date when year changes
                setTimeout(() => markCheckinInCheckoutCalendar(instance), 100);
            }
        });
        
        // Add fallback click handlers
        if (checkinPicker && checkoutPicker) {
            checkinEl.addEventListener('click', function() {
                if (checkinPicker && !checkinPicker.isOpen) {
                    checkinPicker.open();
                }
            });
            
            checkoutEl.addEventListener('click', function() {
                if (checkoutPicker && !checkoutPicker.isOpen) {
                    checkoutPicker.open();
                }
            });
        }
    }

    function updateDateSelectionFeedback() {
        // Removed redundant feedback - availability is confirmed in the availability confirmation section
    }

    // Function to mark the check-in date in the checkout calendar
    function markCheckinInCheckoutCalendar(checkoutPickerInstance) {
        if (!selectedCheckin || !checkoutPickerInstance) {
            console.log('markCheckinInCheckoutCalendar: Missing data');
            return;
        }
        
        console.log(`Marking check-in ${selectedCheckin} in checkout calendar`);
        
        // Remove existing indicators and reset styles
        cleanupIndicators(checkoutPickerInstance, 'checkin-indicator');
        
        // Add check-in indicator
        markDateInCalendar(checkoutPickerInstance, selectedCheckin, 'checkin-indicator');
        
        // Also show checkout indicator if both dates are selected
        if (selectedCheckout) {
            markDateInCalendar(checkoutPickerInstance, selectedCheckout, 'checkout-indicator');
        }
    }

    // Function to mark the check-out date in the checkin calendar
    function markCheckoutInCheckinCalendar(checkinPickerInstance) {
        if (!selectedCheckout || !checkinPickerInstance) {
            console.log('markCheckoutInCheckinCalendar: Missing data');
            return;
        }
        
        console.log(`Marking check-out ${selectedCheckout} in checkin calendar`);
        
        // Remove existing indicators and reset styles
        cleanupIndicators(checkinPickerInstance, 'checkout-indicator');
        
        // Add check-out indicator
        markDateInCalendar(checkinPickerInstance, selectedCheckout, 'checkout-indicator');
        
        // Also show checkin indicator if both dates are selected
        if (selectedCheckin) {
            markDateInCalendar(checkinPickerInstance, selectedCheckin, 'checkin-indicator');
        }
    }

    // Function to clean up existing indicators
    function cleanupIndicators(pickerInstance, className) {
        // Remove range indicators first
        removeRangeIndicators(pickerInstance);
        
        const existingIndicators = pickerInstance.calendarContainer.querySelectorAll(`.${className}`);
        existingIndicators.forEach(el => {
            el.classList.remove(className);
            // Reset inline styles
            el.style.background = '';
            el.style.color = '';
            el.style.fontWeight = '';
            el.style.border = '';
            el.style.borderLeft = '';
            el.style.borderRight = '';
            el.style.transform = '';
            el.style.boxShadow = '';
            el.style.position = '';
            
            // Remove text indicator
            const indicator = el.querySelector('.date-indicator');
            if (indicator) {
                indicator.remove();
            }
        });
    }

    // Helper function to mark a specific date in a calendar
    function markDateInCalendar(pickerInstance, dateStr, className) {
        if (!dateStr || !pickerInstance) {
            console.log('markDateInCalendar: Missing dateStr or pickerInstance');
            return;
        }
        
        console.log(`Marking ${dateStr} with class ${className}`);
        
        const targetDate = new Date(dateStr + 'T00:00:00');
        const allDayElements = pickerInstance.calendarContainer.querySelectorAll('.flatpickr-day');
        
        console.log(`Found ${allDayElements.length} day elements`);
        
        let found = false;
        
        // Remove existing range indicators first
        removeRangeIndicators(pickerInstance);
        allDayElements.forEach(dayEl => {
            if (dayEl.dateObj) {
                const dayDate = new Date(dayEl.dateObj);
                
                // Compare dates (year, month, day)
                if (dayDate.getFullYear() === targetDate.getFullYear() &&
                    dayDate.getMonth() === targetDate.getMonth() &&
                    dayDate.getDate() === targetDate.getDate()) {
                    
                    console.log(`Found matching date: ${dateStr}, adding class ${className}`);
                    dayEl.classList.add(className);
                    found = true;
                    
                    // Add subtle, professional styling
                    if (className === 'checkin-indicator') {
                        dayEl.style.background = 'rgba(182, 134, 44, 0.15)';
                        dayEl.style.color = '#B6862C';
                        dayEl.style.fontWeight = '600';
                        dayEl.style.border = '1px solid #B6862C';
                        dayEl.style.position = 'relative';
                        dayEl.title = `Check-in Date: ${formatDateForDisplay(dateStr)}`;
                        
                        // Add subtle corner indicator
                        if (!dayEl.querySelector('.date-indicator')) {
                            const indicator = document.createElement('div');
                            indicator.className = 'date-indicator';
                            indicator.style.cssText = `
                                position: absolute;
                                top: 2px;
                                right: 2px;
                                width: 6px;
                                height: 6px;
                                background: #B6862C;
                                border-radius: 50%;
                                z-index: 10;
                            `;
                            dayEl.appendChild(indicator);
                        }
                        
                    } else if (className === 'checkout-indicator') {
                        dayEl.style.background = 'rgba(182, 134, 44, 0.08)';
                        dayEl.style.color = '#B6862C';
                        dayEl.style.fontWeight = '500';
                        dayEl.style.border = '1px dashed #B6862C';
                        dayEl.style.position = 'relative';
                        dayEl.title = `Check-out Date: ${formatDateForDisplay(dateStr)}`;
                        
                        // Add subtle corner indicator
                        if (!dayEl.querySelector('.date-indicator')) {
                            const indicator = document.createElement('div');
                            indicator.className = 'date-indicator';
                            indicator.style.cssText = `
                                position: absolute;
                                top: 2px;
                                right: 2px;
                                width: 6px;
                                height: 6px;
                                background: transparent;
                                border: 1px solid #B6862C;
                                border-radius: 50%;
                                z-index: 10;
                            `;
                            dayEl.appendChild(indicator);
                        }
                    }
                }
            }
        });
        
        if (!found) {
            console.log(`Date ${dateStr} not found in current calendar view`);
        }
        
        // Draw range line if both dates are selected and visible
        drawRangeLine(pickerInstance);
    }

    // Function to remove existing range indicators
    function removeRangeIndicators(pickerInstance) {
        const existingLines = pickerInstance.calendarContainer.querySelectorAll('.range-line, .range-fill');
        existingLines.forEach(line => line.remove());
        
        const existingRangeElements = pickerInstance.calendarContainer.querySelectorAll('.in-range');
        existingRangeElements.forEach(el => {
            el.classList.remove('in-range');
            el.style.background = '';
        });
    }

    // Function to draw a subtle line connecting check-in to check-out dates
    function drawRangeLine(pickerInstance) {
        if (!selectedCheckin || !selectedCheckout || !pickerInstance) return;
        
        const checkinDate = new Date(selectedCheckin + 'T00:00:00');
        const checkoutDate = new Date(selectedCheckout + 'T00:00:00');
        
        // Only draw if checkout is after checkin
        if (checkoutDate <= checkinDate) return;
        
        const allDayElements = pickerInstance.calendarContainer.querySelectorAll('.flatpickr-day');
        let checkinElement = null;
        let checkoutElement = null;
        let rangeDates = [];
        
        // Find checkin, checkout, and all dates in between
        allDayElements.forEach(dayEl => {
            if (dayEl.dateObj) {
                const dayDate = new Date(dayEl.dateObj);
                
                // Check if this is checkin date
                if (dayDate.getFullYear() === checkinDate.getFullYear() &&
                    dayDate.getMonth() === checkinDate.getMonth() &&
                    dayDate.getDate() === checkinDate.getDate()) {
                    checkinElement = dayEl;
                }
                
                // Check if this is checkout date
                if (dayDate.getFullYear() === checkoutDate.getFullYear() &&
                    dayDate.getMonth() === checkoutDate.getMonth() &&
                    dayDate.getDate() === checkoutDate.getDate()) {
                    checkoutElement = dayEl;
                }
                
                // Check if this date is between checkin and checkout
                if (dayDate > checkinDate && dayDate < checkoutDate) {
                    rangeDates.push(dayEl);
                }
            }
        });
        
        // Add subtle background to dates in between
        rangeDates.forEach(dayEl => {
            dayEl.classList.add('in-range');
            dayEl.style.background = 'rgba(182, 134, 44, 0.05)';
            dayEl.style.position = 'relative';
        });
        
        // If both dates are visible, draw connecting visual cues
        if (checkinElement && checkoutElement) {
            // Add visual connection indicators
            checkinElement.style.borderRight = '2px solid rgba(182, 134, 44, 0.3)';
            checkoutElement.style.borderLeft = '2px solid rgba(182, 134, 44, 0.3)';
        }
    }

    function formatDateForDisplay(dateStr) {
        // Fix timezone issue by creating date object properly
        const dateParts = dateStr.split('-');
        const year = parseInt(dateParts[0]);
        const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
        const day = parseInt(dateParts[2]);
        
        const date = new Date(year, month, day);
        return date.toLocaleDateString('en-US', { 
            weekday: 'short', month: 'short', day: 'numeric' 
        });
    }

    function formatDateLong(dateStr) {
        // Same timezone-safe approach for long format
        const dateParts = dateStr.split('-');
        const year = parseInt(dateParts[0]);
        const month = parseInt(dateParts[1]) - 1; // Month is 0-indexed
        const day = parseInt(dateParts[2]);
        
        const date = new Date(year, month, day);
        return date.toLocaleDateString('en-US', { 
            weekday: 'long', month: 'long', day: 'numeric' 
        });
    }

    function updateBookingSummary(roomType, checkin, checkout, pricePerNight) {
        if (!bookingSummaryContent) return;
        
        const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
        const totalPrice = (pricePerNight * nights).toFixed(2);
        
        const checkinFormatted = formatDateForDisplay(checkin);
        const checkoutFormatted = formatDateForDisplay(checkout);
        
        bookingSummaryContent.innerHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.95rem;">
                <div><strong>Room:</strong><br>${roomType}</div>
                <div><strong>Duration:</strong><br>${nights} night${nights > 1 ? 's' : ''}</div>
                <div><strong>Check-in:</strong><br>${checkinFormatted}</div>
                <div><strong>Check-out:</strong><br>${checkoutFormatted}</div>
            </div>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #122C55; text-align: center;">
                <div style="font-size: 1.2rem;"><strong>Total: $${totalPrice}</strong></div>
                <div style="font-size: 0.9rem; color: #ccc;">($${pricePerNight}/night × ${nights} night${nights > 1 ? 's' : ''})</div>
            </div>
        `;
        bookingSummaryContent.style.display = 'block';
    }

    // Function to auto-update availability when dates change and section is visible
    function autoUpdateAvailability() {
        // Only auto-update if we have a preselected room type and the availability section is visible
        if (!preselectedRoomType || step2Div.style.display === 'none') {
            return;
        }
        
        const checkin = document.getElementById('checkin_date').value;
        const checkout = document.getElementById('checkout_date').value;
        
        // Only proceed if both dates are selected and valid
        if (checkin && checkout && checkin < checkout) {
            // Add a small delay to avoid multiple rapid calls
            clearTimeout(window.autoUpdateTimeout);
            window.autoUpdateTimeout = setTimeout(() => {
                performAvailabilityCheck();
            }, 300);
        }
    }

    // Refactored availability check function
    function performAvailabilityCheck() {
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

        // Since we're using Flatpickr with disabled dates, blocked dates can't be selected
        // No need for manual validation here - the calendar prevents selection of blocked dates

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
                
                // Filter data if we have a preselected room type
                let availableRooms = data;
                if (preselectedRoomType) {
                    availableRooms = data.filter(room => room.room_type === preselectedRoomType);
                }
                
                if (availableRooms.length === 0) {
                    if (preselectedRoomType) {
                        messageP.innerHTML = `
                            <div style="text-align: center; padding: 20px;">
                                <p style="color: #f1c40f; margin-bottom: 15px;">
                                    ⚠️ The ${preselectedRoomType} is not available for the selected dates.
                                </p>
                                <p style="margin-bottom: 15px;">Would you like to:</p>
                                <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                    <button onclick="document.getElementById('checkin_date').focus()" class="btn btn-secondary btn-sm">
                                        Try Different Dates
                                    </button>
                                    <a href="bookings.php?check_in=${checkin}&check_out=${checkout}" class="btn btn-primary btn-sm">
                                        See All Available Rooms
                                    </a>
                                </div>
                            </div>
                        `;
                    } else {
                        messageP.textContent = 'No rooms available for the selected dates. Please try different dates.';
                        messageP.style.color = 'white';
                    }
                } else {
                    const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
                    
                    if (preselectedRoomType && availableRooms.length === 1) {
                        // For preselected room type, show confirmation and auto-proceed
                        const room = availableRooms[0];
                        const totalPrice = (room.price_per_night * nights).toFixed(2);
                        
                        resultsDiv.innerHTML = `
                            <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #06172D 0%, #081C3A 100%); border-radius: 10px; border: 2px solid #2ecc71;">
                                <div style="font-size: 3rem; color: #2ecc71; margin-bottom: 15px;">✓</div>
                                <h3 style="color: #2ecc71; margin-bottom: 10px;">Great! Your room is available</h3>
                                <p style="font-size: 1.1rem; margin-bottom: 20px;">
                                    ${room.room_type} for ${nights} night${nights > 1 ? 's' : ''} - <strong>$${totalPrice}</strong>
                                </p>
                                <p style="color: #ccc; margin-bottom: 25px;">
                                    ${formatDateLong(checkin)} to 
                                    ${formatDateLong(checkout)}
                                </p>
                                ${hasCompleteProfile ? 
                                    '<p style="color: #2ecc71; margin-bottom: 15px; font-size: 0.9rem;">✓ Your details are ready - proceeding to booking form...</p>' :
                                    '<button class="btn btn-primary" style="padding: 12px 30px; font-size: 1.1rem;" onclick="proceedToGuestDetails()">Continue to Guest Details</button>'
                                }
                            </div>
                        `;
                        
                        // Auto-fill the form
                        selectedRoomInput.value = room.room_type;
                        formCheckinInput.value = checkin;
                        formCheckoutInput.value = checkout;
                        
                        // Update booking summary
                        updateBookingSummary(room.room_type, checkin, checkout, room.price_per_night);
                        
                        // For users with complete profiles, automatically show guest details form
                        if (hasCompleteProfile) {
                            setTimeout(() => {
                                proceedToGuestDetails();
                            }, 1500); // Small delay to let user see the confirmation
                        }
                        
                    } else {
                        // Show available rooms for selection
                        availableRooms.forEach(room => {
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
                                        <button class="btn btn-primary mt-30 select-room-btn" data-room-type="${room.room_type}" data-price="${room.price_per_night}">Select Room</button>
                                    </div>
                                </div>
                            `;
                            resultsDiv.insertAdjacentHTML('beforeend', cardHTML);
                        });

                        // Add event listeners for room selection
                        document.querySelectorAll('.select-room-btn').forEach(button => {
                            button.addEventListener('click', function() {
                                const selectedType = this.getAttribute('data-room-type');
                                const pricePerNight = parseFloat(this.getAttribute('data-price'));
                                
                                selectedRoomInput.value = selectedType;
                                formCheckinInput.value = checkin;
                                formCheckoutInput.value = checkout;

                                // Update booking summary if element exists
                                updateBookingSummary(selectedType, checkin, checkout, pricePerNight);

                                guestForm.style.display = 'block';
                                guestForm.scrollIntoView({ behavior: 'smooth' });
                                
                                // Visual feedback
                                document.querySelectorAll('.room-card-select').forEach(c => c.style.border = '1px solid #122C55');
                                this.closest('.room-card-select').style.border = '2px solid #B6862C';
                                
                                // If this is a different room type, reload blocked dates
                                if (selectedType !== preselectedRoomType) {
                                    loadBlockedDates(selectedType);
                                }
                            });
                        });
                    }
                }
            })
            .catch(error => {
                loader.style.display = 'none';
                messageP.textContent = `Error: ${error.message}. Please check the browser console for more details.`;
                messageP.style.color = 'red';
                console.error('Availability check failed:', error);
            });
    }

    checkBtn.addEventListener('click', function() {
        performAvailabilityCheck();
    });

    // Global function for proceed button
    window.proceedToGuestDetails = function() {
        guestForm.style.display = 'block';
        guestForm.scrollIntoView({ behavior: 'smooth' });
    };
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>