<?php
// === FILE: log_room.php ===

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

/**
 * Logs a room-related action into the audit_rooms table.
 *
 * @param PDO $conn            The PDO database connection.
 * @param int $room_id         The ID of the room.
 * @param int|null $booking_id The ID of the related booking (nullable).
 * @param string $status       The room status (e.g., booked, checked_in, checked_out, maintenance, etc.).
 * @param string $action       The description of the action taken (e.g., 'Room booked', 'Price updated').
 */
function log_room_action($conn, $room_id, $booking_id = null, $status = '', $action = '') {
    if (!$room_id || !$action) {
        error_log("❌ Room log skipped: Missing room ID or action.");
        return;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO audit_rooms (room_id, booking_id, status, action_taken, timestamp)
            VALUES (:room_id, :booking_id, :status, :action, NOW())
        ");

        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindValue(':booking_id', $booking_id, $booking_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':status', $status ?: 'unknown'); // fallback if empty
        $stmt->bindValue(':action', $action);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("❌ Audit Log Error: " . $e->getMessage());
    }
}