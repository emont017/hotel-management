<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Security Check: Restrict access to authorized roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: /index.php");
    exit;
}

// CSRF Token Generation & Validation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$feedback_message = '';
$feedback_type = '';

// Handle POST Actions (Create User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Check if username already exists (including deactivated users)
            $check_stmt = $conn->prepare("SELECT id, is_active FROM users WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $existing_user = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($existing_user) {
                if ($existing_user['is_active'] == 1) {
                    $feedback_message = "Error: Username '{$username}' is already taken by an active user.";
                    $feedback_type = 'danger';
                } else {
                    // Reactivate the deactivated user with new details
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ?, role = ?, full_name = ?, email = ?, phone = ?, is_active = 1 WHERE id = ?");
                    $update_stmt->bind_param("sssssi", $hashed_password, $role, $full_name, $email, $phone, $existing_user['id']);
                    
                    if ($update_stmt->execute()) {
                        // Log user reactivation
                        log_user_management_event($conn, $_SESSION['user_id'], 'User Reactivated', $existing_user['id'], 
                            "Deactivated user reactivated with new details: {$username} ({$full_name}) - Role: {$role}");
                        
                        $feedback_message = "Staff member '{$username}' has been reactivated with updated details!";
                        $feedback_type = 'success';
                    } else {
                        $feedback_message = "Error: Could not reactivate staff member.";
                        $feedback_type = 'danger';
                    }
                    $update_stmt->close();
                }
            } else {
                // Create new user - explicitly set is_active to 1
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, role, full_name, email, phone, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->bind_param("ssssss", $username, $hashed_password, $role, $full_name, $email, $phone);
                
                if ($stmt->execute()) {
                    $new_user_id = $stmt->insert_id;
                    
                    // Log user creation
                    log_user_management_event($conn, $_SESSION['user_id'], 'User Created', $new_user_id, 
                        "New {$role} user created: {$username} ({$full_name})");
                    
                    $feedback_message = "Staff member '{$username}' created successfully!";
                    $feedback_type = 'success';
                } else {
                    $feedback_message = "Error: Could not create staff member.";
                    $feedback_type = 'danger';
                }
                $stmt->close();
            }
        } else {
            $feedback_message = "Username, password, and role are required.";
            $feedback_type = 'danger';
        }
    }
}

// --- Handle GET Actions (Deactivate User) ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['user_id'])) {
    if (!isset($_GET['token']) || !hash_equals($csrf_token, $_GET['token'])) {
        die('CSRF token validation failed.');
    }

    $user_id_to_delete = (int)$_GET['user_id'];
    $current_user_id = (int)$_SESSION['user_id'];

    if ($user_id_to_delete === $current_user_id) {
        $feedback_message = "Error: You cannot deactivate your own account.";
        $feedback_type = 'danger';
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        if ($stmt->execute()) {
            $feedback_message = "Staff member deactivated successfully.";
            $feedback_type = 'success';
        } else {
            $feedback_message = "Error: Could not deactivate staff member.";
            $feedback_type = 'danger';
        }
        $stmt->close();
    }
}

// --- Calculate basic stats for role distribution ---
$staff_stats = $conn->query("SELECT role, COUNT(*) as count FROM users WHERE is_active = 1 AND role != 'guest' GROUP BY role");
$staff_by_role = [];
$total_staff = 0;
while($stat = $staff_stats->fetch_assoc()) {
    $staff_by_role[$stat['role']] = $stat['count'];
    $total_staff += $stat['count'];
}

$total_guests_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'guest' AND is_active = 1");
$total_guests = $total_guests_query->fetch_assoc()['count'] ?? 0;

// --- Data Fetching with Filtering (Guests Excluded) ---
$filter_role = $_GET['filter_role'] ?? 'all';
// UPDATED SQL: Added "AND role != 'guest'" to exclude guest accounts
$sql = "SELECT id, username, role, full_name, email, phone FROM users WHERE is_active = 1 AND role != 'guest'";
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

// --- Guest Data Fetching for CRM ---
$guest_search = $_GET['guest_search'] ?? '';
$guest_sql = "SELECT u.id, u.username, u.full_name, u.email, u.phone,
                     COUNT(b.id) as total_bookings,
                     COALESCE(SUM(p.amount), 0) as total_spent,
                     MAX(b.check_out) as last_stay,
                     MIN(b.check_in) as first_stay
              FROM users u 
              LEFT JOIN bookings b ON u.id = b.user_id 
              LEFT JOIN payments p ON b.id = p.booking_id
              WHERE u.role = 'guest' AND u.is_active = 1";

