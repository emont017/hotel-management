<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';
$title = "Audit Logs";
require_once __DIR__ . '/../includes/header.php';

// Security Check: Only allow admins to view this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /index.php");
    exit;
}

// Handle filters and pagination
$filters = [];
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Build filters from GET parameters
if (!empty($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $filters['user_id'] = intval($_GET['user_id']);
}

if (!empty($_GET['action'])) {
    $filters['action'] = trim($_GET['action']);
}

if (!empty($_GET['target_table'])) {
    $filters['target_table'] = trim($_GET['target_table']);
}

if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Handle clear logs action
$feedback_message = '';
$feedback_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    if (isset($_POST['confirm_clear']) && $_POST['confirm_clear'] === 'yes') {
        try {
            $result = $conn->query("DELETE FROM audit_logs");
            if ($result) {
                $feedback_message = "All audit logs have been cleared successfully.";
                $feedback_type = 'success';
                
                // Log this action (after clearing, so it's the first entry)
                log_system_event($conn, $_SESSION['user_id'], 'Audit Logs Cleared', "All audit logs cleared by " . $_SESSION['username']);
            } else {
                $feedback_message = "Error clearing audit logs.";
                $feedback_type = 'danger';
            }
        } catch (Exception $e) {
            $feedback_message = "Error: " . $e->getMessage();
            $feedback_type = 'danger';
        }
    } else {
        $feedback_message = "Please confirm that you want to clear all logs.";
        $feedback_type = 'warning';
    }
}

// Get logs and total count
$logs = get_audit_logs($conn, $filters, $per_page, $offset);
$total_logs = get_audit_logs_count($conn, $filters);
$total_pages = ceil($total_logs / $per_page);

// Get all users for filter dropdown
$users_query = $conn->query("SELECT id, full_name, username, role FROM users ORDER BY full_name");
$users = [];
while ($user = $users_query->fetch_assoc()) {
    $users[] = $user;
}

// Get unique actions for filter dropdown
$actions_query = $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action");
$actions = [];
while ($action = $actions_query->fetch_assoc()) {
    $actions[] = $action['action'];
}

// Get unique target tables for filter dropdown
$tables_query = $conn->query("SELECT DISTINCT target_table FROM audit_logs WHERE target_table IS NOT NULL ORDER BY target_table");
$target_tables = [];
while ($table = $tables_query->fetch_assoc()) {
    $target_tables[] = $table['target_table'];
}

// Get quick stats for dashboard cards
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$stats_today = $conn->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(timestamp) = '$today'")->fetch_assoc()['count'] ?? 0;
$stats_yesterday = $conn->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(timestamp) = '$yesterday'")->fetch_assoc()['count'] ?? 0;
$stats_total_users = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM audit_logs WHERE user_id IS NOT NULL")->fetch_assoc()['count'] ?? 0;
$stats_unique_actions = $conn->query("SELECT COUNT(DISTINCT action) as count FROM audit_logs")->fetch_assoc()['count'] ?? 0;
?>

<div class="dashboard-header">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h1>System Audit Log History</h1>
            <p>Comprehensive security and activity monitoring for your hotel management system.</p>
        </div>
        <button type="button" onclick="showClearLogsModal()" class="btn btn-danger">
            Clear All Logs
        </button>
    </div>
</div>

<?php if ($feedback_message): ?>
    <div class="alert alert-<?= $feedback_type ?>" style="margin-bottom: 30px;">
        <?= htmlspecialchars($feedback_message) ?>
    </div>
<?php endif; ?>

<!-- Quick Statistics -->
<div class="kpi-grid" style="margin-bottom: 30px;">
    <div class="kpi-card">
        <div class="info">
            <div class="value"><?= number_format($total_logs) ?></div>
            <div class="label">Total Log Entries</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="info">
            <div class="value"><?= number_format($stats_today) ?></div>
            <div class="label">Events Today</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="info">
            <div class="value"><?= number_format($stats_total_users) ?></div>
            <div class="label">Active Users</div>
        </div>
    </div>
    <div class="kpi-card">
        <div class="info">
            <div class="value"><?= number_format($stats_unique_actions) ?></div>
            <div class="label">Event Types</div>
        </div>
    </div>
