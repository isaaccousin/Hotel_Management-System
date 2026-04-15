<?php
require_once '../includes/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

$roomId = intval($_GET['id'] ?? 0);
$status = $_GET['status'] ?? 'booked';

// Allow only expected values
$allowedStatuses = ['booked', 'checked_in', 'both'];
if (!in_array($status, $allowedStatuses)) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid status parameter']);
  exit();
}

// Build status filter dynamically
if ($status === 'both') {
  $statusClause = "IN ('booked', 'checked_in')";
} else {
  $statusClause = "= '$status'";
}

$stmt = $conn->prepare("
  SELECT 
    b.id AS booking_id, 
    b.check_in_date, 
    b.check_out_date, 
    b.amount_paid, 
    b.balance_due,
    r.price
  FROM bookings b
  JOIN rooms r ON r.id = b.room_id
  WHERE b.room_id = ? AND b.status $statusClause
  ORDER BY b.id DESC
  LIMIT 1
");
$stmt->execute([$roomId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($booking) {
  echo json_encode([
    'status' => 'success',
    'booking' => [
      'booking_id'     => $booking['booking_id'],
      'check_in_date'  => $booking['check_in_date'],
      'check_out_date' => $booking['check_out_date'],
      'price'          => $booking['price'],
      'amount_paid'    => $booking['amount_paid'],
      'balance_due'    => $booking['balance_due']
    ]
  ]);
} else {
  echo json_encode(['status' => 'error', 'message' => 'Booking not found']);
}
exit();