<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/audit_functions.php';

// Log logout before clearing session
if (isset($_SESSION['user_id'])) {
    log_auth_event($conn, $_SESSION['user_id'], 'User Logout', "Logout from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to the public homepage with a logout message using an absolute path
header("Location: /index.php?message=logged_out");
exit;
?>