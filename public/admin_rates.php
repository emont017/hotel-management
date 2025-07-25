<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$title = "Rate Management";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && isset($_POST['rate_id'])) {
        $rate_id = (int)$_POST['rate_id'];
        $stmt = $conn->prepare("DELETE FROM room_rates WHERE id = ?");
        $stmt->bind_param("i", $rate_id);
        if ($stmt->execute()) {
            $message = "Rate rule successfully deleted.";
            $message_type = 'success';
        } else {
            $message = "Error: Could not delete the rate rule.";
            $message_type = 'danger';
        }
        $stmt->close();
    }

    if ($action === 'create' || $action === 'update') {
        $rate_name = trim($_POST['rate_name']);
        $room_type = trim($_POST['room_type']);
        $price = (float)$_POST['price'];
        $date_start = $_POST['date_start'];
        $date_end = $_POST['date_end'];
        $rate_id = isset($_POST['rate_id']) ? (int)$_POST['rate_id'] : null;

        if (empty($rate_name) || empty($room_type) || $price <= 0 || empty($date_start) || empty($date_end)) {
            $message = "All fields are required and price must be positive.";
            $message_type = 'danger';
        } elseif ($date_end < $date_start) {
            $message = "End date cannot be before the start date.";
            $message_type = 'danger';
        } else {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO room_rates (rate_name, room_type, price, date_start, date_end) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdds", $rate_name, $room_type, $price, $date_start, $date_end);
                if ($stmt->execute()) {
                    $message = "New rate rule created successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error creating rate: " . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            } elseif ($action === 'update' && $rate_id) {
                $stmt = $conn->prepare("UPDATE room_rates SET rate_name = ?, room_type = ?, price = ?, date_start = ?, date_end = ? WHERE id = ?");
                $stmt->bind_param("ssddsi", $rate_name, $room_type, $price, $date_start, $date_end, $rate_id);
                if ($stmt->execute()) {
                    $message = "Rate rule updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating rate: " . $stmt->error;
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    }
}

// Get rate statistics for KPI cards
$rates = $conn->query("SELECT * FROM room_rates ORDER BY room_type, date_start DESC");
$total_rates = $rates->num_rows;

$active_rates_query = $conn->query("SELECT COUNT(*) as count FROM room_rates WHERE CURDATE() BETWEEN date_start AND date_end");
$active_rates = $active_rates_query->fetch_assoc()['count'] ?? 0;

$avg_price_query = $conn->query("SELECT AVG(price) as avg_price FROM room_rates WHERE CURDATE() BETWEEN date_start AND date_end");
$avg_price = $avg_price_query->fetch_assoc()['avg_price'] ?? 0;

$room_types_covered_query = $conn->query("SELECT COUNT(DISTINCT room_type) as count FROM room_rates WHERE CURDATE() BETWEEN date_start AND date_end");
$room_types_covered = $room_types_covered_query->fetch_assoc()['count'] ?? 0;

$room_types_result = $conn->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>Rate Management</h1>
        <p>Create, view, and edit pricing rules for different room types and date ranges to optimize revenue and manage seasonal pricing.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 30px;">
        <span class="alert-icon"><?= $message_type === 'success' ? '✓' : '⚠️' ?></span>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- KPI Statistics -->
<div class="kpi-grid-professional" style="margin-bottom: 30px;">
    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= number_format($total_rates) ?></div>
            <div class="kpi-label-pro">Total Rate Rules</div>
            <div class="kpi-sub-pro">All configured rates</div>
        </div>
    </div>

    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= number_format($active_rates) ?></div>
            <div class="kpi-label-pro">Active Rates</div>
            <div class="kpi-sub-pro">Currently effective</div>
        </div>
    </div>

    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro">$<?= number_format($avg_price, 0) ?></div>
            <div class="kpi-label-pro">Average Rate</div>
            <div class="kpi-sub-pro">Active rates only</div>
        </div>
    </div>

    <div class="kpi-card-pro">
        <div class="kpi-content-pro">
            <div class="kpi-value-pro"><?= number_format($room_types_covered) ?></div>
            <div class="kpi-label-pro">Room Types</div>
            <div class="kpi-sub-pro">With active rates</div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="detail-grid">
    <!-- Rate Form -->
    <div class="card" id="rate-form-card">
        <h3 id="form-title">Add New Rate Rule</h3>
        <p style="color: #8892a7; margin-bottom: 25px;">Configure pricing rules for specific room types and date ranges to manage revenue optimization.</p>
        
        <form id="rate-form" method="POST" action="admin_rates.php">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="rate_id" id="form-rate-id" value="">

            <label class="form-label" for="rate_name">Rate Name:</label>
            <input type="text" id="rate_name" name="rate_name" class="form-input" placeholder="e.g., Standard Rate, Weekend Special, Holiday Premium" required>

            <label class="form-label" for="room_type">Room Type:</label>
            <select id="room_type" name="room_type" class="form-select" required>
                <option value="" disabled selected>-- Select a Room Type --</option>
                <?php 
                $room_types_result->data_seek(0); // Reset pointer
                while ($type = $room_types_result->fetch_assoc()): 
                ?>
                    <option value="<?= htmlspecialchars($type['room_type']) ?>"><?= htmlspecialchars($type['room_type']) ?></option>
                <?php endwhile; ?>
            </select>

            <label class="form-label" for="price">Price per Night ($):</label>
            <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" placeholder="e.g., 150.50" required>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label class="form-label" for="date_start">Start Date:</label>
                    <input type="date" id="date_start" name="date_start" class="form-input" required>
                </div>
                <div>
                    <label class="form-label" for="date_end">End Date:</label>
                    <input type="date" id="date_end" name="date_end" class="form-input" required>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 30px; display: flex; gap: 15px;">
                <button type="submit" id="submit-button" class="btn btn-primary" style="flex: 1;">Add Rate Rule</button>
                <button type="button" id="cancel-edit" class="btn btn-secondary" style="display: none; flex: 1;">Cancel Edit</button>
            </div>
        </form>
    </div>

    <!-- Rates Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0; color: #B6862C;">Current Rate Rules</h3>
                <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                    Manage all pricing rules and their effective date ranges
                </p>
            </div>
            <?php if ($total_rates > 0): ?>
                <div style="color: #B6862C; font-size: 0.9rem; font-weight: 600;">
                    <?= number_format($total_rates) ?> rate<?= $total_rates !== 1 ? 's' : '' ?> configured
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container" style="max-height: 600px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Room Type</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Rate Name</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Price</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Effective Period</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; width: 120px;">Status</th>
                        <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_rates > 0): ?>
                        <?php 
                        $rates->data_seek(0); // Reset pointer
                        while ($rate = $rates->fetch_assoc()): 
                            $is_active = (date('Y-m-d') >= $rate['date_start'] && date('Y-m-d') <= $rate['date_end']);
                        ?>
                            <tr>
                                <td>
                                    <span style="font-weight: 600; color: #B6862C;"><?= htmlspecialchars($rate['room_type']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($rate['rate_name']) ?></td>
                                <td>
                                    <span style="font-family: monospace; font-size: 1.1rem; font-weight: 600; color: #2ecc71;">
                                        $<?= number_format($rate['price'], 2) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <?= date('M j, Y', strtotime($rate['date_start'])) ?><br>
                                    <span style="color: #8892a7;">to <?= date('M j, Y', strtotime($rate['date_end'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($is_active): ?>
                                        <span class="role-badge" style="background-color: #2ecc71; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">ACTIVE</span>
                                    <?php elseif (date('Y-m-d') < $rate['date_start']): ?>
                                        <span class="role-badge" style="background-color: #f39c12; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">FUTURE</span>
                                    <?php else: ?>
                                        <span class="role-badge" style="background-color: #95a5a6; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">EXPIRED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <button class="btn-link-style edit-btn" style="font-size: 0.85rem; padding: 4px 8px; cursor: pointer;"
                                            data-id="<?= $rate['id'] ?>"
                                            data-name="<?= htmlspecialchars($rate['rate_name']) ?>"
                                            data-type="<?= htmlspecialchars($rate['room_type']) ?>"
                                            data-price="<?= $rate['price'] ?>"
                                            data-start="<?= $rate['date_start'] ?>"
                                            data-end="<?= $rate['date_end'] ?>">
                                            Edit
                                        </button>
                                        <form method="POST" action="admin_rates.php" onsubmit="return confirm('Are you sure you want to delete this rate rule? This action cannot be undone.');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="rate_id" value="<?= $rate['id'] ?>">
                                            <button type="submit" class="btn-link-style" style="color: #e74c3c; font-size: 0.85rem; padding: 4px 8px; cursor: pointer;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 60px 20px; color: #8892a7;">
                                <div style="font-size: 3rem; margin-bottom: 15px;">💰</div>
                                <div style="font-size: 1.1rem; margin-bottom: 8px;">No rate rules configured yet</div>
                                <div style="font-size: 0.9rem;">Create your first rate rule to start managing pricing for different room types and seasons.</div>
                                <button onclick="scrollToForm()" class="btn btn-primary" style="margin-top: 20px;">
                                    Add Your First Rate Rule
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rate-form');
    const formTitle = document.getElementById('form-title');
    const actionInput = document.getElementById('form-action');
    const rateIdInput = document.getElementById('form-rate-id');
    const submitButton = document.getElementById('submit-button');
    const cancelEditButton = document.getElementById('cancel-edit');

    function resetForm() {
        form.reset();
        formTitle.textContent = 'Add New Rate Rule';
        actionInput.value = 'create';
        rateIdInput.value = '';
        submitButton.textContent = 'Add Rate Rule';
        submitButton.classList.remove('btn-secondary');
        submitButton.classList.add('btn-primary');
        cancelEditButton.style.display = 'none';
        document.getElementById('rate_name').focus();
    }

    function scrollToForm() {
        document.getElementById('rate-form-card').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
        document.getElementById('rate_name').focus();
    }

    // Make scrollToForm globally available
    window.scrollToForm = scrollToForm;

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const type = this.dataset.type;
            const price = this.dataset.price;
            const start = this.dataset.start;
            const end = this.dataset.end;

            formTitle.textContent = 'Edit Rate Rule';
            actionInput.value = 'update';
            rateIdInput.value = id;
            document.getElementById('rate_name').value = name;
            document.getElementById('room_type').value = type;
            document.getElementById('price').value = price;
            document.getElementById('date_start').value = start;
            document.getElementById('date_end').value = end;

            submitButton.textContent = 'Update Rate Rule';
            submitButton.classList.remove('btn-primary');
            submitButton.classList.add('btn-secondary');
            cancelEditButton.style.display = 'block';
            
            scrollToForm();
        });
    });

    cancelEditButton.addEventListener('click', resetForm);

    // Set minimum date to today for new rates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date_start').min = today;
    document.getElementById('date_end').min = today;

    // Update end date minimum when start date changes
    document.getElementById('date_start').addEventListener('change', function() {
        document.getElementById('date_end').min = this.value;
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>