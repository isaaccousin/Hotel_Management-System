<?php
session_start();
require_once '../includes/db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../auth/login.php");
  exit();
}

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'Room Audit Trail',
    'dashboard' => '← Return to Room Management',
    'room' => 'Room Number',
    'type' => 'Room Type',
    'price' => 'Price',
    'status' => 'Status',
    'guest' => 'Guest Name',
    'email' => 'Email',
    'phone' => 'Phone',
    'checkin' => 'Check-In',
    'checkout' => 'Check-Out',
    'days' => 'Number of Days',
    'paid' => 'Balance Paid',
    'due' => 'Balance Due',
    'action_taken' => 'Action',
    'performed_by' => 'Performed By',
    'timestamp' => 'Timestamp',
    'export' => 'Export to Excel',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout'
  ],
  'fr' => [
    'title' => 'Historique des Chambres',
    'dashboard' => '← Retour à la Gestion des Chambres',
    'room' => 'Numéro',
    'type' => 'Type de Chambre',
    'price' => 'Prix',
    'status' => 'Statut',
    'guest' => 'Nom du Client',
    'email' => 'Email',
    'phone' => 'Téléphone',
    'checkin' => 'Arrivée',
    'checkout' => 'Départ',
    'days' => 'Nombre de Jours',
    'paid' => 'Montant Payé',
    'due' => 'Solde Dû',
    'action_taken' => 'Action',
    'performed_by' => 'Effectué par',
    'timestamp' => 'Date/Heure',
    'export' => 'Exporter en Excel',
    'nav_dashboard' => 'Tableau de bord',
    'nav_checkin' => 'Enregistrement',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réserver',
    'nav_rooms' => 'Chambres',
    'nav_staff' => 'Personnel',
    'nav_logout' => 'Déconnexion'
  ]
];
$t = $texts[$lang];

try {
  $stmt = $conn->query("SELECT a.id, r.room_number, r.type, r.price, a.status, a.action_taken, a.timestamp,
                               b.check_in_date, b.check_out_date, b.guest_name AS full_name, b.guest_email AS email,
                               b.guest_phone AS phone, a.performed_by AS performed_by_id,
                               u.username AS performed_by,
                               DATEDIFF(b.check_out_date, b.check_in_date) AS number_of_days,
                               IFNULL(SUM(p.amount_paid), 0) AS balance_paid,
                               (DATEDIFF(b.check_out_date, b.check_in_date) * r.price - IFNULL(SUM(p.amount_paid), 0)) AS balance_due
                        FROM audit_rooms a
                        LEFT JOIN rooms r ON a.room_id = r.id
                        LEFT JOIN bookings b ON a.booking_id = b.id
                        LEFT JOIN users u ON a.performed_by = u.id
                        LEFT JOIN payments p ON p.booking_id = b.id AND p.refunded = 0
                        GROUP BY a.id
                        ORDER BY a.timestamp DESC");
  $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("<h3 style='color:red; text-align:center;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</h3>");
}
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet"/>
  <style>
    body {
      background: #f4f6f9;
      font-family: 'Open Sans', sans-serif;
    }
    header {
      background-color: #1a1a1a;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    .logo-nav img {
      height: 45px;
    }
    nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      padding: 6px 12px;
      font-size: 15px;
      border-radius: 5px;
      transition: background 0.3s ease;
    }
    nav a:hover {
      background-color: #333;
    }
    .container {
      max-width: 1400px;
      margin: 50px auto;
      background: #fff;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.07);
    }
    .filter-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
      align-items: center;
      justify-content: space-between;
    }
    .table th, .table td {
      text-align: center;
      vertical-align: middle !important;
    }
  </style>
