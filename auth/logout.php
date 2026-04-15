<?php
session_start();
require_once '../includes/log_action.php';

// ✅ Log logout if user was logged in
if (isset($_SESSION['user_id'])) {
    $reason = $_GET['reason'] ?? '';
    $logMessage = ($reason === 'timeout') ? 'User session expired (auto logout).' : 'User logged out.';
    log_action('Logout', $logMessage);
}

// ✅ Clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// ✅ Prepare logout message
$reason = $_GET['reason'] ?? '';
if ($reason === 'timeout') {
    $message = 'Your session expired due to inactivity. Please log in again.';
} else {
    $message = 'You have been logged out successfully.';
}

// ✅ Redirect to login with message
header('Location: login.php?message=' . urlencode($message));
exit();