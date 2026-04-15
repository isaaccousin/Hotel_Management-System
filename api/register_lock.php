<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/card_payload.php';

function respond($p, $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES); exit; }

try {
  $name = trim($_POST['name'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $serial_hex = trim($_POST['lock_serial'] ?? '');

  if ($serial_hex === '') respond(['status'=>'error','message'=>'lock_serial required'], 400);
  $serial_bin = hex_to_bytes($serial_hex);
  if ($serial_bin === '') respond(['status'=>'error','message'=>'invalid serial'], 400);

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // upsert by lock_serial
  $stmt = $conn->prepare("SELECT id FROM locks WHERE lock_serial=:s");
  $stmt->execute([':s'=>$serial_bin]);
  $row = $stmt->fetch(PDO::FETCH_NUM);

  if ($row) {
    $id = (int)$row[0];
    $upd = $conn->prepare("UPDATE locks SET name=COALESCE(NULLIF(:n,''),name), location=COALESCE(NULLIF(:l,''),location) WHERE id=:id");
    $upd->execute([':n'=>$name, ':l'=>$location, ':id'=>$id]);
  } else {
    $ins = $conn->prepare("INSERT INTO locks (name,location,lock_serial) VALUES (:n,:l,:s)");
    $ins->execute([':n'=>$name, ':l'=>$location, ':s'=>$serial_bin]);
    $id = (int)$conn->lastInsertId();
  }

  respond(['status'=>'success','lock_id'=>$id], 200);
} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'server'], 500);
}