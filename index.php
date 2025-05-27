<?php
session_start();
$title = "Home";
require_once 'includes/header.php';  // Your consistent header with FIU colors & nav
?>

<h1>Welcome to our Hotel!</h1>
<p>Your comfort is our priority. Please log in below or book your stay without logging in.</p>

<!-- Login form -->
<div style="max-width: 400px; margin: 20px auto; padding: 20px; background-color: #081C3A; border-radius: 10px;">
    <form action="php/login.php" method="POST" style="display: flex; flex-direction: column;">
        <label for="username" style="color: #F7B223; font-weight: bold;">Username:</label>
        <input type="text" id="username" name="username" required style="margin-bottom: 15px; padding: 8px; border-radius: 5px; border:none;">

        <label for="password" style="color: #F7B223; font-weight: bold;">Password:</label>
        <input type="password" id="password" name="password" required style="margin-bottom: 15px; padding: 8px; border-radius: 5px; border:none;">

        <button type="submit" style="
            background-color: #F7B223;
            color: #081C3A;
            font-weight: bold;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
        ">Login</button>
    </form>

    <?php
    if (isset($_GET['error'])) {
        echo "<p style='color:red; text-align:center; margin-top: 15px;'>Invalid login credentials, please try again.</p>";
    }
    if (isset($_GET['message']) && $_GET['message'] === 'registration_successful') {
        echo "<p style='color:lightgreen; text-align:center; margin-top: 15px;'>Registration successful! You can now log in.</p>";
    }
    ?>
</div>

<!-- Public booking call to action -->
<div style="text-align:center; margin: 40px 0;">
    <a href="bookings.php" style="
        background-color: #F7B223;
        color: #081C3A;
        padding: 15px 30px;
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        font-size: 1.2rem;
    ">Book Your Stay Now</a>
</div>

<?php
require_once 'includes/footer.php';  // Close body/html, add footer consistent styling
?>