</head>
<body>
<header>
  <div class="logo-nav">
    <img src="../assets/images/47.png" alt="Hotel Logo">
    <strong>47 Points Hotel</strong>
  </div>
  <nav>
    <a href="dashboard.php"><?= $t['nav_dashboard'] ?></a>
    <a href="checkin.php"><?= $t['nav_checkin'] ?></a>
    <a href="checkout.php"><?= $t['nav_checkout'] ?></a>
    <a href="booking.php"><?= $t['nav_book'] ?></a>
    <a href="room_management.php"><?= $t['nav_rooms'] ?></a>
    <a href="staff_management.php"><?= $t['nav_staff'] ?></a>
    <a href="../auth/logout.php"><?= $t['nav_logout'] ?></a>
  </nav>
</header>
<div class="container">
  <h2 class="text-center"><?= $t['title'] ?></h2>
  <div class="filter-bar">
    <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search guest/email...">
    <input type="text" id="dateRange" class="form-control" placeholder="📆 Date Range">
    <button class="btn btn-success" onclick="exportTableToExcel()">📄 <?= $t['export'] ?></button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover table-bordered">
      <thead>
        <tr>
          <th><?= $t['room'] ?></th>
          <th><?= $t['type'] ?></th>
          <th><?= $t['price'] ?></th>
          <th><?= $t['status'] ?></th>
          <th><?= $t['guest'] ?></th>
          <th><?= $t['email'] ?></th>
          <th><?= $t['phone'] ?></th>
          <th><?= $t['checkin'] ?></th>
          <th><?= $t['checkout'] ?></th>
          <th><?= $t['days'] ?></th>
          <th><?= $t['paid'] ?></th>
          <th><?= $t['due'] ?></th>
          <th><?= $t['action_taken'] ?></th>
          <th><?= $t['performed_by'] ?></th>
          <th><?= $t['timestamp'] ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($audit_logs as $log): ?>
          <tr>
            <td><?= htmlspecialchars($log['room_number'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['type'] ?? '-') ?></td>
            <td>$<?= number_format($log['price'] ?? 0, 2) ?></td>
            <td><?= htmlspecialchars($log['status'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['full_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['email'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['phone'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['check_in_date'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['check_out_date'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['number_of_days'] ?? '-') ?></td>
            <td>$<?= number_format($log['balance_paid'] ?? 0, 2) ?></td>
            <td>$<?= number_format($log['balance_due'] ?? 0, 2) ?></td>
            <td><?= htmlspecialchars($log['action_taken'] ?? '-') ?></td>
            <td><?= htmlspecialchars($log['performed_by'] ?? '-') ?></td>
            <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
  $("#searchInput").on("keyup", function () {
    const value = $(this).val().toLowerCase();
    $("table tbody tr").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });

  $('#dateRange').daterangepicker({
    autoUpdateInput: false,
    locale: { cancelLabel: 'Clear' }
  });

  $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
    const start = picker.startDate;
    const end = picker.endDate;

    $("table tbody tr").filter(function () {
      const dateStr = $(this).find("td").last().text();
      const rowDate = moment(dateStr, 'YYYY-MM-DD HH:mm:ss');
      $(this).toggle(rowDate.isBetween(start, end, undefined, '[]'));
    });
  });

  $('#dateRange').on('cancel.daterangepicker', function() {
    $(this).val('');
    $("table tbody tr").show();
  });

 function exportTableToExcel(filename = 'audit-trail.xlsx') {
  const table = document.querySelector("table");
  const wb = XLSX.utils.table_to_book(table, {sheet: "Audit"});

  // Optional: auto-width for all columns
  const ws = wb.Sheets["Audit"];
  const range = XLSX.utils.decode_range(ws['!ref']);
  const colWidths = [];
  for (let C = range.s.c; C <= range.e.c; ++C) {
    colWidths.push({ wch: 20 }); // set all columns to width 20 characters
  }
  ws['!cols'] = colWidths;

  XLSX.writeFile(wb, filename);
}

</script>

<script src="../assets/js/session_monitor.js"></script>
</body>
</html>