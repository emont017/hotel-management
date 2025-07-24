<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';
$title = "Housekeeping Management";

// Security & Role Management
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'housekeeping'])) {
    header("Location: /index.php");
    exit;
}
$user_role = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];
$feedback_message = '';
$feedback_type = '';

// Handle Status Update Form Submission (for Admins/Managers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (in_array($user_role, ['admin', 'manager'])) {
        $room_id = (int)$_POST['room_id'];
        $new_status = $_POST['new_status'];
        $allowed_statuses = ['clean', 'dirty', 'maintenance'];

        if ($room_id > 0 && in_array($new_status, $allowed_statuses)) {
            $conn->begin_transaction();
            try {
                // Update the room's status
                $stmt_update = $conn->prepare("UPDATE rooms SET housekeeping_status = ? WHERE id = ?");
                $stmt_update->bind_param("si", $new_status, $room_id);
                $stmt_update->execute();
                $stmt_update->close();

                // Log the change in housekeeping_logs (existing logging)
                $log_notes = "Status changed by " . $_SESSION['username'];
                $stmt_log = $conn->prepare("INSERT INTO housekeeping_logs (room_id, status, updated_by, notes) VALUES (?, ?, ?, ?)");
                $stmt_log->bind_param("isis", $room_id, $new_status, $user_id, $log_notes);
                $stmt_log->execute();
                $stmt_log->close();
                
                // Also log in audit_logs for centralized tracking
                log_room_event($conn, $user_id, 'Room Status Changed', $room_id, 
                    "Room status changed from previous to {$new_status} by " . $_SESSION['username']);
                
                $conn->commit();
                $feedback_message = "Room status updated successfully!";
                $feedback_type = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $feedback_message = "Error updating status: " . $e->getMessage();
                $feedback_type = 'danger';
            }
        } else {
            $feedback_message = "Invalid data provided for status update.";
            $feedback_type = 'danger';
        }
    }
}

