<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$title = "Manage Users";
require_once 'includes/header.php';
require_once 'php/db.php';

// Handle promote, demote, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $target_id = intval($_POST['user_id']);

    if ($target_id !== $_SESSION['user_id']) {
        if ($action === 'promote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        } elseif ($action === 'demote') {
            $stmt = $conn->prepare("UPDATE users SET role = 'guest' WHERE id = ?");
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        }

        if (isset($stmt)) {
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Fetch all users
$sql = "SELECT id, username, role FROM users ORDER BY role DESC, username ASC";
$result = $conn->query($sql);
?>

<h2 style="text-align:center; margin-top: 30px;">Registered Users</h2>

<?php if ($result && $result->num_rows > 0): ?>
    <table style="width: 100%; max-width: 900px; margin: 30px auto; border-collapse: collapse; background-color: rgba(255,255,255,0.05); border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.2);">
        <thead>
            <tr style="background-color: #F7B223; color: #081C3A;">
                <th style="padding: 12px;">User ID</th>
                <th style="padding: 12px;">Username</th>
                <th style="padding: 12px;">Role</th>
                <th style="padding: 12px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="text-align: center; border-top: 1px solid #ccc;">
                    <td style="padding: 12px;"><?= htmlspecialchars($row['id']) ?></td>
                    <td style="padding: 12px;"><?= htmlspecialchars($row['username']) ?></td>
                    <td style="padding: 12px; text-transform: capitalize;"><?= htmlspecialchars($row['role']) ?></td>
                    <td style="padding: 12px;">
                        <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <?php if ($row['role'] === 'guest'): ?>
                                    <button type="submit" name="action" value="promote" style="margin-right: 5px; padding: 6px 12px; border-radius: 6px; background-color: #4CAF50; color: white; border: none;">Promote</button>
                                <?php elseif ($row['role'] === 'admin'): ?>
                                    <button type="submit" name="action" value="demote" style="margin-right: 5px; padding: 6px 12px; border-radius: 6px; background-color: #FF9800; color: white; border: none;">Demote</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this user?');" style="padding: 6px 12px; border-radius: 6px; background-color: #f44336; color: white; border: none;">Delete</button>
                            </form>
                        <?php else: ?>
                            <em>(You)</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="text-align:center;">No users found in the system.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
