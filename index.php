<?php
session_start();
$title = "Home";
require_once 'includes/header.php';  
?>

<h1 style="text-align: center; margin-top: 30px;">Welcome to Our Hotel!</h1>
<p style="text-align: center; max-width: 600px; margin: 10px auto 30px;">
    Your comfort is our priority. Please log in below or book your stay without logging in.
</p>

<!-- Login Form -->
<div style="
    max-width: 400px;
    margin: 30px auto;
    padding: 25px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 20px rgba(247, 178, 35, 0.7);
">
    <form action="php/login.php" method="POST" style="display: flex; flex-direction: column;">
        <label for="username" style="color: #F7B223; font-weight: bold; margin-bottom: 5px;">Username:</label>
        <input type="text" id="username" name="username" required style="
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            color: #081C3A;
        ">

        <label for="password" style="color: #F7B223; font-weight: bold; margin-bottom: 5px;">Password:</label>
        <input type="password" id="password" name="password" required style="
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
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
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
            Login
        </button>
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

<!-- Call to Action -->
<div style="text-align:center; margin: 40px 0;">
    <a href="bookings.php" style="
        background-color: #F7B223;
        color: #081C3A;
        padding: 15px 30px;
        font-weight: bold;
        text-decoration: none;
        border-radius: 10px;
        font-size: 1.2rem;
        box-shadow: 0 0 12px rgba(247, 178, 35, 0.7);
        transition: background-color 0.3s ease;
    " onmouseover="this.style.backgroundColor='#e5a91d'" onmouseout="this.style.backgroundColor='#F7B223'">
        Book Your Stay Now
    </a>
</div>

<?php
require_once 'includes/footer.php';  
?>