// Handle Task Completion (for Housekeepers)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task'])) {
    $task_id = (int)$_POST['task_id'];
    $room_id = (int)$_POST['room_id'];

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("UPDATE housekeeping_tasks SET status = 'completed' WHERE id = ? AND assigned_to_user_id = ?");
        $stmt1->bind_param("ii", $task_id, $user_id);
        $stmt1->execute();
        $stmt1->close();

        $stmt2 = $conn->prepare("UPDATE rooms SET housekeeping_status = 'clean' WHERE id = ?");
        $stmt2->bind_param("i", $room_id);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
        $feedback_message = "Task completed and room marked as clean!";
        $feedback_type = 'success';
    } catch(Exception $e) {
        $conn->rollback();
        $feedback_message = "Error completing task.";
        $feedback_type = 'danger';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>Housekeeping Management</h1>
        <p>Monitor room status, manage cleaning assignments, and track housekeeping operations for optimal guest satisfaction.</p>
    </div>
</div>

<?php if ($feedback_message): ?>
    <div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 30px;">
        <span class="alert-icon"><?= $feedback_type === 'success' ? '✓' : '⚠️' ?></span>
        <span><?= htmlspecialchars($feedback_message) ?></span>
    </div>
<?php endif; ?>

<?php if (in_array($user_role, ['admin', 'manager'])): ?>
    <?php
    // --- Data Fetching Logic for Admin/Manager View ---
    $all_rooms_result = $conn->query("SELECT * FROM rooms ORDER BY room_number ASC");
    $all_rooms = [];
    while($room = $all_rooms_result->fetch_assoc()) {
        $all_rooms[$room['id']] = $room;
    }
    $today = date('Y-m-d');
    
    // Get active bookings
    $active_bookings = [];
    $bookings_result = $conn->query("SELECT b.room_id, u.full_name FROM bookings b JOIN users u ON b.user_id = u.id WHERE b.status = 'checked-in' AND '$today' >= b.check_in AND '$today' < b.check_out");
    while($booking = $bookings_result->fetch_assoc()) {
        $active_bookings[$booking['room_id']] = $booking;
    }

    // Get pending tasks
    $pending_tasks = [];
    $tasks_result = $conn->query("SELECT ht.room_id, u.username FROM housekeeping_tasks ht JOIN users u ON ht.assigned_to_user_id = u.id WHERE ht.status = 'pending' AND ht.task_date = CURDATE()");
    while($task = $tasks_result->fetch_assoc()) {
        $pending_tasks[$task['room_id']] = $task['username'];
    }

    // Calculate KPIs for the professional dashboard
    $total_rooms = count($all_rooms);
    $occupied_rooms = count($active_bookings);
    $clean_rooms = 0;
    $dirty_rooms = 0;
    $maintenance_rooms = 0;
    
    foreach($all_rooms as $room) {
        if ($room['status'] === 'maintenance' || $room['housekeeping_status'] === 'maintenance') {
            $maintenance_rooms++;
        } elseif (isset($active_bookings[$room['id']])) {
            // Room is occupied - don't count in clean/dirty stats
        } elseif ($room['housekeeping_status'] === 'clean') {
            $clean_rooms++;
        } else {
            $dirty_rooms++;
        }
    }
    
    $pending_task_count = count($pending_tasks);
    $occupancy_rate = $total_rooms > 0 ? ($occupied_rooms / $total_rooms) * 100 : 0;
    ?>

    <!-- KPI Statistics -->
    <div class="kpi-grid-professional" style="margin-bottom: 30px;">
        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $clean_rooms ?></div>
                <div class="kpi-label-pro">Clean & Ready</div>
                <div class="kpi-sub-pro">Available rooms</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $dirty_rooms ?></div>
                <div class="kpi-label-pro">Needs Cleaning</div>
                <div class="kpi-sub-pro">Requires attention</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $occupied_rooms ?></div>
                <div class="kpi-label-pro">Currently Occupied</div>
                <div class="kpi-sub-pro">Guest rooms</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $maintenance_rooms ?></div>
                <div class="kpi-label-pro">Maintenance</div>
                <div class="kpi-sub-pro">Out of order</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $pending_task_count ?></div>
                <div class="kpi-label-pro">Pending Tasks</div>
                <div class="kpi-sub-pro">Today's assignments</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= round($occupancy_rate, 1) ?>%</div>
                <div class="kpi-label-pro">Occupancy Rate</div>
                <div class="kpi-sub-pro"><?= $occupied_rooms ?>/<?= $total_rooms ?> rooms</div>
            </div>
            <div class="progress-bar-pro">
                <div class="progress-fill-pro" style="width: <?= $occupancy_rate ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Room Status Management -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0; color: #B6862C;">Room Status Overview</h3>
                <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                    Monitor and update the status of all rooms in real-time
                </p>
            </div>
            <div style="color: #8892a7; font-size: 0.85rem; font-weight: 500;">
                <?= number_format($total_rooms) ?> total room<?= $total_rooms !== 1 ? 's' : '' ?>
            </div>
        </div>

        <div class="filter-tabs" style="margin-bottom: 25px;">
            <div class="tab active" data-filter="all">All Rooms (<?= $total_rooms ?>)</div>
            <div class="tab" data-filter="occupied">Occupied (<?= $occupied_rooms ?>)</div>
            <div class="tab" data-filter="dirty">Needs Cleaning (<?= $dirty_rooms ?>)</div>
            <div class="tab" data-filter="clean">Clean & Ready (<?= $clean_rooms ?>)</div>
            <div class="tab" data-filter="maintenance">Maintenance (<?= $maintenance_rooms ?>)</div>
        </div>

        <div class="table-container" style="max-height: 600px; overflow-y: auto;">
            <table class="data-table" id="housekeeping-table">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Room</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Type</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Current Status</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Guest / Assignment</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; min-width: 180px;">Update Status</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($all_rooms as $room): ?>
                        <?php
                            $status_class = '';
                            $status_text = '';
                            $status_badge_style = '';
                            $occupant_info = 'Available';
                            $is_occupied = isset($active_bookings[$room['id']]);

                            if ($room['status'] === 'maintenance' || $room['housekeeping_status'] === 'maintenance') {
                                $status_class = 'maintenance';
                                $status_text = 'Maintenance Required';
                                $status_badge_style = 'background-color: #95a5a6; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;';
                            } elseif ($is_occupied) {
                                $status_class = 'occupied';
                                $status_text = 'Occupied';
                                $status_badge_style = 'background-color: #e74c3c; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;';
                                $occupant_info = htmlspecialchars($active_bookings[$room['id']]['full_name']);
                            } elseif ($room['housekeeping_status'] === 'clean') {
                                $status_class = 'clean';
                                $status_text = 'Clean & Ready';
                                $status_badge_style = 'background-color: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;';
                            } else {
                                $status_class = 'dirty';
                                $status_text = 'Needs Cleaning';
                                $status_badge_style = 'background-color: #f39c12; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;';
                                if (isset($pending_tasks[$room['id']])) {
                                    $occupant_info = 'Assigned to: ' . htmlspecialchars($pending_tasks[$room['id']]);
                                }
                            }
                        ?>
                        <tr data-status="<?= $status_class ?>">
                            <td>
                                <span style="font-weight: 600; color: #B6862C; font-size: 1rem;">
                                    <?= htmlspecialchars($room['room_number']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($room['room_type']) ?></td>
                            <td>
                                <span class="role-badge" style="<?= $status_badge_style ?>">
                                    <?= $status_text ?>
                                </span>
                            </td>
                            <td style="color: <?= $is_occupied ? '#fff' : '#8892a7' ?>;">
                                <?= $occupant_info ?>
                            </td>
                            <td>
                                <form method="POST" action="housekeeping.php" style="display: flex; align-items: center; gap: 10px;">
                                    <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                    <select name="new_status" class="form-select form-select-sm" style="min-width: 120px; font-size: 0.85rem;" <?= $is_occupied ? 'disabled' : '' ?>>
                                        <option value="clean" <?= $room['housekeeping_status'] === 'clean' ? 'selected' : '' ?>>Clean</option>
                                        <option value="dirty" <?= $room['housekeeping_status'] === 'dirty' ? 'selected' : '' ?>>Dirty</option>
                                        <option value="maintenance" <?= $room['housekeeping_status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                    </select>
                            </td>
                            <td>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm" <?= $is_occupied ? 'disabled' : '' ?>>
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: // --- Housekeeper View --- ?>
    <?php
    $tasks_stmt = $conn->prepare("SELECT ht.id as task_id, r.id as room_id, r.room_number, r.room_type FROM housekeeping_tasks ht JOIN rooms r ON ht.room_id = r.id WHERE ht.assigned_to_user_id = ? AND ht.status = 'pending' AND ht.task_date = CURDATE()");
    $tasks_stmt->bind_param("i", $user_id);
    $tasks_stmt->execute();
    $tasks_result = $tasks_stmt->get_result();
    $total_tasks = $tasks_result->num_rows;
    
    // Get completed tasks for today
    $completed_stmt = $conn->prepare("SELECT COUNT(*) as count FROM housekeeping_tasks WHERE assigned_to_user_id = ? AND status = 'completed' AND task_date = CURDATE()");
    $completed_stmt->bind_param("i", $user_id);
    $completed_stmt->execute();
    $completed_tasks = $completed_stmt->get_result()->fetch_assoc()['count'];
    $completed_stmt->close();
    
    $completion_rate = $total_tasks > 0 ? ($completed_tasks / ($total_tasks + $completed_tasks)) * 100 : 0;
    ?>

    <!-- Housekeeper KPIs -->
    <div class="kpi-grid-professional" style="margin-bottom: 30px;">
        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $total_tasks ?></div>
                <div class="kpi-label-pro">Pending Tasks</div>
                <div class="kpi-sub-pro">Today's assignments</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= $completed_tasks ?></div>
                <div class="kpi-label-pro">Completed Today</div>
                <div class="kpi-sub-pro">Finished tasks</div>
            </div>
        </div>

        <div class="kpi-card-pro">
            <div class="kpi-content-pro">
                <div class="kpi-value-pro"><?= round($completion_rate, 1) ?>%</div>
                <div class="kpi-label-pro">Completion Rate</div>
                <div class="kpi-sub-pro">Today's progress</div>
            </div>
            <div class="progress-bar-pro">
                <div class="progress-fill-pro" style="width: <?= $completion_rate ?>%"></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0; color: #B6862C;">My Cleaning Assignments</h3>
                <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                    Complete your assigned rooms for <?= date('l, F j, Y') ?>
                </p>
            </div>
            <?php if ($total_tasks > 0): ?>
                <div style="color: #8892a7; font-size: 0.85rem; font-weight: 500;">
                    <?= $total_tasks ?> task<?= $total_tasks !== 1 ? 's' : '' ?> remaining
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Priority</th>
                        <th style="width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_tasks > 0): ?>
                        <?php 
                        $tasks_result->data_seek(0); // Reset pointer
                        while($task = $tasks_result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: #B6862C; font-size: 1rem;">
                                        <?= htmlspecialchars($task['room_number']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($task['room_type']) ?></td>
                                <td>
                                    <span class="role-badge" style="background-color: #f39c12; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                        NORMAL
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="housekeeping.php" style="display: inline;">
                                        <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                        <input type="hidden" name="room_id" value="<?= $task['room_id'] ?>">
                                        <button type="submit" name="complete_task" class="btn btn-primary btn-sm">
                                            Mark Complete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 60px 20px; color: #8892a7;">
                                <div style="font-size: 3rem; margin-bottom: 15px;">✅</div>
                                <div style="font-size: 1.1rem; margin-bottom: 8px;">All tasks completed!</div>
                                <div style="font-size: 0.9rem;">Great work! You have no pending cleaning assignments for today.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter tabs functionality for admin/manager view
    const tabs = document.querySelectorAll('.filter-tabs .tab');
    if (tabs.length > 0) {
        const rows = document.querySelectorAll('#housekeeping-table tbody tr');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                
                // Show/hide rows based on filter
                rows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }

    // Auto-refresh functionality (every 30 seconds for live updates)
    setInterval(function() {
        // Only refresh if user is not actively interacting with forms
        const activeElement = document.activeElement;
        if (!activeElement || (activeElement.tagName !== 'SELECT' && activeElement.tagName !== 'BUTTON')) {
            // Subtle refresh without full page reload
            location.reload();
        }
    }, 30000);
});
</script>