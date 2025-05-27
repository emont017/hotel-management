<?php
session_start();

$title = "Login";
require_once 'includes/header.php';
?>

<h2 style="text-align:center; margin-top: 30px;">Login</h2>

<?php
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'missing_fields') {
        echo "<p style='color:red; text-align:center;'>Please enter both username and password.</p>";
    } elseif ($_GET['error'] === 'invalid') {
        echo "<p style='color:red; text-align:center;'>Invalid username or password.</p>";
    }
}
?>

<form action="php/login.php" method="POST" style="
    max-width: 500px;
    margin: 30px auto 60px;
    padding: 30px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(247, 178, 35, 0.7);
    color: #F7B223;
    font-size: 1.1rem;
">
    <label for="username" style="display:block; margin-bottom:8px;">Username:</label>
    <input type="text" id="username" name="username" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px;">

    <label for="password" style="display:block; margin-bottom:8px;">Password:</label>
    <input type="password" id="password" name="password" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 30px;">

    <button type="submit" style="
        width: 100%;
        padding: 12px;
        background-color: #F7B223;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.3rem;
        color: #081C3A;
        cursor: pointer;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='#e5a91d'"
    onmouseout="this.style.backgroundColor='#F7B223'">
        Login
    </button>
</form>

<?php require_once 'includes/footer.php'; ?>
