<?php
session_start();
if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
} else {
    $_SESSION['lang'] = 'en'; // fallback
}

require_once '../includes/db.php';
require_once '../includes/log_action.php'; // ✅ Unified logger

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['lang'] = $_POST['lang'] ?? 'en'; // ✅ Save selected language

        // If password reset is required
        if (!empty($user['force_password_reset'])) {
            $_SESSION['force_reset'] = true;
            header("Location: change_password.php?default=1");
            exit();
        }

        // ✅ Log the login event
        log_action('Login', "User {$user['username']} logged in.");

        header("Location: ../modules/dashboard.php");
        exit();
    } else {
        header("Location: login.php?error=Invalid username or password");
        exit();
    }
} else {
    header("Location: login.php?error=Invalid request");
    exit();
}