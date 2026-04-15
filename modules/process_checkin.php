<?php
date_default_timezone_set('Africa/Lagos'); // West Africa Time (WAT)
header('Content-Type: application/json');
session_start();

require_once '../includes/db.php';
require_once 'log_room.php'; // Room audit logging
require_once '../includes/log_action.php'; // System log

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

// Capture form data
$data = $_POST;
$guest_name    = trim($data['name'] ?? '');
$guest_email   = trim($data['email'] ?? '');
$guest_phone   = trim($data['phone'] ?? '');
$id_doc        = trim($data['id_doc'] ?? '');
$room_id       = intval($data['room_id'] ?? 0);
$checkin_date  = $data['checkin_date'] ?? '';
$checkout_date = $data['checkout_date'] ?? '';

// ✅ Validate required fields
if (!$guest_name || !$guest_phone || !$id_doc || !$room_id || !$checkin_date || !$checkout_date) {
  echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
  exit;
}

// ✅ Validate date logic
if (strtotime($checkout_date) <= strtotime($checkin_date)) {
  echo json_encode(['status' => 'error', 'message' => 'Checkout date must be after check-in date.']);
  exit;
}

try {
  // ✅ Fetch room by ID and check availability
  $stmt = $conn->prepare("SELECT room_number, price FROM rooms WHERE id = ? AND status = 'available'");
  $stmt->execute([$room_id]);
  $room = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$room) {
    echo json_encode(['status' => 'error', 'message' => 'Room not available or does not exist.']);
    exit;
  }

  $room_number = $room['room_number'];
  $price = floatval($room['price']);

  // ✅ Calculate duration and cost
  $days = max(1, ceil((strtotime($checkout_date) - strtotime($checkin_date)) / 86400));
  $amount_paid = $price * $days;

  // ✅ Insert into bookings
  $stmt = $conn->prepare("
    INSERT INTO bookings 
    (room_id, guest_name, guest_email, guest_phone, id_doc, check_in_date, check_out_date, amount_paid, balance_due, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 'checked_in')
  ");
  $stmt->execute([
    $room_id, $guest_name, $guest_email, $guest_phone, $id_doc,
    $checkin_date, $checkout_date, $amount_paid
  ]);

  $booking_id = $conn->lastInsertId();
  $_SESSION['checkin_receipt'] = $booking_id;

  // ✅ Auto-generate a unique card ID
  $card_uid = strtoupper(bin2hex(random_bytes(5))); // Example: A1B2C3D4E5

  // ✅ Insert into card_queue for bridge app to write card
  $stmt = $conn->prepare("
    INSERT INTO card_queue (guest_name, room_number, card_uid, status, created_at)
    VALUES (?, ?, ?, 'pending', NOW())
  ");
  $stmt->execute([$guest_name, $room_number, $card_uid]);

  // ✅ Log payment
  $payStmt = $conn->prepare("
    INSERT INTO payments (booking_id, amount_paid, payment_method, payment_date)
    VALUES (?, ?, 'check-in', NOW())
  ");
  $payStmt->execute([$booking_id, $amount_paid]);

  // ✅ Update room to booked
  $conn->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$room_id]);

  // ✅ Logging
  log_room_action($conn, $room_id, $booking_id, 'checked_in', "Guest checked in");
  log_action("Guest $guest_name checked into Room $room_number.");

  // ✅ Return response
  echo json_encode([
    'status' => 'success',
    'receipt_id' => $booking_id,
    'card_uid' => $card_uid
  ]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => 'Check-in failed: ' . $e->getMessage()
  ]);
}