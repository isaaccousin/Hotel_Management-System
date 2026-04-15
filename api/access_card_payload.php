<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/card_payload.php';

function respond($p, $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES); exit; }

try {
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $group_code = trim($_POST['group_code'] ?? '');
  $lock_serial = trim($_POST['lock_serial'] ?? '');

  $vf = trim($_POST['valid_from'] ?? '');
  $vt = trim($_POST['valid_to'] ?? '');
  if ($vf === '' || $vt === '') respond(['status'=>'error','message'=>'valid_from/valid_to required'], 400);

  $from_ts = strtotime($vf);
  $to_ts   = strtotime($vt);
  if ($from_ts === false || $to_ts === false || $to_ts <= $from_ts) {
    respond(['status'=>'error','message'=>'invalid time range'], 400);
  }

  $lock_ids = [];
  $meta = [];

  if ($group_code !== '') {
    $lock_ids = lock_ids_for_group($conn, $group_code);
    if (!$lock_ids) respond(['status'=>'error','message'=>'unknown or empty group'], 404);
    $meta['group_code'] = $group_code;
  } elseif ($lock_serial !== '') {
    $lock = find_lock_by_serial($conn, $lock_serial);
    if (!$lock) respond(['status'=>'error','message'=>'unknown lock'], 404);
    $lock_ids = [(int)$lock['id']];
    $meta['lock_serial'] = strtoupper($lock_serial);
  } else {
    respond(['status'=>'error','message'=>'group_code OR lock_serial required'], 400);
  }

  // Compact list of u16 lock ids
  $locks_bin = '';
  foreach ($lock_ids as $id) {
    if ($id < 1 || $id > 65535) respond(['status'=>'error','message'=>'lock id out of u16 range'], 400);
    $locks_bin .= u16be((int)$id);
  }

  [$key_label, $key_bytes] = active_key($conn);

  // Build TLV
  $buf = '';
  $buf .= tlv_bytes(TAG_MAGIC, build_magic());
  $buf .= tlv_bytes(TAG_SITE, hex2bin(SITE_CODE_HEX));
  $buf .= tlv_bytes(TAG_LOCKS, $locks_bin);
  $buf .= tlv_bytes(TAG_VFROM, u64be($from_ts));
  $buf .= tlv_bytes(TAG_VTO,   u64be($to_ts));
  $buf .= tlv_string(TAG_KLABEL, $key_label);

  $sig = payload_hmac($buf, $key_bytes);
  $buf .= tlv_bytes(TAG_SIG, $sig);

  $payload_b64 = base64_encode($buf);

  respond([
    'status'      => 'success',
    'key_label'   => $key_label,
    'page_start'  => PAGE_START_DEFAULT,
    'payload_b64' => $payload_b64,
    'meta'        => $meta
  ], 200);

} catch (Throwable $e) {
  respond(['status'=>'error','message'=>'server'], 500);
}