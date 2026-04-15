<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../includes/db.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function respond($p,$c=200){ http_response_code($c); echo json_encode($p,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : (int)($_POST['booking_id'] ?? 0);
$timeout   = isset($_GET['timeout']) ? (int)$_GET['timeout'] : (int)($_POST['timeout'] ?? 30);
if ($bookingId <= 0) respond(['status'=>'error','message'=>'booking_id required'],200);
if ($timeout < 1 || $timeout > 120) $timeout = 30;

$start = time();
try {
    while (time() - $start < $timeout) {
        $st = $conn->prepare("
            SELECT id, encoded_uid
            FROM card_queue
            WHERE booking_id = :bid AND status = 'used' AND encoded_uid IS NOT NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $st->execute([':bid'=>$bookingId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['encoded_uid'])) {
            respond(['status'=>'success','uid'=>$row['encoded_uid'],'card_id'=>(int)$row['id']]);
        }
        usleep(400000); // 0.4s
    }
    respond(['status'=>'waiting','message'=>'No card yet.'],200);
} catch (Throwable $e) {
    respond(['status'=>'error','message'=>'Server error.'],500);
}