if (!empty($guest_search)) {
    $guest_sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)";
}

$guest_sql .= " GROUP BY u.id ORDER BY total_spent DESC, u.full_name ASC";

$stmt_guests = $conn->prepare($guest_sql);
if (!empty($guest_search)) {
    $search_param = "%{$guest_search}%";
    $stmt_guests->bind_param("sss", $search_param, $search_param, $search_param);
}
$stmt_guests->execute();
$guests_result = $stmt_guests->get_result();

$title = "Staff & Guest Management";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-header">
    <div>
        <h1>Staff & Guest Management</h1>
        <p>Create and manage staff accounts, monitor user permissions, and maintain guest relationships for optimal operations.</p>
    </div>
</div>

<?php if ($feedback_message): ?>
    <div class="alert alert-<?= $feedback_type === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 30px;">
        <span class="alert-icon"><?= $feedback_type === 'success' ? '✓' : '⚠️' ?></span>
        <span><?= htmlspecialchars($feedback_message) ?></span>
    </div>
<?php endif; ?>


<!-- Main Content Grid -->
<div class="detail-grid" style="margin-top: 20px;">
    <!-- Staff Creation Form -->
    <div class="card">
        <h3 style="margin-bottom: 10px; color: #B6862C;">Create New Staff Member</h3>
        <p style="color: #8892a7; margin-bottom: 25px; font-size: 0.9rem;">Add new employees and assign appropriate roles and permissions.</p>
        
        <form method="post" action="users.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <label class="form-label">Username:</label>
            <input type="text" name="username" placeholder="Required" required class="form-input">

            <label class="form-label">Password:</label>
            <input type="password" name="password" placeholder="Required" required class="form-input">

            <label class="form-label">Role:</label>
            <select name="role" required class="form-select">
                <option value="">Select Role</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="front_desk">Front Desk</option>
                <option value="housekeeping">Housekeeping</option>
                <option value="accountant">Accountant</option>
            </select>

            <label class="form-label">Full Name:</label>
            <input type="text" name="full_name" class="form-input" placeholder="Optional">

            <label class="form-label">Email:</label>
            <input type="email" name="email" class="form-input" placeholder="Optional">

            <label class="form-label">Phone:</label>
            <input type="tel" name="phone" class="form-input" placeholder="Optional">

            <button type="submit" name="create_user" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Create Staff Member</button>
        </form>
    </div>

    <!-- Quick Role Stats -->
    <div class="card">
        <h3 style="margin-bottom: 10px; color: #B6862C;">Staff by Role</h3>
        <p style="color: #8892a7; margin-bottom: 20px; font-size: 0.9rem;">Current distribution of active staff members.</p>
        
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php 
            $role_labels = [
                'admin' => 'Administrators',
                'manager' => 'Managers', 
                'front_desk' => 'Front Desk',
                'housekeeping' => 'Housekeeping',
                'accountant' => 'Accountants'
            ];
            
            foreach($role_labels as $role => $label): 
                $count = $staff_by_role[$role] ?? 0;
            ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <span style="color: #fff; font-size: 0.9rem;"><?= $label ?></span>
                    <span style="color: #B6862C; font-weight: 600; font-size: 0.9rem;"><?= $count ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Staff Management Table -->
