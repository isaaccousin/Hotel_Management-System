<?php
session_start();
require_once '../includes/db.php';
require_once 'log_room.php';
require_once '../includes/log_action.php';
require_once 'rfid.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'missing_fields' => 'Missing required fields.',
    'room_booked' => 'This room is already booked during the selected dates.',
    'room_not_exist' => 'Room does not exist.',
    'booking_success' => 'Booking recorded successfully!',
    'booking_failed' => 'Booking failed:'
  ],
  'fr' => [
    'missing_fields' => 'Champs requis manquants.',
    'room_booked' => 'Cette chambre est déjà réservée pendant les dates sélectionnées.',
    'room_not_exist' => 'La chambre n\'existe pas.',
    'booking_success' => 'Réservation enregistrée avec succès !',
    'booking_failed' => 'Échec de la réservation :'
  ]
];
$t = $texts[$lang];

// Retrieve and sanitize form input
$guest_name     = trim($_POST['guest_name'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$id_doc         = trim($_POST['id_doc'] ?? '');
$room_id        = intval($_POST['room_id'] ?? 0);
$checkin_date   = $_POST['checkin_date'] ?? '';
$checkout_date  = $_POST['checkout_date'] ?? '';
$amount_paid    = floatval($_POST['amount_paid'] ?? 0.0);
$payment_method = 'booking';

// Validate required fields
if (!$guest_name || !$phone || !$id_doc || !$room_id || !$checkin_date || !$checkout_date) {
  $_SESSION['booking_error'] = $t['missing_fields'];
  header("Location: booking.php");
  exit();
}

try {
  // Prevent double booking
  $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings
    WHERE room_id = ?
    AND status IN ('booked', 'checked_in')
    AND (
      (check_in_date <= ? AND check_out_date > ?) OR
      (check_in_date < ? AND check_out_date >= ?) OR
      (? <= check_in_date AND ? >= check_out_date)
    )");
  $stmt->execute([
    $room_id,
    $checkin_date, $checkin_date,
    $checkout_date, $checkout_date,
    $checkin_date, $checkout_date
  ]);
  if ($stmt->fetchColumn() > 0) {
    $_SESSION['booking_error'] = $t['room_booked'];
    header("Location: booking.php");
    exit();
  }

  // Fetch room details
  $stmt = $conn->prepare("SELECT room_number, price FROM rooms WHERE id = ?");
  $stmt->execute([$room_id]);
  $room = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$room) {
    $_SESSION['booking_error'] = $t['room_not_exist'];
    header("Location: booking.php");
    exit();
  }

  $room_number = $room['room_number'];
  $price = floatval($room['price']);

  // Calculate booking duration and balance
  $days = max(1, ceil((strtotime($checkout_date) - strtotime($checkin_date)) / 86400));
  $total_cost = $price * $days;
  $balance_due = max(0, $total_cost - $amount_paid);

  // Insert booking
  $stmt = $conn->prepare("INSERT INTO bookings
    (guest_name, guest_email, guest_phone, id_doc, room_id, check_in_date, check_out_date, amount_paid, balance_due, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'booked')");
  $stmt->execute([
    $guest_name, $email, $phone, $id_doc,
    $room_id, $checkin_date, $checkout_date,
    $amount_paid, $balance_due
  ]);

  $booking_id = $conn->lastInsertId();

 // ====== ISSUE/PROGRAM THE CARD IF A SCAN IS ALREADY AVAILABLE ======
try {
  // Try to grab the newest unused scan (works with your latest_uid.php logic)
  $q = $conn->query("
      SELECT id, card_uid 
      FROM card_queue 
      WHERE status IS NULL OR status = '' OR status = 'pending' 
      ORDER BY id DESC LIMIT 1
  ");
  $row = $q->fetch(PDO::FETCH_ASSOC);

  if ($row && !empty($row['card_uid'])) {
    $uid = strtoupper($row['card_uid']);

    // Bind the card to this booking + room (valid for stay window)
    rfid_bind_card(
      $conn,
      (int)$booking_id,
      (int)$room_id,
      $uid,
      date('Y-m-d 00:00:00', strtotime($checkin_date)),
      date('Y-m-d 23:59:59', strtotime($checkout_date))
    );

    // Mark that queue row as used so we don't reuse the same scan
    rfid_consume_queue($conn, (int)$row['id']);

    log_room_action($conn, $room_id, $booking_id, 'card_issued', "Card $uid issued ($checkin_date → $checkout_date).");
    $_SESSION['card_uid_info'] = $uid; // optional: show on success toast
  } else {
    // No scan yet → front desk can click “Program Card” later
    $_SESSION['card_uid_info'] = null;
  }
} catch (Exception $ex) {
  error_log('RFID issue error: ' . $ex->getMessage());
  // Don’t fail the whole booking if card issuing hiccups—front desk can retry
}


  // Log booking action
  log_room_action($conn, $room_id, $booking_id, 'booked', "Guest booked room");
  log_action("Booking", "Guest $guest_name booked Room $room_number.");

  // Mark room as booked
  $conn->prepare("UPDATE rooms SET status = 'booked' WHERE id = ?")->execute([$room_id]);

  // Log payment
  if ($amount_paid > 0) {
    $conn->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, payment_date)
                    VALUES (?, ?, ?, NOW())")
         ->execute([$booking_id, $amount_paid, $payment_method]);
  }

  // ✅ Finalize and redirect with success
  $_SESSION['booking_success'] = $t['booking_success'];
  $_SESSION['booking_receipt'] = $booking_id;
  header("Location: booking.php");
  exit();

} catch (Exception $e) {
  error_log("Booking error: " . $e->getMessage());
  $_SESSION['booking_error'] = $t['booking_failed'] . ' ' . $e->getMessage();
  header("Location: booking.php");
  exit();
}