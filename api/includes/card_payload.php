<?php
declare(strict_types=1);

/**
 * Shared TLV + HMAC payload builder for card programming.
 * Requires a PDO $conn from ../includes/db.php
 */

const SITE_CODE_HEX = '5331'; // "S1" (2 bytes) — change for your site
const PAGE_START_DEFAULT = 4; // Ultralight/NTAG: start writing at page 4

// TLV tags
const TAG_MAGIC    = 0x10; // "SPK1" (4 bytes)
const TAG_SITE     = 0x11; // site code (2 bytes)
const TAG_LOCK_ID  = 0x12; // u16 lock id (system card)
const TAG_LOCKS    = 0x13; // list of u16 lock ids (access card)
const TAG_VFROM    = 0x14; // valid_from (u64be epoch seconds)
const TAG_VTO      = 0x15; // valid_to   (u64be epoch seconds)
const TAG_KLABEL   = 0x16; // key_label (ascii)
const TAG_SIG      = 0xFE; // HMAC-SHA256 (32 bytes)

// ---- helpers ----
function hex_to_bytes(string $hex): string {
  $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex) ?? '';
  if ($hex === '' || (strlen($hex) % 2) !== 0) return '';
  return hex2bin($hex) ?: '';
}

function bytes_to_hex(string $bin): string {
  return strtoupper(bin2hex($bin));
}

function u16be(int $n): string {
  if ($n < 0 || $n > 0xFFFF) throw new InvalidArgumentException("u16 range");
  return pack('n', $n);
}

function u64be(int $ts): string {
  $hi = ($ts >> 32) & 0xFFFFFFFF;
  $lo =  $ts        & 0xFFFFFFFF;
  return pack('N2', $hi, $lo);
}

function tlv_bytes(int $tag, string $value): string {
  $len = strlen($value);
  if ($len > 65535) throw new InvalidArgumentException("TLV too long");
  return chr($tag) . pack('n', $len) . $value;
}

function tlv_string(int $tag, string $ascii): string {
  return tlv_bytes($tag, $ascii);
}

function active_key(PDO $conn): array {
  $row = $conn->query("SELECT key_label, key_bytes FROM card_keys WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new RuntimeException("No active card key");
  return [$row['key_label'], $row['key_bytes']];
}

function payload_hmac(string $data, string $key_bytes): string {
  return hash_hmac('sha256', $data, $key_bytes, true);
}

function payload_sha256(string $data): string {
  return hash('sha256', $data, true);
}

function build_magic(): string {
  return "SPK1"; // 0x53 0x50 0x4B 0x31
}

// Expand group_code -> array of lock ids
function lock_ids_for_group(PDO $conn, string $group_code): array {
  $stmt = $conn->prepare("
    SELECT lgm.lock_id
    FROM lock_groups lg
    JOIN lock_group_members lgm ON lgm.group_id = lg.id
    WHERE lg.code = :c
    ORDER BY lgm.lock_id ASC
  ");
  $stmt->execute([':c' => $group_code]);
  $ids = [];
  while ($r = $stmt->fetch(PDO::FETCH_NUM)) $ids[] = (int)$r[0];
  return $ids;
}

function find_lock_by_serial(PDO $conn, string $lock_serial_hex): ?array {
  $b = hex_to_bytes($lock_serial_hex);
  if ($b === '') return null;
  $stmt = $conn->prepare("SELECT id, name, location FROM locks WHERE lock_serial = :s LIMIT 1");
  $stmt->execute([':s' => $b]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  return $r ?: null;
}