<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/db.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function respond(array $p, int $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

try {
    $conn->beginTransaction();

    // Oldest PENDING → reserve it for this workstation
    $stmt = $conn->query("
        SELECT id
        FROM card_queue
        WHERE status='pending'
        ORDER BY id ASC
        LIMIT 1
        FOR UPDATE
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) { $conn->rollBack(); respond(['status'=>'waiting','message'=>'No pending card to reserve.'],200); }

    $upd = $conn->prepare("UPDATE card_queue SET status='reserved' WHERE id=:id");
    $upd->execute([':id'=>$row['id']]);

    $conn->commit();
    respond(['status'=>'success','card_id'=>(int)$row['id']],200);

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    respond(['status'=>'error','message'=>'Server error.'],500);
}