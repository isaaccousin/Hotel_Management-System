<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/card_payload.php';

function respond($p,$c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES); exit; }

try {
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $kind = strtolower(trim($_POST['kind'] ?? ''));
  $card_uid_hex = trim($_POST['card_uid'] ?? '');
  $payload_sha_hex = trim($_POST['payload_sha256_hex'] ?? '');

  if (!in_array($kind, ['system','access','guest','staff'], true)) respond(['status'=>'error','message'=>'bad kind'], 400);
  if ($card_uid_hex === '' || (strlen(preg_replace('/\s/','',$card_uid_hex)) % 2) !== 0) respond(['status'=>'error','message'=>'bad uid'], 400);
  if ($payload_sha_hex === '' || strlen($payload_sha_hex) !== 64) respond(['status'=>'error','message'=>'bad sha'], 400);

  $card_uid_bin = hex_to_bytes($card_uid_hex);
  $payload_sha_bin = hex2bin($payload_sha_hex);

  $lock_id = null;
  $group_code = null;
  $valid_from = null;
  $valid_to = null;
  $key_label = null;

  if (isset($_POST['lock_serial'])) {
    $lock = find_lock_by_serial($conn, trim($_POST['lock_serial']));
    if ($lock) $lock_id = (int)$lock['id'];
  }
  if (isset($_POST['group_code'])) {
    $group_code = trim($_POST['group_code']);
  }
  if (isset($_POST['valid_from'])) $valid_from = date('Y-m-d H:i:s', strtotime($_POST['valid_from']));
  if (isset($_POST['valid_to']))   $valid_to   = date('Y-m-d H:i:s', strtotime($_POST['valid_to']));
  if (isset($_POST['key_label']))  $key_label  = trim($_POST['key_label']);

  $stmt = $conn->prepare("
    INSERT INTO cards_issued (card_uid, kind, lock_id, group_code, valid_from, valid_to, key_label, payload_hash)
    VALUES (:uid, :k, :lid, :g, :vf, :vt, :kl, :ph)
  ");
  $stmt->execute([
    ':uid' => $card_uid_bin,
    ':k'   => $kind,
    ':lid' => $lock_id,
    ':g'   => $group_code,
    ':vf'  => $valid_from,
    ':vt'  => $valid_to,
    ':kl'  => $key_label,
    ':ph'  => $payload_sha_bin
  ]);

  respond(['status'=>'success','id'=>(int)$conn->lastInsertId()], 200);
} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'server'], 500);
}