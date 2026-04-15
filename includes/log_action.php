<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // Adjust path if needed when including

/**
 * Logs a system action to the system_logs table.
 *
 * @param string $action  Short description of the action (e.g., 'Check-in', 'Checkout').
 * @param string $details Detailed message or description of what happened.
 */
function log_action($action, $details = '') {
    global $conn;

    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description, log_time)
                                VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        error_log("System Log Error: " . $e->getMessage());
    }
}