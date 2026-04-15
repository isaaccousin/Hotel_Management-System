<?php 
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

$rooms = [];

try {
  $sql = "
    SELECT 
      r.id, 
      r.room_number, 
      r.type, 
      r.price, 
      r.status AS original_status,
      COALESCE(b.check_out_date, NULL) AS check_out_date,
      COALESCE(b.balance_due, 0) AS balance_due
    FROM rooms r
    LEFT JOIN (
      SELECT 
        room_id, 
        MAX(check_out_date) AS check_out_date, 
        SUM(balance_due) AS balance_due
      FROM bookings
      WHERE status IN ('booked', 'pending_balance', 'checked_in')
      GROUP BY room_id
    ) b ON r.id = b.room_id
    WHERE r.status != 'deleted'
    ORDER BY CAST(r.room_number AS UNSIGNED)
  ";

  $stmt = $conn->prepare($sql);
  $stmt->execute();

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // ✅ Status decision must be inside the loop
    $status = ($row['balance_due'] > 0 && in_array($row['original_status'], ['booked', 'checked_in'])) 
      ? 'pending_balance' 
      : $row['original_status'];

    $rooms[] = [
      'id' => $row['id'],
      'room_number' => $row['room_number'],
      'type' => $row['type'],
      'price' => $row['price'],
      'status' => $status,
      'check_out_date' => $row['check_out_date'],
      'balance_due' => $row['balance_due']
    ];
  }

  echo json_encode(['status' => 'success', 'rooms' => $rooms]);

} catch (PDOException $e) {
  echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
exit();