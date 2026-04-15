<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/card_payload.php';

function respond($p, $c=200){ http_response_code($c); echo json_encode($p, JSON_UNESCAPED_SLASHES); exit; }

try {
  ini_set('display_errors','0');
  error_reporting(E_ALL);

  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $lock_serial = strtoupper(trim($_POST['lock_serial'] ?? ''));
  if ($lock_serial === '') respond(['status'=>'error','message'=>'lock_serial required'], 400);

  // Must exist in DB
  $lock = find_lock_by_serial($conn, $lock_serial);
  if (!$lock) respond(['status'=>'error','message'=>'unknown lock'], 404);

  // Key must exist
  [$key_label, $key_bytes] = active_key($conn);
  if (empty($key_bytes)) respond(['status'=>'error','message'=>'no active key configured'], 500);

  // Build TLV
  $buf = '';
  $buf .= tlv_bytes(TAG_MAGIC, build_magic());

  $site = hex2bin(SITE_CODE_HEX);
  if ($site === false) respond(['status'=>'error','message'=>'invalid SITE_CODE_HEX'], 500);
  $buf .= tlv_bytes(TAG_SITE, $site);

  $buf .= tlv_bytes(TAG_LOCK_ID, u16be((int)$lock['id']));
  $buf .= tlv_string(TAG_KLABEL, $key_label);

  // Sign
  $sig = payload_hmac($buf, $key_bytes);
  $buf .= tlv_bytes(TAG_SIG, $sig);

  $payload_b64 = base64_encode($buf);

  respond([
    'status'      => 'success',
    'lock_id'     => (int)$lock['id'],
    'lock_serial' => $lock_serial,
    'key_label'   => $key_label,
    'page_start'  => PAGE_START_DEFAULT,
    'payload_b64' => $payload_b64
  ], 200);

} catch (Throwable $e) {
  error_log('system_card_payload error: '.$e->getMessage());
  respond(['status'=>'error','message'=>'server: '.$e->getMessage()], 500);
}