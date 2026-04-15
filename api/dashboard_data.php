<?php
date_default_timezone_set('Africa/Lagos'); // West Africa Time (WAT)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

// 🔐 Secure access
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    http_response_code(403);
    exit();
}

$role = $_SESSION['role'] ?? 'staff';

// -------------------------
// 📊 ROOM STATS
// -------------------------
$roomStatsStmt = $conn->query("
    SELECT 
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS booked,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
        SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) AS maintenance,
        COUNT(*) AS total
    FROM rooms
");
$roomStats = $roomStatsStmt->fetch(PDO::FETCH_ASSOC);

// 🔹 Occupancy Rate
$occupancy = ($roomStats['total'] ?? 0) > 0 
    ? round(($roomStats['booked'] / $roomStats['total']) * 100, 2)
    : 0;

// -------------------------
// 💰 REVENUE STATS (Role-Based)
// -------------------------
$revenue = [
    'daily' => 0,
    'weekly' => 0,
    'monthly' => 0,
    'yearly' => 0,
    'balance_due' => 0,
    'styles' => [
        'daily' => '',
        'weekly' => '',
        'monthly' => '',
        'yearly' => '',
        'balance_due' => ''
    ]
];

if (in_array($role, ['admin', 'manager'])) {
    $revenueStmt = $conn->query("
        SELECT 
            SUM(CASE WHEN DATE(payment_date) = CURDATE() THEN amount_paid ELSE 0 END) AS daily,
            SUM(CASE WHEN WEEK(payment_date) = WEEK(CURDATE()) THEN amount_paid ELSE 0 END) AS weekly,
            SUM(CASE WHEN MONTH(payment_date) = MONTH(CURDATE()) THEN amount_paid ELSE 0 END) AS monthly,
            SUM(CASE WHEN YEAR(payment_date) = YEAR(CURDATE()) THEN amount_paid ELSE 0 END) AS yearly
        FROM payments
    ");
    $revenueData = $revenueStmt->fetch(PDO::FETCH_ASSOC);

    $revenue['daily'] = $revenueData['daily'] ?? 0;
    $revenue['weekly'] = $revenueData['weekly'] ?? 0;
    $revenue['monthly'] = $revenueData['monthly'] ?? 0;
    $revenue['yearly'] = $role === 'admin' ? ($revenueData['yearly'] ?? 0) : 0;

    if ($revenue['daily'] > 0) $revenue['styles']['daily'] = 'color:green;font-weight:bold';
    if ($revenue['weekly'] > 0) $revenue['styles']['weekly'] = 'color:green;font-weight:bold';
    if ($revenue['monthly'] > 0) $revenue['styles']['monthly'] = 'color:green;font-weight:bold';
    if ($revenue['yearly'] > 0) $revenue['styles']['yearly'] = 'color:green;font-weight:bold';

    if ($role === 'admin') {
        $balanceDue = $conn->query("
            SELECT SUM(balance_due) 
            FROM bookings 
            WHERE status IN ('booked', 'checked_in', 'pending_balance')
        ")->fetchColumn();

        $revenue['balance_due'] = $balanceDue ?? 0;
        if ($revenue['balance_due'] > 0) {
            $revenue['styles']['balance_due'] = 'color:red;font-weight:bold';
        }
    }
}

// -------------------------
// 📋 BOOKED ROOM LIST
// -------------------------
$bookedRooms = $conn->query("
     SELECT r.room_number, b.guest_name, b.check_in_date, b.check_out_date, b.created_at
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.status IN ('booked', 'checked_in', 'pending_balance')
    ORDER BY b.check_in_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// -------------------------
// 📋 AVAILABLE ROOM LIST
// -------------------------
$availableRooms = $conn->query("
    SELECT room_number, type, price 
    FROM rooms 
    WHERE status = 'available'
    ORDER BY room_number ASC
")->fetchAll(PDO::FETCH_ASSOC);

// -------------------------
// ✅ FINAL JSON OUTPUT
// -------------------------
echo json_encode([
    'status' => 'success',
    'room_stats' => $roomStats,
    'occupancy' => $occupancy,
    'revenue' => $revenue,
    'booked_rooms' => $bookedRooms,
    'available_rooms' => $availableRooms
]);