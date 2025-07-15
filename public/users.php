<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// --- Security Check: Restrict access to authorized roles ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

// --- CSRF Token Generation & Validation ---
// Generate a token if one doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$feedback_message = '';
$feedback_type = '';

// --- Handle POST Actions (Create User) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die('CSRF token validation failed.');
    }

    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if(!empty($username) && !empty($password) && !empty($role)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashed_password, $role, $full_name, $email, $phone);
            if ($stmt->execute()) {
                $feedback_message = "User '{$username}' created successfully!";
                $feedback_type = 'success';
            } else {
                $feedback_message = "Error: Could not create user. The username or email might already exist.";
                $feedback_type = 'danger';
            }
            $stmt->close();
        } else {
            $feedback_message = "Username, password, and role are required.";
            $feedback_type = 'danger';
        }
    }
}

// --- Handle GET Actions (Delete/Deactivate User) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user_id'])) {
    // Verify CSRF token from URL
    if (!isset($_GET['token']) || !hash_equals($csrf_token, $_GET['token'])) {
        die('CSRF token validation failed.');
    }

    $user_id_to_delete = (int)$_GET['user_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    // Prevent a user from deleting themselves
    if ($user_id_to_delete === $current_user_id) {
        $feedback_message = "Error: You cannot delete your own account.";
        $feedback_type = 'danger';
    } else {
        // Soft delete the user by setting is_active to 0
        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $feedback_message = "User deactivated successfully.";
            $feedback_type = 'success';
        } else {
            $feedback_message = "Error: Could not deactivate user.";
            $feedback_type = 'danger';
        }
        $stmt->close();
    }
}


// --- Data Fetching with Filtering ---
$filter_role = $_GET['filter_role'] ?? 'all';
$sql = "SELECT id, username, role, full_name, email, phone FROM users WHERE is_active = 1";
$params = [];
$types = '';

if ($filter_role !== 'all') {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
    $types .= 's';
}
$sql .= " ORDER BY role, username";

$stmt_users = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();


$title = "Staff Management";
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Staff Management</h2>
<p>Create new staff accounts, edit permissions, and manage user access.</p>

<?php if ($feedback_message): ?>
<div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>">
    <?= htmlspecialchars($feedback_message) ?>
</div>
<?php endif; ?>


<!-- User Creation Form -->
<div class="form-container">
    <h3>Create New User</h3>
    <form method="post" action="users.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <label class="form-label">Username:</label>
                <input type="text" name="username" placeholder="Required" required class="form-input">
            </div>
            <div>
                <label class="form-label">Password:</label>
                <input type="password" name="password" placeholder="Required" required class="form-input">
            </div>
             <div>
                <label class="form-label">Role:</label>
                <select name="role" required class="form-select">
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="front_desk">Front Desk</option>
                    <option value="housekeeping">Housekeeping</option>
                    <option value="accountant">Accountant</option>
                    <option value="guest">Guest</option>
                </select>
            </div>
            <div>
                <label class="form-label">Full Name:</label>
                <input type="text" name="full_name" class="form-input">
            </div>
            <div>
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-input">
            </div>
            <div>
                <label class="form-label">Phone:</label>
                <input type="tel" name="phone" class="form-input">
            </div>
        </div>
        <button type="submit" name="create_user" class="btn btn-primary mt-30">Create User</button>
    </form>
</div>


<!-- User List & Filtering -->
<h3 class="mt-30">Active Users</h3>
<div class="card">
    <form method="GET" action="users.php" class="mb-20">
        <label for="filter_role" class="form-label">Filter by Role:</label>
        <select name="filter_role" id="filter_role" class="form-select" onchange="this.form.submit()" style="width: 200px; display: inline-block;">
            <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>All Roles</option>
            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="manager" <?= $filter_role === 'manager' ? 'selected' : '' ?>>Manager</option>
            <option value="front_desk" <?= $filter_role === 'front_desk' ? 'selected' : '' ?>>Front Desk</option>
            <option value="housekeeping" <?= $filter_role === 'housekeeping' ? 'selected' : '' ?>>Housekeeping</option>
            <option value="accountant" <?= $filter_role === 'accountant' ? 'selected' : '' ?>>Accountant</option>
            <option value="guest" <?= $filter_role === 'guest' ? 'selected' : '' ?>>Guest</option>
        </select>
    </form>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td class="text-capitalize"><?= htmlspecialchars($row['role']) ?></td>
                        <td><?= htmlspecialchars($row['full_name'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['email'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['phone'] ?: 'N/A') ?></td>
                        <td>
                            <a href="admin_user_edit.php?user_id=<?= $row['id'] ?>" class="btn-link-style">Edit</a> |
                            <a href="?action=delete&user_id=<?= $row['id'] ?>&token=<?= $csrf_token ?>"
                               onclick="return confirm('Are you sure you want to deactivate this user? They will no longer be able to log in.')"
                               class="btn-link-style" style="color: #e74c3c;">Deactivate</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No active users found matching the criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt_users->close();
require_once __DIR__ . '/../includes/footer.php';
?>
