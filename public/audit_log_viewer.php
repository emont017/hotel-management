<?php
session_start();
require_once __DIR__ . '/../config/db.php';
$title = "Audit Logs";
require_once __DIR__ . '/../includes/header.php';

// Security Check: Only allow admins to view this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Fetch all audit logs using the correct table and column names
$logs = [];
$query = "
    SELECT l.id, l.timestamp, l.action, l.details, u.full_name as ran_by
    FROM audit_logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.timestamp DESC
";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

?>

<div class="container">
    <h2>System Audit Log History</h2>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Date & Time</th>
                    <th>Performed By</th>
                    <th>Action Taken</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No audit logs found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['id']) ?></td>
                            <td><?= date("F j, Y, g:i A", strtotime($log['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($log['ran_by']) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>