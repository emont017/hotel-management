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

$rates = $conn->query("SELECT * FROM room_rates ORDER BY room_type, date_start DESC");
$room_types_result = $conn->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<h2>Rate Management</h2>
<p>Create, view, and edit pricing rules for different room types and date ranges.</p>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<div class="dashboard-columns mt-30">
    <div class="main-column">
        <div class="card">
            <h3 id="form-title">Add New Rate Rule</h3>
            <form id="rate-form" method="POST" action="admin_rates.php">
                <input type="hidden" name="action" id="form-action" value="create">
                <input type="hidden" name="rate_id" id="form-rate-id" value="">

                <label class="form-label" for="rate_name">Rate Name:</label>
                <input type="text" id="rate_name" name="rate_name" class="form-input" placeholder="e.g., Standard Rate, Weekend Special" required>

                <label class="form-label" for="room_type">Room Type:</label>
                <select id="room_type" name="room_type" class="form-select" required>
                    <option value="" disabled selected>-- Select a Room Type --</option>
                    <?php while ($type = $room_types_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($type['room_type']) ?>"><?= htmlspecialchars($type['room_type']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label class="form-label" for="price">Price per Night ($):</label>
                <input type="number" id="price" name="price" class="form-input" step="0.01" min="0" placeholder="e.g., 150.50" required>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="date_start">Start Date:</label>
                        <input type="date" id="date_start" name="date_start" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="date_end">End Date:</label>
                        <input type="date" id="date_end" name="date_end" class="form-input" required>
                    </div>
                </div>

                <div class="form-actions mt-30">
                    <button type="submit" id="submit-button" class="btn btn-primary">Add Rate Rule</button>
                    <button type="button" id="cancel-edit" class="btn btn-secondary" style="display: none;">Cancel Edit</button>
                </div>
            </form>
        </div>
    </div>

    <div class="side-column">
        <div class="card">
            <h3>Existing Rate Rules</h3>
            <div style="max-height: 600px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Room Type</th>
                            <th>Rate Name</th>
                            <th>Price</th>
                            <th>Effective Dates</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rates->num_rows > 0): ?>
                            <?php while ($rate = $rates->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($rate['room_type']) ?></td>
                                    <td><?= htmlspecialchars($rate['rate_name']) ?></td>
                                    <td>$<?= number_format($rate['price'], 2) ?></td>
                                    <td><?= htmlspecialchars($rate['date_start']) ?> to <?= htmlspecialchars($rate['date_end']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-link-style edit-btn"
                                                data-id="<?= $rate['id'] ?>"
                                                data-name="<?= htmlspecialchars($rate['rate_name']) ?>"
                                                data-type="<?= htmlspecialchars($rate['room_type']) ?>"
                                                data-price="<?= $rate['price'] ?>"
                                                data-start="<?= $rate['date_start'] ?>"
                                                data-end="<?= $rate['date_end'] ?>">
                                                Edit
                                            </button>
                                            <form method="POST" action="admin_rates.php" onsubmit="return confirm('Are you sure you want to delete this rate? This cannot be undone.');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="rate_id" value="<?= $rate['id'] ?>">
                                                <button type="submit" class="btn-link-style-danger">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No rate rules found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
        submitButton.classList.replace('btn-secondary', 'btn-primary');
        cancelEditButton.style.display = 'none';
        document.getElementById('rate_name').focus();
    }

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
            submitButton.classList.replace('btn-primary', 'btn-secondary');
            cancelEditButton.style.display = 'inline-block';
            
            form.scrollIntoView({ behavior: 'smooth' });
        });
    });

    cancelEditButton.addEventListener('click', resetForm);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>