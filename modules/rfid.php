<?php
declare(strict_types=1);

/** Create a PENDING queue row */
function rfid_enqueue(PDO $conn, string $guestName, string $roomNumber): int {
    $stmt = $conn->prepare("INSERT INTO card_queue (guest_name, room_number, status, created_at)
                            VALUES (:g, :r, 'pending', NOW())");
    $stmt->execute([':g'=>$guestName ?: 'Guest', ':r'=>$roomNumber]);
    return (int)$conn->lastInsertId();
}

/** Mark all USED cards for the room as revoked (checkout/cancel) */
function rfid_revoke_room(PDO $conn, string $roomNumber): int {
    $stmt = $conn->prepare("UPDATE card_queue SET status='revoked' WHERE room_number=:r AND status='used'");
    $stmt->execute([':r'=>$roomNumber]);
    return $stmt->rowCount();
}

/** Revoke by specific UID */
function rfid_revoke_uid(PDO $conn, string $uidHex): int {
    $uid = strtoupper(preg_replace('/[^0-9A-Fa-f]/','', $uidHex));
    $stmt = $conn->prepare("UPDATE card_queue SET status='revoked' WHERE card_uid=:u AND status='used'");
    $stmt->execute([':u'=>$uid]);
    return $stmt->rowCount();
}

/** Manually set a reserved row to used (rare; normally done by rfid_bind_latest.php) */
function rfid_consume(PDO $conn, int $queueId, string $uidHex): bool {
    $uid = strtoupper(preg_replace('/[^0-9A-Fa-f]/','', $uidHex));
    $stmt = $conn->prepare("UPDATE card_queue SET status='used', card_uid=:u, used_at=NOW() WHERE id=:id AND status IN ('pending','reserved')");
    $stmt->execute([':u'=>$uid, ':id'=>$queueId]);
    return $stmt->rowCount() > 0;
}