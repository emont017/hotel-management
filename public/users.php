<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
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
        $stmt->execute();
        $stmt->close();
    }
    header("Location: users.php");
    exit;
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id_to_delete = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id_to_delete);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit;
}

$title = "Manage Users";
require_once __DIR__ . '/../includes/header.php';
$users = $conn->query("SELECT * FROM users ORDER BY role, username");
?>

<h2>👥 Manage Users</h2>

<div class="form-container">
    <h3>➕ Create New User</h3>
    <form method="post" action="users.php">
        <label class="form-label">Username:</label>
        <input type="text" name="username" placeholder="Username" required class="form-input">
        
        <label class="form-label">Password:</label>
        <input type="password" name="password" placeholder="Password" required class="form-input">
        
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

        <label class="form-label">Full Name:</label>
        <input type="text" name="full_name" placeholder="Full Name" class="form-input">
        
        <label class="form-label">Email:</label>
        <input type="email" name="email" placeholder="Email" class="form-input">

        <label class="form-label">Phone:</label>
        <input type="text" name="phone" placeholder="Phone" class="form-input">

        <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
    </form>
</div>

<h3 class="mt-30">📋 All Users</h3>
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
            <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username'] ?? '') ?></td>
                <td class="text-capitalize"><?= htmlspecialchars($row['role'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                <td>
                    <a href="admin_user_edit.php?user_id=<?= $row['id'] ?>" class="btn-link-style">Edit</a> | 
                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.')" class="btn-link-style" style="color: #e74c3c;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>