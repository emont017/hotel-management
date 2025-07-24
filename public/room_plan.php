<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'front_desk'])) {
    header("Location: /index.php");
    exit;
}

$title = "Upcoming Stays";
require_once __DIR__ . '/../includes/header.php';

// Date and Navigation Logic
$today = new DateTime('now', new DateTimeZone('America/New_York'));
$start_date_str = $_GET['start_date'] ?? $today->format('Y-m-d');
$start_date = new DateTime($start_date_str);
$days_to_show = 7;

$end_date = clone $start_date;
$end_date->modify('+' . ($days_to_show - 1) . ' days');
$prev_date = clone $start_date;
$prev_date->modify('-7 days');
$next_date = clone $start_date;
$next_date->modify('+7 days');

// Data Fetching Logic
$rooms_query = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
$rooms = [];
if ($rooms_query) {
    while($row = $rooms_query->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// Fetch all relevant bookings once
$bookings_stmt = $conn->prepare("
    SELECT b.id as booking_id, b.room_id, b.check_in, b.check_out, b.status AS booking_status, u.full_name
    FROM bookings b JOIN users u ON b.user_id = u.id
    WHERE b.room_id IN (SELECT id FROM rooms) AND b.check_out >= ? AND b.check_in <= ? AND b.status != 'cancelled'
");
$start_date_param = $start_date->format('Y-m-d');
$end_date_param = $end_date->format('Y-m-d');
$bookings_stmt->bind_param("ss", $start_date_param, $end_date_param);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings_by_room = [];
while($booking = $bookings_result->fetch_assoc()) {
    $bookings_by_room[$booking['room_id']][] = $booking;
}
$bookings_stmt->close();
?>

<style>
    .room-plan-container { overflow-x: auto; padding-bottom: 15px; border: 1px solid #122C55; border-radius: 8px; background-color: #081C3A;}
    .tape-chart { min-width: 900px; border-collapse: collapse; table-layout: fixed; }
    .tape-chart th, .tape-chart td { border: 1px solid #06172D; text-align: center; padding: 0; height: 40px; }
    .tape-chart th { color: #B6862C; font-size: 0.85rem; padding: 8px 4px; position: sticky; top: 0; z-index: 10; background-color: #0E1E40;}
    .room-number-col { color: #B6862C; font-weight: bold; position: sticky; left: 0; z-index: 5; width: 100px; background-color: #0E1E40;}
    .date-cell { font-size: 0.75rem; }
    .booking-block { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; color: #fff; font-weight: bold; font-size: 0.8rem; padding: 0 5px; box-sizing: border-box; overflow: hidden; text-decoration: none; border-radius: 4px; border: 1px solid rgba(255, 255, 255, 0.2); transition: all 0.2s ease;}
    .status-checked-in { background-color: #e74c3c; }
    .status-confirmed { background-color: #3498db; }
    
    /* Enhanced Drag & Drop Styles */
    .booking-block[draggable="true"] { cursor: grab; }
    .booking-block[draggable="true"]:active { cursor: grabbing; }
    .booking-block.dragging { 
        opacity: 0.6; 
        transform: scale(0.95); 
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        z-index: 1000;
    }
    
    .droppable { position: relative; transition: all 0.2s ease; }
    .droppable.drag-over { 
        background-color: rgba(182, 134, 44, 0.2) !important;
        border: 2px dashed #B6862C !important;
        transform: scale(1.02);
    }
    .droppable.drag-over .booking-block { opacity: 0.3; }
    
    .status-vacant-clean { background-color: #2ecc71; }
    .status-vacant-dirty { background-color: #f1c40f; }
    .status-maintenance { background-color: #95a5a6; }
    
    /* Loading Overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(8, 28, 58, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        backdrop-filter: blur(3px);
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(182, 134, 44, 0.3);
        border-top: 4px solid #B6862C;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Custom Confirmation Modal */
    .drag-confirm-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9998;
    }
    
    .drag-confirm-content {
        background: #081C3A;
        padding: 25px;
        border-radius: 12px;
        border: 2px solid #B6862C;
        max-width: 400px;
        text-align: center;
        animation: slideInUp 0.3s ease;
    }
    
    .drag-confirm-content h3 {
        margin-bottom: 15px;
        color: #B6862C;
    }
    
    .drag-confirm-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 20px;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Success Animation */
    .booking-move-success {
        animation: successPulse 0.6s ease;
    }
    
    @keyframes successPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); background-color: #2ecc71; }
        100% { transform: scale(1); }
    }
    
    /* Keyboard Accessibility */
    .booking-selected {
        outline: 3px solid #B6862C !important;
        outline-offset: 2px;
        animation: selectedPulse 1.5s ease-in-out infinite;
    }
    
    @keyframes selectedPulse {
        0%, 100% { outline-color: #B6862C; }
        50% { outline-color: rgba(182, 134, 44, 0.5); }
    }
    
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    .legend { list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; align-items: center;}
    .legend li { display: flex; align-items: center; gap: 8px; }
    .legend .color-box { width: 20px; height: 20px; border: 1px solid #fff; border-radius: 4px;}
</style>

<div class="mb-20" style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Upcoming Stays</h2>
    <div style="display: flex; gap: 10px;">
        <a href="?start_date=<?= $prev_date->format('Y-m-d') ?>" class="btn btn-primary">&larr; Previous</a>
        <a href="?start_date=<?= $next_date->format('Y-m-d') ?>" class="btn btn-primary">Next &rarr;</a>
    </div>
</div>

<ul class="legend">
    <li><span class="color-box" style="background-color: #e74c3c;"></span> Checked-In</li>
    <li><span class="color-box" style="background-color: #3498db;"></span> Confirmed</li>
    <li><span class="color-box" style="background-color: #2ecc71;"></span> Vacant (Clean)</li>
    <li><span class="color-box" style="background-color: #f1c40f;"></span> Vacant (Dirty)</li>
    <li><span class="color-box" style="background-color: #95a5a6;"></span> Maintenance</li>
</ul>

<div class="room-plan-container">
    <table class="tape-chart">
        <thead>
            <tr>
                <th class="room-number-col">Room</th>
                <?php
                $current_header_date = clone $start_date;
                for ($i = 0; $i < $days_to_show; $i++) {
                    echo '<th>' . $current_header_date->format('D') . '<br><small>' . $current_header_date->format('M j') . '</small></th>';
                    $current_header_date->modify("+1 day");
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td class="room-number-col"><?= htmlspecialchars($room['room_number']) ?></td>
                    <?php
                    for ($i = 0; $i < $days_to_show; $i++) {
                        $current_date_cell = clone $start_date;
                        $current_date_cell->modify("+$i days");
                        $cell_date_str = $current_date_cell->format('Y-m-d');
                        
                        $booking_for_this_day = null;
                        
                        if (isset($bookings_by_room[$room['id']])) {
                            foreach ($bookings_by_room[$room['id']] as $booking) {
                                if ($cell_date_str >= $booking['check_in'] && $cell_date_str < $booking['check_out']) {
                                    $booking_for_this_day = $booking;
                                    break;
                                }
                            }
                        }

                        if ($booking_for_this_day) {
                            if ($cell_date_str == $booking_for_this_day['check_in']) {
                                $check_in_dt = new DateTime($booking_for_this_day['check_in']);
                                $check_out_dt = new DateTime($booking_for_this_day['check_out']);
                                $duration = $check_in_dt->diff($check_out_dt)->days;
                                $duration = max(1, $duration);
                                $status_class = 'status-' . htmlspecialchars($booking_for_this_day['booking_status']);
                                
                                
echo "<td colspan='{$duration}' class='date-cell'>
  <div class='booking-block {$status_class}' 
       data-booking-id='{$booking_for_this_day['booking_id']}' 
       data-room-id='{$room['id']}' 
       draggable='true'>
    " . htmlspecialchars($booking_for_this_day['full_name']) . "
  </div>
</td>";
                                
                                $i += $duration - 1;
                            }
                        } else {
                            $status_class = $room['housekeeping_status'] === 'clean' ? 'status-vacant-clean' : 'status-vacant-dirty';
                            
                            // THIS LINE IS NOW CORRECTED
                            if ($room['status'] === 'maintenance') {
                                $status_class = 'status-maintenance';
                            }
                             
                            echo "<td class='date-cell droppable' 
             data-date='{$cell_date_str}' 
             data-room-id='{$room['id']}'>
        <div class='booking-block {$status_class}' style='opacity: 0.5;'></div>
      </td>";
                        }
                    }
                    ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const bookings = document.querySelectorAll('.booking-block[draggable="true"]');
    const droppableCells = document.querySelectorAll('.droppable');
    let draggedElement = null;

    // Create loading overlay
    function showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
        return overlay;
    }

    // Custom confirmation modal
    function showConfirmModal(message) {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'drag-confirm-modal';
            modal.innerHTML = `
                <div class="drag-confirm-content">
                    <h3>Confirm Move</h3>
                    <p>${message}</p>
                    <div class="drag-confirm-buttons">
                        <button class="btn btn-primary confirm-yes">Yes, Move</button>
                        <button class="btn btn-secondary confirm-no">Cancel</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            modal.querySelector('.confirm-yes').onclick = () => {
                document.body.removeChild(modal);
                resolve(true);
            };
            
            modal.querySelector('.confirm-no').onclick = () => {
                document.body.removeChild(modal);
                resolve(false);
            };
            
            // Close on background click
            modal.onclick = (e) => {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                    resolve(false);
                }
            };
        });
    }

    bookings.forEach(booking => {
        booking.addEventListener('dragstart', (e) => {
            draggedElement = booking;
            booking.classList.add('dragging');
            e.dataTransfer.setData('text/plain', JSON.stringify({
                bookingId: booking.getAttribute('data-booking-id'),
                oldRoomId: booking.getAttribute('data-room-id')
            }));
            
            // Add ghost image effect
            e.dataTransfer.effectAllowed = 'move';
        });

        booking.addEventListener('dragend', () => {
            if (draggedElement) {
                draggedElement.classList.remove('dragging');
                draggedElement = null;
            }
            
            // Remove all hover states
            droppableCells.forEach(cell => {
                cell.classList.remove('drag-over');
            });
        });
        
        // Accessibility: Double-click to select booking for keyboard users
        booking.addEventListener('dblclick', () => {
            // Remove any existing selection
            document.querySelectorAll('.booking-selected').forEach(el => {
                el.classList.remove('booking-selected');
            });
            
            booking.classList.add('booking-selected');
            
            // Show helper message
            const helpText = document.createElement('div');
            helpText.className = 'keyboard-help';
            helpText.innerHTML = 'Booking selected. Click on a vacant cell to move it there.';
            helpText.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: #081C3A;
                color: #B6862C;
                padding: 10px 20px;
                border-radius: 8px;
                border: 1px solid #B6862C;
                z-index: 10000;
                font-weight: bold;
                animation: slideInDown 0.3s ease;
            `;
            
            document.body.appendChild(helpText);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (helpText.parentNode) {
                    helpText.remove();
                }
            }, 4000);
        });
    });

    droppableCells.forEach(cell => {
        cell.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            if (!cell.classList.contains('drag-over')) {
                cell.classList.add('drag-over');
            }
        });

        cell.addEventListener('dragleave', (e) => {
            // Only remove highlight if we're actually leaving the cell
            if (!cell.contains(e.relatedTarget)) {
                cell.classList.remove('drag-over');
            }
        });

        cell.addEventListener('drop', async (e) => {
            e.preventDefault();
            cell.classList.remove('drag-over');

            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            const newRoomId = cell.getAttribute('data-room-id');
            const newDate = cell.getAttribute('data-date');

            // Format date for display
            const dateObj = new Date(newDate);
            const formattedDate = dateObj.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            });

            const confirmed = await showConfirmModal(
                `Move this booking to room ${cell.closest('tr').querySelector('.room-number-col').textContent} on ${formattedDate}?`
            );

            if (confirmed) {
                const loadingOverlay = showLoadingOverlay();
                
                try {
                    const response = await fetch('/api/move_booking.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            bookingId: data.bookingId,
                            newRoomId: newRoomId,
                            newStartDate: newDate
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Add success animation before reload
                        if (draggedElement) {
                            draggedElement.classList.add('booking-move-success');
                        }
                        
                        setTimeout(() => {
                            location.reload();
                        }, 600);
                    } else {
                        document.body.removeChild(loadingOverlay);
                        
                        // Custom error modal instead of alert
                        const errorModal = document.createElement('div');
                        errorModal.className = 'drag-confirm-modal';
                        errorModal.innerHTML = `
                            <div class="drag-confirm-content">
                                <h3 style="color: #e74c3c;">Move Failed</h3>
                                <p>${result.message}</p>
                                <div class="drag-confirm-buttons">
                                    <button class="btn btn-primary">OK</button>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(errorModal);
                        errorModal.querySelector('.btn').onclick = () => {
                            document.body.removeChild(errorModal);
                        };
                    }
                } catch (err) {
                    document.body.removeChild(loadingOverlay);
                    console.error('Error moving booking:', err);
                    
                    const errorModal = document.createElement('div');
                    errorModal.className = 'drag-confirm-modal';
                    errorModal.innerHTML = `
                        <div class="drag-confirm-content">
                            <h3 style="color: #e74c3c;">Network Error</h3>
                            <p>Unable to move booking. Please check your connection and try again.</p>
                            <div class="drag-confirm-buttons">
                                <button class="btn btn-primary">OK</button>
                            </div>
                        </div>
                    `;
                    
                    document.body.appendChild(errorModal);
                    errorModal.querySelector('.btn').onclick = () => {
                        document.body.removeChild(errorModal);
                    };
                                 }
             }
         });
        
        // Keyboard accessibility: Click handler for vacant cells
        if (cell.querySelector('.booking-block[style*="opacity: 0.5"]')) {
            cell.addEventListener('click', async (e) => {
                const selectedBooking = document.querySelector('.booking-selected');
                if (!selectedBooking) return;
                
                e.preventDefault();
                
                const bookingId = selectedBooking.getAttribute('data-booking-id');
                const oldRoomId = selectedBooking.getAttribute('data-room-id');
                const newRoomId = cell.getAttribute('data-room-id');
                const newDate = cell.getAttribute('data-date');
                
                // Format date for display
                const dateObj = new Date(newDate);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    month: 'short', 
                    day: 'numeric' 
                });

                const confirmed = await showConfirmModal(
                    `Move this booking to room ${cell.closest('tr').querySelector('.room-number-col').textContent} on ${formattedDate}?`
                );

                if (confirmed) {
                    const loadingOverlay = showLoadingOverlay();
                    
                    try {
                        const response = await fetch('/api/move_booking.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                bookingId: bookingId,
                                newRoomId: newRoomId,
                                newStartDate: newDate
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Remove selection and add success animation
                            selectedBooking.classList.remove('booking-selected');
                            selectedBooking.classList.add('booking-move-success');
                            
                            // Remove any help text
                            const helpText = document.querySelector('.keyboard-help');
                            if (helpText) helpText.remove();
                            
                            setTimeout(() => {
                                location.reload();
                            }, 600);
                        } else {
                            document.body.removeChild(loadingOverlay);
                            
                            const errorModal = document.createElement('div');
                            errorModal.className = 'drag-confirm-modal';
                            errorModal.innerHTML = `
                                <div class="drag-confirm-content">
                                    <h3 style="color: #e74c3c;">Move Failed</h3>
                                    <p>${result.message}</p>
                                    <div class="drag-confirm-buttons">
                                        <button class="btn btn-primary">OK</button>
                                    </div>
                                </div>
                            `;
                            
                            document.body.appendChild(errorModal);
                            errorModal.querySelector('.btn').onclick = () => {
                                document.body.removeChild(errorModal);
                            };
                        }
                    } catch (err) {
                        document.body.removeChild(loadingOverlay);
                        console.error('Error moving booking:', err);
                        
                        const errorModal = document.createElement('div');
                        errorModal.className = 'drag-confirm-modal';
                        errorModal.innerHTML = `
                            <div class="drag-confirm-content">
                                <h3 style="color: #e74c3c;">Network Error</h3>
                                <p>Unable to move booking. Please check your connection and try again.</p>
                                <div class="drag-confirm-buttons">
                                    <button class="btn btn-primary">OK</button>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(errorModal);
                        errorModal.querySelector('.btn').onclick = () => {
                            document.body.removeChild(errorModal);
                        };
                    }
                } else {
                    // User cancelled, remove selection
                    selectedBooking.classList.remove('booking-selected');
                    const helpText = document.querySelector('.keyboard-help');
                    if (helpText) helpText.remove();
                }
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>