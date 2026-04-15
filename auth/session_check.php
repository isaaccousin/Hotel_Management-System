<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

$timeout_duration = 600; // 10 minutes
$lang = $_SESSION['lang'] ?? 'en';

$timeout_message = [
    'en' => 'Your session has expired due to inactivity. Please log in again.',
    'fr' => 'Votre session a expiré pour cause d\'inactivité. Veuillez vous reconnecter.'
];

$script_path = $_SERVER['SCRIPT_NAME'];
$login_relative_path = strpos($script_path, '/auth/') !== false ? 'login.php' : '../auth/login.php';

// If not logged in
if (!isset($_SESSION['user_id'])) {
    if ($isAjax) {
        echo json_encode(['expired' => true]);
    } else {
        header("Location: $login_relative_path");
    }
    exit();
}

// Handle timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();

    if ($isAjax) {
        echo json_encode(['expired' => true, 'message' => $timeout_message[$lang]]);
    } else {
        $_SESSION['timeout_message'] = $timeout_message[$lang];
        header("Location: $login_relative_path?timeout=1");
    }
    exit();
}

// Update activity timestamp
$_SESSION['LAST_ACTIVITY'] = time();

if ($isAjax) {
    echo json_encode(['expired' => false]);
}
?>