</div>

<!-- Filters Form -->
<div class="card" style="margin-bottom: 30px;">
    <h3 style="margin-bottom: 20px;">Filter Audit Logs</h3>
    <form method="GET" class="audit-filters">
        <div class="filter-row">
            <div class="filter-group">
                <label for="user_id" class="form-label">User:</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= (isset($filters['user_id']) && $filters['user_id'] == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['full_name'] ?? $user['username']) ?> (<?= $user['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="action" class="form-label">Action:</label>
                <select name="action" id="action" class="form-select">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $action): ?>
                        <option value="<?= htmlspecialchars($action) ?>" <?= (isset($filters['action']) && $filters['action'] == $action) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($action) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="target_table" class="form-label">Table:</label>
                <select name="target_table" id="target_table" class="form-select">
                    <option value="">All Tables</option>
                    <?php foreach ($target_tables as $table): ?>
                        <option value="<?= htmlspecialchars($table) ?>" <?= (isset($filters['target_table']) && $filters['target_table'] == $table) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($table) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="filter-row">
            <div class="filter-group">
                <label for="date_from" class="form-label">From Date:</label>
                <input type="date" name="date_from" id="date_from" class="form-input" value="<?= $_GET['date_from'] ?? '' ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to" class="form-label">To Date:</label>
                <input type="date" name="date_to" id="date_to" class="form-input" value="<?= $_GET['date_to'] ?? '' ?>">
            </div>
            
            <div class="filter-group">
                <label class="form-label">&nbsp;</label>
                <div style="display: flex; gap: 10px; margin-bottom: 1.25rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 8px 20px;">Apply Filters</button>
                    <a href="audit_log_viewer.php" class="btn btn-secondary" style="padding: 8px 20px;">Clear Filters</a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Results Summary -->
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h4 style="margin: 0; color: #B6862C;">Search Results</h4>
            <p style="margin: 5px 0 0 0; color: #ccc;">
                Showing <?= count($logs) ?> of <?= number_format($total_logs) ?> total audit entries
                <?php if ($total_pages > 1): ?>
                    (Page <?= $page ?> of <?= $total_pages ?>)
                <?php endif; ?>
            </p>
        </div>
        <?php if (count($logs) > 0): ?>
            <div style="color: #B6862C; font-size: 0.9rem;">
                <?= number_format(count($logs)) ?> records displayed
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Audit Logs Table -->
<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th style="min-width: 160px;">Action</th>
                    <th>Target</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #aaa;">
                            No audit logs found matching your criteria.
                            <?php if (!empty($filters)): ?>
                                <br><a href="audit_log_viewer.php" style="color: #B6862C;">Clear filters to see all logs</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><span style="font-family: monospace; color: #B6862C;">#<?= htmlspecialchars($log['id']) ?></span></td>
                            <td><?= date("M j, Y g:i A", strtotime($log['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($log['user_name'] ?? $log['username']) ?></td>
                            <td><span class="role-badge role-<?= strtolower($log['role']) ?>"><?= htmlspecialchars($log['role']) ?></span></td>
                            <td><span class="action-badge"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td>
                                <?php if ($log['target_table'] && $log['target_id']): ?>
                                    <span style="color: #B6862C;"><?= htmlspecialchars($log['target_table']) ?></span> #<?= $log['target_id'] ?>
                                <?php elseif ($log['target_table']): ?>
                                    <span style="color: #B6862C;"><?= htmlspecialchars($log['target_table']) ?></span>
                                <?php else: ?>
                                    <em style="color: #888;">System</em>
                                <?php endif; ?>
                            </td>
                            <td class="details-cell" title="<?= htmlspecialchars($log['details']) ?>">
                                <?= htmlspecialchars(strlen($log['details']) > 150 ? substr($log['details'], 0, 150) . '...' : $log['details']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div class="pagination-container mt-30">
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-link">« Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="pagination-current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-link"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-link">Next »</a>
            <?php endif; ?>
        </div>
        <div style="text-align: center; margin-top: 10px; color: #aaa; font-size: 0.9rem;">
            Showing page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_logs) ?> total entries)
        </div>
    </div>
<?php endif; ?>

<!-- Clear Logs Modal -->
<div id="clearLogsModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="hideClearLogsModal()">&times;</span>
        <h3 style="color: #dc3545; margin-bottom: 20px;">Clear All Audit Logs</h3>
        <p><strong>Warning:</strong> This action will permanently delete ALL audit log entries and cannot be undone.</p>
        <p>This will remove all historical records of system activity, user actions, and security events.</p>
        <p style="color: #dc3545;"><strong>Are you absolutely sure you want to proceed?</strong></p>
        
        <form method="POST" style="margin-top: 30px;">
            <div style="margin-bottom: 20px; text-align: left;">
                <label style="display: flex; align-items: center; gap: 10px; color: #333;">
                    <input type="checkbox" name="confirm_clear" value="yes" required style="transform: scale(1.2);">
                    I understand this will delete all audit logs permanently and cannot be undone
                </label>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button type="submit" name="clear_logs" class="btn btn-danger">
                    Yes, Clear All Logs
                </button>
                <button type="button" onclick="hideClearLogsModal()" class="btn btn-secondary">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.audit-filters {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 180px;
    flex: 1;
}

.details-cell {
    max-width: 400px;
    word-wrap: break-word;
    word-break: break-word;
    cursor: help;
    font-family: monospace;
    font-size: 0.9rem;
    line-height: 1.4;
    white-space: normal;
}

.table-container {
    max-height: 600px;
    overflow-x: auto;
    overflow-y: auto;
    border-radius: 8px;
}

.table-container table {
    position: relative;
}

.table-container thead th {
    position: sticky;
    top: 0;
    background-color: #122C55;
    z-index: 10;
    border-bottom: 2px solid #B6862C;
}

/* Custom Scrollbar Styling */
.table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #081C3A;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #B6862C;
    border-radius: 4px;
    border: 2px solid #081C3A;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #D4A340;
}

.table-container::-webkit-scrollbar-corner {
    background: #081C3A;
}

.pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.pagination {
    display: flex;
    gap: 5px;
    align-items: center;
    margin-bottom: 10px;
}

.pagination-link {
    padding: 10px 15px;
    background: #122C55;
    border: 1px solid #B6862C;
    text-decoration: none;
    color: #fff;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-weight: bold;
}

.pagination-link:hover {
    background: #B6862C;
    color: #081C3A;
    transform: translateY(-1px);
}

.pagination-current {
    padding: 10px 15px;
    background: #B6862C;
    color: #081C3A;
    border-radius: 6px;
    font-weight: bold;
    border: 1px solid #B6862C;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-admin {
    background-color: #dc3545;
    color: white;
}

.role-manager {
    background-color: #B6862C;
    color: #081C3A;
}

.role-front_desk {
    background-color: #28a745;
    color: white;
}

.role-housekeeping {
    background-color: #17a2b8;
    color: white;
}

.role-guest {
    background-color: #6c757d;
    color: white;
}

.action-badge {
    padding: 4px 8px;
    background-color: #122C55;
    border: 1px solid #B6862C;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: bold;
    color: #B6862C;
    white-space: nowrap;
    display: inline-block;
    min-width: fit-content;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.alert-success {
    background-color: #122C55;
    border-left-color: #28a745;
    color: #fff;
}

.alert-danger {
    background-color: #122C55;
    border-left-color: #dc3545;
    color: #fff;
}

.alert-warning {
    background-color: #122C55;
    border-left-color: #ffc107;
    color: #fff;
}

.dashboard-header h1 {
    margin-bottom: 5px;
}

.dashboard-header p {
    color: #aaa;
    margin: 0;
}
</style>

<script>
function showClearLogsModal() {
    document.getElementById('clearLogsModal').style.display = 'flex';
}

function hideClearLogsModal() {
    document.getElementById('clearLogsModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('clearLogsModal');
    if (event.target === modal) {
        hideClearLogsModal();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>