<div class="card" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="margin: 0; color: #B6862C;">Active Staff Members</h3>
            <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                Manage permissions and access for all staff accounts
            </p>
        </div>
        <div style="color: #8892a7; font-size: 0.85rem; font-weight: 500;">
            <?= number_format($total_staff) ?> active staff member<?= $total_staff !== 1 ? 's' : '' ?>
        </div>
    </div>

    <form method="GET" action="users.php" style="margin-bottom: 20px;">
        <label for="filter_role" class="form-label">Filter by Role:</label>
        <select name="filter_role" id="filter_role" class="form-select" onchange="this.form.submit()" style="width: 200px; display: inline-block;">
            <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>All Staff Roles</option>
            <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="manager" <?= $filter_role === 'manager' ? 'selected' : '' ?>>Manager</option>
            <option value="front_desk" <?= $filter_role === 'front_desk' ? 'selected' : '' ?>>Front Desk</option>
            <option value="housekeeping" <?= $filter_role === 'housekeeping' ? 'selected' : '' ?>>Housekeeping</option>
            <option value="accountant" <?= $filter_role === 'accountant' ? 'selected' : '' ?>>Accountant</option>
        </select>
    </form>

    <div class="table-container" style="max-height: 500px; overflow-y: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">ID</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Username</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Role</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Full Name</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Email</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Phone</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td>
                            <span style="font-weight: 600; color: #B6862C;">
                                <?= htmlspecialchars($row['username']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="role-badge" style="background-color: #3498db; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                <?= strtoupper(str_replace('_', ' ', $row['role'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['full_name'] ?: 'N/A') ?></td>
                        <td style="color: #8892a7;"><?= htmlspecialchars($row['email'] ?: 'N/A') ?></td>
                        <td style="color: #8892a7;"><?= htmlspecialchars($row['phone'] ?: 'N/A') ?></td>
                        <td>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a href="admin_user_edit.php?user_id=<?= $row['id'] ?>" class="btn-link-style" style="font-size: 0.85rem;">Edit</a>
                                <a href="?action=delete&user_id=<?= $row['id'] ?>&token=<?= $csrf_token ?>"
                                   onclick="return confirm('Are you sure you want to deactivate this user? They will no longer be able to log in.')"
                                   class="btn-link-style" style="color: #e74c3c; font-size: 0.85rem;">Deactivate</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px 20px; color: #8892a7;">
                            <div style="font-size: 2rem; margin-bottom: 10px;">👥</div>
                            <div>No active staff members found matching the criteria.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Guest Management Section -->
<div class="card" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h3 style="margin: 0; color: #B6862C;">Guest Relationship Management</h3>
            <p style="margin: 5px 0 0 0; color: #8892a7; font-size: 0.9rem;">
                Monitor guest accounts, spending patterns, and booking history
            </p>
        </div>
        <div style="color: #8892a7; font-size: 0.85rem; font-weight: 500;">
            <?= number_format($total_guests) ?> registered guest<?= $total_guests !== 1 ? 's' : '' ?>
        </div>
    </div>

    <form method="GET" action="users.php" style="margin-bottom: 20px;">
        <label for="guest_search" class="form-label">Search Guests:</label>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="guest_search" id="guest_search" 
                   value="<?= htmlspecialchars($guest_search) ?>" 
                   placeholder="Search by name, email, or username"
                   class="form-input" style="flex: 1;">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (!empty($guest_search)): ?>
                <a href="users.php" class="btn btn-secondary" style="background-color: #6c757d;">Clear</a>
            <?php endif; ?>
        </div>
        <!-- Preserve other filters -->
        <?php if (isset($_GET['filter_role']) && $_GET['filter_role'] !== 'all'): ?>
            <input type="hidden" name="filter_role" value="<?= htmlspecialchars($_GET['filter_role']) ?>">
        <?php endif; ?>
    </form>

    <div class="table-container" style="max-height: 500px; overflow-y: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">ID</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Guest Name</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Email</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Phone</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Bookings</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Total Spent</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">First Stay</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10;">Last Stay</th>
                    <th style="position: sticky; top: 0; background-color: #122C55; z-index: 10; width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($guests_result->num_rows > 0): ?>
                    <?php while ($guest = $guests_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $guest['id'] ?></td>
                        <td>
                            <span style="font-weight: 600; color: #B6862C;">
                                <?= htmlspecialchars($guest['full_name'] ?: $guest['username']) ?>
                            </span>
                        </td>
                        <td style="color: #8892a7;"><?= htmlspecialchars($guest['email'] ?: 'N/A') ?></td>
                        <td style="color: #8892a7;"><?= htmlspecialchars($guest['phone'] ?: 'N/A') ?></td>
                        <td><?= $guest['total_bookings'] ?></td>
                        <td>
                            <span style="font-family: monospace; font-weight: 600; color: #2ecc71;">
                                $<?= number_format($guest['total_spent'], 2) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.9rem;"><?= $guest['first_stay'] ? date('M j, Y', strtotime($guest['first_stay'])) : 'Never' ?></td>
                        <td style="font-size: 0.9rem;"><?= $guest['last_stay'] ? date('M j, Y', strtotime($guest['last_stay'])) : 'Never' ?></td>
                        <td>
                            <a href="admin_guest_profile.php?guest_id=<?= $guest['id'] ?>" 
                               class="btn-link-style" style="font-size: 0.85rem;">View Profile</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: #8892a7;">
                            <div style="font-size: 2rem; margin-bottom: 10px;">🔍</div>
                            <div><?= !empty($guest_search) ? 'No guests found matching your search.' : 'No guest accounts found.' ?></div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$stmt_users->close();
$stmt_guests->close();
require_once __DIR__ . '/../includes/footer.php';
?>
