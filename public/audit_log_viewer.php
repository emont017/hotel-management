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
?>

<div class="container">
    <h2>System Audit Log History</h2>
    
    <!-- Filters Form -->
    <div class="card mb-30">
        <h3>Filter Logs</h3>
        <form method="GET" class="audit-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="user_id">User:</label>
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
                    <label for="action">Action:</label>
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
                    <label for="target_table">Table:</label>
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
                    <label for="date_from">From Date:</label>
                    <input type="date" name="date_from" id="date_from" class="form-input" value="<?= $_GET['date_from'] ?? '' ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">To Date:</label>
                    <input type="date" name="date_to" id="date_to" class="form-input" value="<?= $_GET['date_to'] ?? '' ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="audit_log_viewer.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="results-summary mb-20">
        <p>Showing <?= count($logs) ?> of <?= number_format($total_logs) ?> total audit entries</p>
        <?php if ($total_pages > 1): ?>
            <p>Page <?= $page ?> of <?= $total_pages ?></p>
        <?php endif; ?>
    </div>

    <!-- Audit Logs Table -->
    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No audit logs found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['id']) ?></td>
                            <td><?= date("M j, Y g:i A", strtotime($log['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($log['user_name'] ?? $log['username']) ?></td>
                            <td class="text-capitalize"><?= htmlspecialchars($log['role']) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td>
                                <?php if ($log['target_table'] && $log['target_id']): ?>
                                    <?= htmlspecialchars($log['target_table']) ?> #<?= $log['target_id'] ?>
                                <?php elseif ($log['target_table']): ?>
                                    <?= htmlspecialchars($log['target_table']) ?>
                                <?php else: ?>
                                    <em>System</em>
                                <?php endif; ?>
                            </td>
                            <td class="details-cell" title="<?= htmlspecialchars($log['details']) ?>">
                                <?= htmlspecialchars(strlen($log['details']) > 50 ? substr($log['details'], 0, 50) . '...' : $log['details']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container mt-30">
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-link">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination-current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-link">Next →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.audit-filters {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.results-summary {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
    color: #666;
}

.details-cell {
    max-width: 300px;
    word-wrap: break-word;
    cursor: help;
}

.pagination-container {
    display: flex;
    justify-content: center;
}

.pagination {
    display: flex;
    gap: 10px;
    align-items: center;
}

.pagination-link {
    padding: 8px 12px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.pagination-link:hover {
    background: #e9ecef;
}

.pagination-current {
    padding: 8px 12px;
    background: #081C3A;
    color: white;
    border-radius: 4px;
    font-weight: bold;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>