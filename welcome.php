<?php
session_start();
$title = "Welcome";
require_once 'includes/header.php';
?>

<h2 style="text-align: center;">🎉 Welcome to the FIU Hotel Management System</h2>
<p style="text-align: center; font-size: 1.2em; max-width: 600px; margin: 20px auto;">
    You’re now logged in. Use the navigation bar above to begin managing room reservations, update hotel information, or access the admin dashboard.
</p>

<?php require_once 'includes/footer.php'; ?>
