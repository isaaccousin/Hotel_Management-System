<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function respond($p,$c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

try {
    $uid = strtoupper(preg_replace('/[^0-9A-Fa-f]/','', (string)($_POST['card_uid'] ?? '')));
    if ($uid === '') respond(['status'=>'error','message'=>'card_uid required'],200);

    $upd = $conn->prepare("UPDATE card_queue SET status='revoked' WHERE card_uid=:u AND status='used'");
    $upd->execute([':u'=>$uid]);

    respond(['status'=>'success','affected'=>$upd->rowCount()],200);

} catch (Throwable $e) {
    respond(['status'=>'error','message'=>'server'],500);
}