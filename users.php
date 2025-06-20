<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: index.php");
    exit;
}

$title = "Manage Users";
require_once 'includes/header.php';
require_once 'php/db.php';

// Handle Create User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $full_name = !empty($_POST['full_name']) ? $_POST['full_name'] : null;
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : null;

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $password, $role, $full_name, $email, $phone);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit;
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: users.php");
    exit;
}

$users = $conn->query("SELECT * FROM users");
?>

<h2 style="color: #F7B223;">👥 Manage Users</h2>

<!-- Create User Form -->
<form method="post" style="margin-top: 30px; max-width: 600px;">
    <h3 style="color: #fff;">➕ Create New User</h3>
    <input type="text" name="username" placeholder="Username" required style="width: 100%; padding: 10px; margin: 8px 0;"><br>
    <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 10px; margin: 8px 0;"><br>
    
    <select name="role" required style="width: 100%; padding: 10px; margin: 8px 0;">
        <option value="">Select Role</option>
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="front_desk">Front Desk</option>
        <option value="housekeeping">Housekeeping</option>
        <option value="accountant">Accountant</option>
        <option value="staff">Staff</option>
        <option value="guest">Guest</option>
    </select><br>

    <input type="text" name="full_name" placeholder="Full Name" style="width: 100%; padding: 10px; margin: 8px 0;"><br>
    <input type="email" name="email" placeholder="Email" style="width: 100%; padding: 10px; margin: 8px 0;"><br>
    <input type="text" name="phone" placeholder="Phone" style="width: 100%; padding: 10px; margin: 8px 0;"><br>

    <input type="submit" name="create_user" value="Create User" style="padding: 10px 20px; background-color: #F7B223; border: none; color: #081C3A; font-weight: bold; cursor: pointer;">
</form>

<!-- Users Table -->
<h3 style="margin-top: 50px; color: #F7B223;">📋 All Users</h3>
<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
    <thead>
        <tr style="background-color: #081E3F; color: white;">
            <th style="padding: 12px; border: 1px solid #ddd;">ID</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Username</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Role</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Full Name</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Email</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Phone</th>
            <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $i = 0; while ($row = $users->fetch_assoc()): ?>
        <?php $bg = ($i++ % 2 === 0) ? "#f8f9fa" : "#ffffff"; ?>
        <tr style="background-color: <?= $bg ?>; color: #081E3F;" onmouseover="this.style.backgroundColor='#e9ecef'" onmouseout="this.style.backgroundColor='<?= $bg ?>'">
            <td style="padding: 10px; border: 1px solid #ddd;"><?= $row['id'] ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['username'] ?? '') ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['role'] ?? '') ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['email'] ?? '') ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;"><?= htmlspecialchars($row['phone'] ?? '') ?></td>
            <td style="padding: 10px; border: 1px solid #ddd;">
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')" style="color: red;">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'includes/footer.php'; ?>
