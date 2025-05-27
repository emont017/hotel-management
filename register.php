<?php
session_start();

$title = "Register";
require_once 'includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'php/db.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    
    if (!$username || !$password || !$full_name || !$email) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($phone !== '' && !preg_match('/^[0-9+\-\s\(\)]+$/', $phone)) {
        $error = "Invalid phone number format.";
    } else {
        
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'friend';

            $insert_sql = "INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $role);

            if ($stmt->execute()) {
                
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['role'] = $role;
                session_regenerate_id(true);

                header("Location: welcome.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>

<h2 style="text-align:center; margin-top: 30px;">Register</h2>

<?php if ($error): ?>
    <p style="color:red; text-align:center; font-weight:bold; margin-top: 10px;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form action="register.php" method="POST" style="
    max-width: 500px;
    margin: 30px auto 60px;
    padding: 30px;
    background-color: rgba(7, 28, 58, 0.85);
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(247, 178, 35, 0.7);
    color: 
    font-size: 1.1rem;
">
    <label for="username" style="display:block; margin-bottom:8px;">Username <span style="color:
    <input type="text" id="username" name="username" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px;">

    <label for="password" style="display:block; margin-bottom:8px;">Password <span style="color:
    <input type="password" id="password" name="password" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px;">

    <label for="full_name" style="display:block; margin-bottom:8px;">Full Name <span style="color:
    <input type="text" id="full_name" name="full_name" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px;">

    <label for="email" style="display:block; margin-bottom:8px;">Email <span style="color:
    <input type="email" id="email" name="email" required
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 20px;">

    <label for="phone" style="display:block; margin-bottom:8px;">Phone Number:</label>
    <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s\(\)]*" placeholder="+1 555-555-5555"
        style="width: 100%; padding: 10px; border-radius: 8px; border: none; font-size: 1rem; margin-bottom: 30px;">

    <button type="submit" style="
        width: 100%;
        padding: 12px;
        background-color: 
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.3rem;
        color: 
        cursor: pointer;
        transition: background-color 0.3s ease;
    "
    onmouseover="this.style.backgroundColor='
    onmouseout="this.style.backgroundColor='
        Register
    </button>
</form>

<?php require_once 'includes/footer.php'; ?>
