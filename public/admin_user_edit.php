<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

// Ensure a valid user ID is provided in the URL
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: /users.php");
    exit;
}

$user_id_to_edit = intval($_GET['user_id']);
$success_message = '';
$error_message = '';

// Handle form submission when the admin saves changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // Base query for updating user details
    $sql = "UPDATE users SET username = ?, role = ?, full_name = ?, email = ?, phone = ?";
    $params = [$username, $role, $full_name, $email, $phone];
    $types = "sssss";

    // If a new password was entered, add it to the query
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    // Add the WHERE clause to update the correct user
    $sql .= " WHERE id = ?";
    $params[] = $user_id_to_edit;
    $types .= "i";

    $stmt_update = $conn->prepare($sql);
    $stmt_update->bind_param($types, ...$params);
    
    if ($stmt_update->execute()) {
        $success_message = "User updated successfully!";
    } else {
        $error_message = "Error updating user: " . $stmt_update->error;
    }
    $stmt_update->close();
}

// Fetch the user's current data to pre-populate the form
$stmt_fetch = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt_fetch->bind_param("i", $user_id_to_edit);
$stmt_fetch->execute();
$user_result = $stmt_fetch->get_result();
$user = $user_result->fetch_assoc();
$stmt_fetch->close();

// If user doesn't exist, redirect back to the user list
if (!$user) {
    header("Location: /users.php");
    exit;
}

$title = "Edit User: " . htmlspecialchars($user['username']);
require_once __DIR__ . '/../includes/header.php';
?>

<a href="users.php" class="btn btn-secondary mb-20">← Back to All Users</a>
<h2>✏️ Edit User: <?= htmlspecialchars($user['username']) ?></h2>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?= $success_message ?></div>
<?php endif; ?>
<?php if ($error_message): ?>
    <div class="alert alert-danger"><?= $error_message ?></div>
<?php endif; ?>

<div class="form-container">
    <form method="post" action="admin_user_edit.php?user_id=<?= $user_id_to_edit ?>">
        <label class="form-label">Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required class="form-input">
        
        <label class="form-label">Full Name:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="form-input">

        <label class="form-label">Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-input">

        <label class="form-label">Phone:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="form-input">

        <label class="form-label">Role:</label>
        <select name="role" required class="form-select">
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
            <option value="front_desk" <?= $user['role'] === 'front_desk' ? 'selected' : '' ?>>Front Desk</option>
            <option value="housekeeping" <?= $user['role'] === 'housekeeping' ? 'selected' : '' ?>>Housekeeping</option>
            <option value="accountant" <?= $user['role'] === 'accountant' ? 'selected' : '' ?>>Accountant</option>
            <option value="guest" <?= $user['role'] === 'guest' ? 'selected' : '' ?>>Guest</option>
        </select>

        <label class="form-label">New Password:</label>
        <input type="password" name="password" placeholder="Leave blank to keep current password" class="form-input">

        <div style="display:flex; gap: 15px; margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>