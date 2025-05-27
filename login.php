<?php
session_start();

$title = "Login";
require_once 'includes/header.php';
?>

<h1 style="text-align: center; margin-top: 30px;">🔐 Login</h1>

<?php
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'missing_fields') {
        echo "<p style='color:red; text-align:center; margin-top: 10px;'>Please enter both username and password.</p>";
    } elseif ($_GET['error'] === 'invalid') {
        echo "<p style='color:red; text-align:center; margin-top: 10px;'>Invalid username or password.</p>";
    }
}
?>

<div style="
    max-width: 500px;
    margin: 30px auto 60px;
    padding: 30px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 15px;
    box-shadow: 0 0 25px rgba(247, 178, 35, 0.8);
    color: #ffffff;
">
    <form action="php/login.php" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
        <label for="username" style="font-weight: bold; color: #F7B223;">Username:</label>
        <input type="text" id="username" name="username" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="password" style="font-weight: bold; color: #F7B223;">Password:</label>
        <input type="password" id="password" name="password" required style="
            padding: 10px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <button type="submit" style="
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
            Login
        </button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
