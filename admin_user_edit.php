<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'php/db.php';

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: admin_bookings.php");
    exit;
}

$user_id = intval($_GET['user_id']);
$error = '';
$success = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($full_name) || empty($email)) {
        $error = "Full Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
        if ($stmt->execute()) {
            $success = "User info updated successfully.";
        } else {
            $error = "Error updating user info: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get user info
$stmt = $conn->prepare("SELECT username, full_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $full_name, $email, $phone);
$stmt->fetch();
$stmt->close();

$title = "Edit User: $username";
require_once 'includes/header.php';
?>

<a href="admin_booking_detail.php?booking_id=<?= $_GET['booking_id'] ?? '' ?>" style="
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #F7B223;
    color: #081C3A;
    text-decoration: none;
    font-weight: bold;
    border-radius: 6px;
">
    ← Back to Booking Details
</a>

<h2 style="color: #F7B223;">✏️ Edit Guest Info for <?= htmlspecialchars($username) ?></h2>

<?php if ($error): ?>
    <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>
<?php if ($success): ?>
    <p style="color: green; font-weight: bold;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>

<form method="POST" action="admin_user_edit.php?user_id=<?= $user_id ?>&booking_id=<?= $_GET['booking_id'] ?? '' ?>" style="
    max-width: 600px;
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
">
    <label>Full Name:</label><br>
    <input type="text" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required style="
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
    ">

    <label>Email:</label><br>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required style="
        width: 100%;
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
    ">

    <label>Phone:</label><br>
    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" style="
        width: 100%;
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 6px;
    ">

    <button type="submit" style="
        background-color: #081C3A;
        color: white;
        padding: 10px 20px;
        font-weight: bold;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    ">💾 Save Changes</button>
</form>

<?php require_once 'includes/footer.php'; ?>
