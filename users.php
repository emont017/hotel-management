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


$sql = "SELECT id, username, role FROM users ORDER BY role DESC, username ASC";
$result = $conn->query($sql);
?>

<h2>Registered Users</h2>

<?php if ($result && $result->num_rows > 0): ?>
<table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr style="background-color: 
            <th style="padding: 10px; border-bottom: 1px solid 
            <th style="padding: 10px; border-bottom: 1px solid 
            <th style="padding: 10px; border-bottom: 1px solid 
            <th style="padding: 10px; border-bottom: 1px solid 
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid 
                <td style="padding: 10px; border-bottom: 1px solid 
                <td style="padding: 10px; border-bottom: 1px solid 
                <td style="padding: 10px; border-bottom: 1px solid 
                    <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <?php if ($row['role'] === 'guest'): ?>
                                <button type="submit" name="action" value="promote">Promote</button>
                            <?php elseif ($row['role'] === 'admin'): ?>
                                <button type="submit" name="action" value="demote">Demote</button>
                            <?php endif; ?>
                            <button type="submit" name="action" value="delete" onclick="return confirm('Are you sure you want to delete this user?');">Delete</button>
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
    <p>No users found in the system.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
