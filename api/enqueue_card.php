<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function respond($p,$c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit; }

try {
    $guest = trim($_POST['guest_name'] ?? '');
    $room  = trim($_POST['room_number'] ?? '');

    if ($room === '') respond(['status'=>'error','message'=>'room_number required'],200);
    if ($guest === '') $guest = 'Guest';

    $ins = $conn->prepare("INSERT INTO card_queue (guest_name, room_number, status, created_at)
                           VALUES (:g, :r, 'pending', NOW())");
    $ins->execute([':g'=>$guest, ':r'=>$room]);
    respond(['status'=>'success','card_id'=>(int)$conn->lastInsertId()],200);

} catch (Throwable $e) {
    respond(['status'=>'error','message'=>'server'],500);
}