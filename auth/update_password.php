<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['force_reset'])) {
    header("Location: login.php");
    exit();
}

$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($new) < 6 || $new !== $confirm) {
    header("Location: change_password.php?error=Passwords do not match or too short");
    exit();
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hash, $_SESSION['user_id']]);

unset($_SESSION['force_reset']);
header("Location: ../modules/dashboard.php");
exit();