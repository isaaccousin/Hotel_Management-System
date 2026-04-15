<?php 
date_default_timezone_set('Africa/Lagos');
session_start();

require_once '../includes/db.php';
require_once 'log_room.php';
require_once '../includes/log_action.php';

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'must_pay' => 'Balance must be fully paid before checkout.',
    'invalid' => 'Invalid booking or status.',
    'success' => 'Guest checked out successfully!',
    'error' => 'Checkout failed.'
  ],
  'fr' => [
    'must_pay' => 'Le solde doit être entièrement payé avant le départ.',
    'invalid' => 'Réservation ou statut invalide.',
    'success' => 'Client enregistré comme parti avec succès !',
    'error' => 'Échec du départ.'
  ]
];
$t = $texts[$lang];

// Block if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $booking_id = intval($_POST['booking_id'] ?? 0);
  $payment_amount = floatval($_POST['payment_amount'] ?? 0);

  // Fetch booking and room info
  $stmt = $conn->prepare("
    SELECT b.guest_name, b.room_id, b.balance_due, b.status,
           r.id AS room_id, r.room_number
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ?
  ");
  $stmt->execute([$booking_id]);
  $booking = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($booking && in_array($booking['status'], ['checked_in', 'booked'])) {
    $balance_due = floatval($booking['balance_due']);

    // Must pay exact amount if balance is due
    if ($balance_due > 0) {
      if (abs($payment_amount - $balance_due) > 0.001) {
        $_SESSION['checkout_error'] = $t['must_pay'];
        header("Location: checkout.php");
        exit();
      }

      // Record payment
      $conn->prepare("
        INSERT INTO payments (booking_id, amount_paid, payment_method, payment_date)
        VALUES (?, ?, 'checkout', NOW())
      ")->execute([$booking_id, $payment_amount]);

      // Set balance to zero
      $conn->prepare("UPDATE bookings SET balance_due = 0 WHERE id = ?")
            ->execute([$booking_id]);
    }

    // Finalize checkout
    $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE id = ?")
          ->execute([$booking_id]);

    $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?")
          ->execute([$booking['room_id']]);

    // Log actions
    log_room_action($conn, $booking['room_id'], $booking_id, 'checked_out', 'Guest checked out');
    log_action('Checkout', "{$booking['guest_name']} checked out from Room {$booking['room_number']} (Booking ID $booking_id)");

    $_SESSION['checkout_success'] = $t['success'];
    $_SESSION['checkout_receipt'] = $booking_id;
    header("Location: checkout.php");
    exit();
  }

  $_SESSION['checkout_error'] = $t['invalid'];
  header("Location: checkout.php");
  exit();
}