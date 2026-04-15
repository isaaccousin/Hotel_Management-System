<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Will save error to this file



date_default_timezone_set('Africa/Lagos');
session_start();
require_once '../includes/db.php';
require_once '../modules/log_room.php';
require_once '../includes/log_action.php';
require_once '../modules/rfid.php';





if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = intval($input['id'] ?? 0);


function record_payment($conn, $bookingId, $amount, $method) {
    // Allow both positive (payment) and negative (refund)
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, payment_date) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$bookingId, $amount, $method]);
}



function update_revenue_after_refund($conn, $refundAmount) {
    $stmt = $conn->prepare("
        UPDATE revenue_stats
        SET 
            daily = daily - ?,
            weekly = weekly - ?,
            monthly = monthly - ?,
            yearly = yearly - ?,
            balance_due = balance_due + ?
        WHERE id = 1
    ");
    $stmt->execute([$refundAmount, $refundAmount, $refundAmount, $refundAmount, $refundAmount]);
}





// === ADD ROOM ===
if ($action === 'add') {
    $room_number = trim($input['room_number'] ?? '');
    $type = trim($input['type'] ?? '');
    $price = floatval($input['price'] ?? 0);
    $amenities = trim($input['amenities'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if (!$room_number || !$type || $price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit();
    }

    try {
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND status != 'deleted'");
        $checkStmt->execute([$room_number]);
        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Room number already exists.']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO rooms (room_number, type, price, amenities, notes, status) VALUES (?, ?, ?, ?, ?, 'available')");
        $stmt->execute([$room_number, $type, $price, $amenities, $notes]);
        $room_id = $conn->lastInsertId();

        log_room_action($conn, $room_id, null, 'available', 'Room created');
        echo json_encode(['status' => 'success', 'message' => 'Room created successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// === EDIT ROOM ===
if ($action === 'edit') {
    $type = trim($input['type'] ?? '');
    $price = floatval($input['price'] ?? 0);

    if (!$type || $price <= 0 || !$id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid room details.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE rooms SET type = ?, price = ? WHERE id = ?");
        $stmt->execute([$type, $price, $id]);

        $stmt = $conn->prepare("SELECT * FROM bookings WHERE room_id = ? AND status IN ('booked', 'checked_in') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        $logMessage = "Room edited. Type: $type, Price: $$price";
        $bookingId = null;

        if ($booking) {
            $bookingId = $booking['id'];
            $checkin = new DateTime($booking['check_in_date']);
            $checkout = new DateTime($booking['check_out_date']);
            $days = max(1, $checkin->diff($checkout)->days);

            $newTotal = $days * $price;
            $paid = floatval($booking['amount_paid'] ?? 0);
            $newBalance = $newTotal - $paid;

            $stmt = $conn->prepare("UPDATE bookings SET price = ?, balance_due = ? WHERE id = ?");
            $stmt->execute([$price, $newBalance, $bookingId]);

            $logMessage .= ". Booking ID: $bookingId. Balance updated to $$newBalance";
        } else {
            $logMessage .= " (No active booking)";
        }

        log_room_action($conn, $id, $bookingId, 'edited', $logMessage);
        echo json_encode(['status' => 'success', 'message' => $logMessage]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Edit failed: ' . $e->getMessage()]);
    }
    exit();
}


// === DELETE ROOM ===
if ($action === 'delete') {
    try {
        // Optional: Check if the room is not currently booked or checked in
        $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('booked', 'checked_in')");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete a room with active bookings.']);
            exit();
        }

        // Soft delete: just mark status as 'deleted'
        $stmt = $conn->prepare("UPDATE rooms SET status = 'deleted' WHERE id = ?");
        $stmt->execute([$id]);

        log_room_action($conn, $id, null, 'deleted', "Room marked as deleted.");
        echo json_encode(['status' => 'success', 'message' => 'Room deleted successfully.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $e->getMessage()]);
    }
    exit();
}




// === EXTEND DATE WITH PARTIAL PAYMENT SUPPORT ===
if ($action === 'extend_date') {
    $newDate = $input['new_date'] ?? '';
    $pricePerDay = floatval($input['price'] ?? 0);
    $amountPaid = floatval($input['amount_paid'] ?? 0);
    $paymentMode = $input['payment_mode'] ?? 'later';

    try {
        // Get current booking
        $stmt = $conn->prepare("SELECT id, check_out_date FROM bookings 
                                WHERE room_id = ? AND status IN ('booked', 'checked_in') 
                                ORDER BY id DESC LIMIT 1");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception('Booking not found.');
        }

        $bookingId = $booking['id'];
        $currentDate = new DateTime($booking['check_out_date']);
        $newDateObj = new DateTime($newDate);
        rfid_extend_card($conn, $bookingId, $id /* room_id */, $newDate);
        log_room_action($conn, $id, $bookingId, 'card_extended', "RFID validity extended to $newDate");

        if ($newDateObj <= $currentDate) {
            throw new Exception('New date must be after current checkout.');
        }

        $daysExtended = $currentDate->diff($newDateObj)->days;
        $extraCharge = $daysExtended * $pricePerDay;
        $remainingBalance = max(0, $extraCharge - $amountPaid);

        // === 1. Update booking
        if ($remainingBalance > 0) {
            $stmt = $conn->prepare("UPDATE bookings 
                SET check_out_date = ?, 
                    balance_due = balance_due + ?, 
                    amount_paid = amount_paid + ? 
                WHERE id = ?");
            $stmt->execute([$newDate, $remainingBalance, $amountPaid, $bookingId]);
        } else {
            $stmt = $conn->prepare("UPDATE bookings 
                SET check_out_date = ?, 
                    amount_paid = amount_paid + ? 
                WHERE id = ?");
            $stmt->execute([$newDate, $amountPaid, $bookingId]);
        }

        // === 2. Record payment (if any)
        if ($amountPaid > 0) {
            $stmt = $conn->prepare("INSERT INTO payments 
                (booking_id, amount_paid, payment_method, payment_date) 
                VALUES (?, ?, 'extend', NOW())");
            $stmt->execute([$bookingId, $amountPaid]);
        }

        // === 3. Log action
        $logMessage = "Checkout extended to $newDate. Total: $$extraCharge.";
        if ($amountPaid > 0 && $remainingBalance > 0) {
            $logMessage .= " Paid $$amountPaid, balance $$remainingBalance.";
        } elseif ($amountPaid >= $extraCharge) {
            $logMessage .= " Paid in full.";
        } else {
            $logMessage .= " Payment deferred.";
        }

        log_room_action($conn, $id, $bookingId, 'booked', $logMessage);
        log_action('Extend Stay', "Room ID $id, Booking ID $bookingId: Extended to $newDate. Paid: $$amountPaid, Remaining: $$remainingBalance.");


        echo json_encode([
            'status' => 'success',
            'message' => "Extended by $daysExtended day(s). Total: $$extraCharge.",
            'receipt_id' => $bookingId,
            'pay_later' => ($paymentMode === 'later'),
            'partial_payment' => ($amountPaid > 0 && $remainingBalance > 0)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Extension failed: ' . $e->getMessage()]);
    }
    exit();
}







// === REFUND 75% ===
if ($action === 'refund') {
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT id, check_in_date, check_out_date FROM bookings WHERE room_id = ? AND status IN ('booked', 'checked_in')");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking || empty($booking['check_in_date']) || empty($booking['check_out_date'])) {
            throw new Exception("Booking data missing or incomplete.");
        }

        $bookingId = $booking['id'];

        $roomStmt = $conn->prepare("SELECT price FROM rooms WHERE id = ?");
        $roomStmt->execute([$id]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

        if (!$room || !isset($room['price'])) {
            throw new Exception("Room data missing.");
        }

        $checkIn = new DateTime($booking['check_in_date']);
        $checkOut = new DateTime($booking['check_out_date']);
        $today = new DateTime();

        $effectiveToday = ($today < $checkOut) ? $today : $checkOut;

        $totalDays = max(1, $checkIn->diff($checkOut)->days);
        $daysUsed = max(0, $checkIn->diff($effectiveToday)->days);
        $daysLeft = max(0, $totalDays - $daysUsed);

        if ($daysLeft <= 0) {
            $conn->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Refund not allowed. The guest has already completed their stay.']);
            exit();
        }

        $pricePerDay = floatval($room['price']);
        $totalLeftAmount = $daysLeft * $pricePerDay;
        $refundAmount = $totalLeftAmount * 0.75;
        $retainedRevenue = $totalLeftAmount * 0.25;

        // Log retained and refund amounts
        record_payment($conn, $bookingId, $retainedRevenue, 'retained-refund');
        record_payment($conn, $bookingId, -1 * $refundAmount, 'refund');
        update_revenue_after_refund($conn, $refundAmount);


        // Set booking and room status
        $stmt = $conn->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ?");
        $stmt->execute([$bookingId]);

        $stmt = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt->execute([$id]);

        log_room_action($conn, $id, $bookingId, 'refunded', "Refunded 75%: $$refundAmount for $daysLeft day(s). Retained: $$retainedRevenue");
        log_action('Refund', "Room ID $id, Booking ID $bookingId: Refunded 75% = $$refundAmount for $daysLeft day(s).");
        error_log("REFUND triggered for room ID: " . $id);


        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "Refunded 75% of remaining $daysLeft day(s): $$refundAmount",
            'receipt_id' => $bookingId
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Refund Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Refund failed: ' . $e->getMessage()]);
    }
    exit();
}



if ($action === 'refund_partial') {
    $amount = floatval($input['amount'] ?? 0);
    if ($amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid refund amount.']);
        exit();
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT id, check_in_date, check_out_date FROM bookings WHERE room_id = ? AND status IN ('booked', 'checked_in') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking || empty($booking['check_in_date']) || empty($booking['check_out_date'])) {
            throw new Exception("No active booking found or booking data incomplete.");
        }

        $bookingId = $booking['id'];
        $checkIn = new DateTime($booking['check_in_date']);
        $checkOut = new DateTime($booking['check_out_date']);
        $today = new DateTime();
        $effectiveToday = ($today < $checkOut) ? $today : $checkOut;

        $totalDays = max(1, $checkIn->diff($checkOut)->days);
        $daysUsed = max(0, $checkIn->diff($effectiveToday)->days);
        $daysLeft = max(0, $totalDays - $daysUsed);

        if ($daysLeft <= 0) {
            throw new Exception("No refundable days remaining.");
        }

        // Get price per day
        $roomStmt = $conn->prepare("SELECT price FROM rooms WHERE id = ?");
        $roomStmt->execute([$id]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
        $pricePerDay = floatval($room['price'] ?? 0);
        if ($pricePerDay <= 0) {
            throw new Exception("Room price not found.");
        }

        $maxRefundable = $daysLeft * $pricePerDay;
        if ($amount > $maxRefundable) {
            throw new Exception("Refund amount exceeds refundable value: $$maxRefundable");
        }

        // Record refund and update statuses
        record_payment($conn, $bookingId, -1 * $amount, 'partial-refund');
        update_revenue_after_refund($conn, $amount);

        $stmt = $conn->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ?");
        $stmt->execute([$bookingId]);

        $stmt = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt->execute([$id]);

        log_room_action($conn, $id, $bookingId, 'refunded', "Partial refund: $$amount for $daysLeft unused day(s)");
        log_action('Partial Refund', "Room $id, Booking $bookingId: $$amount refunded.");
        error_log("Partial refund executed for room $id");

        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "Partial refund of $$amount for $daysLeft unused day(s) processed.",
            'receipt_id' => $bookingId
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Partial Refund Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Partial refund failed: ' . $e->getMessage()]);
    }
    exit();
}








// === PAYMENT TOWARD BALANCE ===
if ($action === 'pay_balance') {
    $amountPaid = floatval($input['amount_paid'] ?? 0);
    if ($amountPaid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment amount.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT id, balance_due FROM bookings WHERE room_id = ? AND status IN ('booked', 'checked_in')");
        $stmt->execute([$id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            throw new Exception('No active booking found.');
        }

        $bookingId = $booking['id'];
        $currentDue = floatval($booking['balance_due']);
        $newDue = max(0, $currentDue - $amountPaid);

        $stmt = $conn->prepare("UPDATE bookings SET amount_paid = amount_paid + ?, balance_due = ? WHERE id = ?");
        $stmt->execute([$amountPaid, $newDue, $bookingId]);

        $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount_paid, payment_method, payment_date) VALUES (?, ?, 'balance', NOW())");
        $stmt->execute([$bookingId, $amountPaid]);

        log_room_action($conn, $id, $bookingId, 'payment', "Paid $$amountPaid toward balance. New balance: $$newDue");

        echo json_encode(['status' => 'success', 'message' => "Payment of $$amountPaid recorded.", 'receipt_id' => $bookingId]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Payment failed: ' . $e->getMessage()]);
    }
    exit();
}



// === CHECKOUT ===
if ($action === 'checkout') {
    $conn->beginTransaction();
    try {
        // Update room status
        $stmt1 = $conn->prepare("UPDATE rooms SET status = 'available' WHERE id = ?");
        $stmt1->execute([$id]);

        // Update booking status
        $stmt2 = $conn->prepare("UPDATE bookings SET status = 'checked_out' WHERE room_id = ? AND status IN ('booked', 'checked_in')");
        $stmt2->execute([$id]);

        // Fetch the most recent booking ID for logging
        $stmt3 = $conn->prepare("SELECT id FROM bookings WHERE room_id = ? ORDER BY id DESC LIMIT 1");
        $stmt3->execute([$id]);
        $booking = $stmt3->fetch(PDO::FETCH_ASSOC);
        $bookingId = $booking['id'] ?? null;
        rfid_revoke_cards($conn, $id /* room_id */, $id /* room_id again */); // uses bookingId+roomId
        rfid_revoke_cards($conn, $bookingId, $id);


        // ✅ Audit logs
        log_room_action($conn, $id, $bookingId, 'checked_out', "Room checked out and marked available.");
        log_action('Checkout', "Room ID $id, Booking ID $bookingId checked out.");
        log_room_action($conn, $id, $bookingId, 'card_revoked', "RFID card(s) revoked at checkout.");

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Room checked out successfully.']);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Failed to checkout room.']);
    }
    exit();
}

$statusMap = [
    'set_available' => 'available',
    'set_booked' => 'booked',
    'set_maintenance' => 'maintenance',
    'restore' => 'available',
    'restore_maintenance' => 'available',
    'collect' => 'available'
];

if (array_key_exists($action, $statusMap)) {
    $newStatus = $statusMap[$action];
    $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    log_room_action($conn, $id, null, $newStatus, "Room status set to $newStatus");
    echo json_encode(['status' => 'success', 'message' => "Room status updated to $newStatus"]);
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
exit();