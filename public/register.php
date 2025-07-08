<?php
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/db.php';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$username || !$password || !$full_name || !$email) {
        $error = "Please fill all required fields.";
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
            $role = 'guest'; // Standardized role

            $insert_sql = "INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($insert_sql);
            $stmt_insert->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $role);

            if ($stmt_insert->execute()) {
                $_SESSION['user_id'] = $stmt_insert->insert_id;
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;
                session_regenerate_id(true);
                header("Location: welcome.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt_insert->close();
        }
        $stmt->close();
        $conn->close();
    }
}

$title = "Register";
require_once __DIR__ . '/../includes/header.php';
?>

<h2 class="text-center mt-30">Register</h2>

<?php if ($error): ?>
    <p class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form action="register.php" method="POST" class="form-container">
    <label for="username" class="form-label">Username <span style="color:red">*</span></label>
    <input type="text" id="username" name="username" class="form-input" required>

    <label for="password" class="form-label">Password <span style="color:red">*</span></label>
    <input type="password" id="password" name="password" class="form-input" required>

    <label for="full_name" class="form-label">Full Name <span style="color:red">*</span></label>
    <input type="text" id="full_name" name="full_name" class="form-input" required>

    <label for="email" class="form-label">Email <span style="color:red">*</span></label>
    <input type="email" id="email" name="email" class="form-input" required>

    <label for="phone" class="form-label">Phone Number:</label>
    <input type="tel" id="phone" name="phone" pattern="[0-9+\-\s\(\)]*" placeholder="+1 555-555-5555" class="form-input">

    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.3rem;">
        Register
    </button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>