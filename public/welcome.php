<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$title = "Welcome";
require_once __DIR__ . '/../includes/header.php';

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$role = $_SESSION['role'] ?? 'guest';

// --- Dynamic Content based on User Role ---
$welcome_message = '';
$action_buttons = '';

switch ($role) {
    case 'admin':
    case 'manager':
        $welcome_message = "You have full access to all management and administrative tools. Use the navigation bar to get a complete overview of hotel operations.";
        $action_buttons = '<a href="admin_dashboard.php" class="btn btn-primary">Go to Dashboard</a> <a href="users.php" class="btn btn-secondary">Manage Staff</a>';
        break;
    
    case 'accountant':
        $welcome_message = "You can access all financial data. Please proceed to the Accounting section to manage payments and generate reports.";
        $action_buttons = '<a href="payments.php" class="btn btn-primary">Manage Payments</a> <a href="reports.php" class="btn btn-secondary">View Reports</a>';
        break;

    case 'housekeeping':
        $welcome_message = "Here you can view your daily room assignments and update room statuses. Let's get started!";
        $action_buttons = '<a href="housekeeping_tasks.php" class="btn btn-primary">View My Tasks</a> <a href="housekeeping.php" class="btn btn-secondary">Master Room List</a>';
        break;

    case 'guest':
        $welcome_message = "You can manage your reservations or book a new stay with us. We look forward to welcoming you!";
        $action_buttons = '<a href="manage_reservations.php" class="btn btn-primary">My Reservations</a> <a href="bookings.php" class="btn btn-secondary">Book a New Stay</a>';
        break;

    default:
        $welcome_message = "Use the navigation bar above to explore our hotel and services.";
        break;
}
?>

<div class="card text-center" style="max-width: 800px; margin: 60px auto;">
    <h2>🎉 Welcome, <?= $username ?>!</h2>
    <p style="font-size: 1.2em;">
        You are logged in as a <strong><?= ucfirst($role) ?></strong>.
        <?= $welcome_message ?>
    </p>

    <div class="mt-30">
        <?= $action_buttons ?>
    </div>
    
    <!-- Corrected Logout Link -->
    <a href="/api/logout.php" class="btn btn-danger mt-30">Logout</a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
