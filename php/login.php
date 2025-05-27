<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if (empty($_POST['username']) || empty($_POST['password'])) {
        header("Location: ../index.php?error=missing_fields");
        exit;
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT id, role, password FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $user_role, $hashed_password);

    if ($stmt->fetch() && password_verify($password, $hashed_password)) {
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $user_role;
        session_regenerate_id(true);

        
        header("Location: ../welcome.php");
        exit;
    } else {
        
        header("Location: ../index.php?error=invalid");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>
