<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../includes/db.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function respond(array $p, int $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

function read_input(): array {
    $uidRaw = $_POST['uid'] ?? null;
    $cardId = isset($_POST['card_id']) ? (int)$_POST['card_id'] : null;

    if ($uidRaw === null) {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $j = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($j)) {
                if (isset($j['uid']))     $uidRaw = (string)$j['uid'];
                if (isset($j['card_id'])) $cardId = (int)$j['card_id'];
            }
        }
    }
    $uid = strtoupper(preg_replace('/[^0-9A-Fa-f]/','', (string)$uidRaw));
    if ($uid === '' || strlen($uid) < 4 || strlen($uid) > 32) respond(['status'=>'error','message'=>'Invalid uid'],200);
    return [$uid, $cardId];
}

list($uid, $cardId) = read_input();

try {
    $conn->beginTransaction();

    if ($cardId) {
        $st = $conn->prepare("SELECT id,status FROM card_queue WHERE id=:id FOR UPDATE");
        $st->execute([':id'=>$cardId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $conn->rollBack(); respond(['status'=>'error','message'=>'card_id not found'],200); }
        if (!in_array((string)$row['status'], ['pending','reserved',''], true)) {
            $conn->rollBack(); respond(['status'=>'error','message'=>'Row not reservable/usable'],200);
        }

        $upd = $conn->prepare("UPDATE card_queue SET status='used', card_uid=:u, used_at=NOW() WHERE id=:id");
        $upd->execute([':u'=>$uid, ':id'=>$row['id']]);

        $conn->commit();
        respond(['status'=>'success','card_id'=>(int)$row['id'],'uid'=>$uid],200);
    }

    // If no card_id provided → bind the most recent RESERVED row
    $st = $conn->query("SELECT id FROM card_queue WHERE status='reserved' ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $conn->rollBack(); respond(['status'=>'error','message'=>'No reserved row to bind'],200); }

    $upd = $conn->prepare("UPDATE card_queue SET status='used', card_uid=:u, used_at=NOW() WHERE id=:id");
    $upd->execute([':u'=>$uid, ':id'=>$row['id']]);

    $conn->commit();
    respond(['status'=>'success','card_id'=>(int)$row['id'],'uid'=>$uid],200);

} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    respond(['status'=>'error','message'=>'Server error.'],500);
}