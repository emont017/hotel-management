<?php
session_start();

// If a user is already logged in, redirect them to the welcome page
if (isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $sql = "SELECT id, username, role, password, full_name, email, phone FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $db_username, $user_role, $hashed_password, $full_name, $email, $phone);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $user_role;
                $_SESSION['username'] = $db_username;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                
                // Log successful login
                log_auth_event($conn, $user_id, 'User Login', "Successful login");
    
                header("Location: welcome.php");
                exit;
            }
                }
        
        // Log failed login attempt
        $attempt_user_id = $user_id ?? 0; // Use 0 if user not found
        if ($attempt_user_id > 0) {
            log_auth_event($conn, $attempt_user_id, 'Failed Login Attempt', "Invalid password");
        }
        
        $error = "Invalid username or password.";
    }
    $stmt->close();
    $conn->close();
}

$title = "Login";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="form-container">
    <h1 class="text-center">Staff Login</h1>

    <?php if ($error): ?>
        <p class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'logged_out'): ?>
        <p class="alert alert-success text-center">You have been logged out successfully.</p>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <label for="username" class="form-label">Username:</label>
        <input type="text" id="username" name="username" class="form-input" required>

        <label for="password" class="form-label">Password:</label>
        <input type="password" id="password" name="password" class="form-input" required>

        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.2rem;">
            Login
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>