<?php

require_once '../includes/db.php';
require_once '../auth/session_check.php';

// Only allow access if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}


$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'System Logs',
    'logs' => 'System Logs',
    'by' => 'By',
    'at' => 'At',
    'desc' => 'Description',
    'id' => 'ID',
    'search' => '🔍 Search logs...',
    'export' => 'Export to Excel',
    'back' => '← Back to Dashboard',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout'
  ],
  'fr' => [
    'title' => 'Journaux Système',
    'logs' => 'Journaux Système',
    'by' => 'Par',
    'at' => 'À',
    'desc' => 'Description',
    'id' => 'ID',
    'search' => '🔍 Rechercher dans les journaux...',
    'export' => 'Exporter vers Excel',
    'back' => '← Retour au Tableau de Bord',
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

$logs = $conn->query("SELECT l.*, u.username FROM system_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.log_time DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body { background: #f9f6f2; font-family: 'Open Sans', sans-serif; }
    header {
      background-color: #1a1a1a;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    .logo-nav {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logo-nav img {
      height: 45px;
    }
    nav {
      display: flex;
      gap: 12px;
      margin-top: 10px;
      flex-wrap: wrap;
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
    .hamburger {
      display: none;
      font-size: 26px;
      cursor: pointer;
    }
    @media screen and (max-width: 768px) {
      nav {
        flex-direction: column;
        display: none;
        width: 100%;
      }
      nav.show {
        display: flex;
      }
      .hamburger {
        display: block;
      }
    }
    .container { max-width: 1000px; margin: auto; padding: 40px 20px; }
    h2 { font-family: 'Playfair Display', serif; margin-bottom: 25px; text-align: center; }
    table th { background-color: #eaeaea; }
    .btn-back, .btn-export {
      background-color: #d4af37;
      color: #1a1a1a;
      border: none;
    }
    .btn-back:hover, .btn-export:hover {
      background-color: #c1a232;
    }
    #searchBox {
      margin-bottom: 15px;
    }
  </style>
</head>
<body>
<header>
  <div class="logo-nav">
    <img src="../assets/images/47.png" alt="Hotel Logo">
    <strong>47 Points Hotel</strong>
    <div class="hamburger" onclick="toggleMenu()">☰</div>
  </div>
  <nav id="mainNav">
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
  <h2 class="mt-4"><?= $t['logs'] ?></h2>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="dashboard.php" class="btn btn-back"><?= $t['back'] ?></a>
    <button class="btn btn-export" onclick="exportToExcel()"><?= $t['export'] ?></button>
  </div>
  <input type="text" id="searchBox" class="form-control" placeholder="<?= $t['search'] ?>">
  <table class="table table-bordered table-striped bg-white" id="logTable">
    <thead>
      <tr>
        <th><?= $t['id'] ?></th>
        <th><?= $t['desc'] ?></th>
        <th><?= $t['by'] ?></th>
        <th><?= $t['at'] ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?= $log['id'] ?></td>
          <td><?= htmlspecialchars($log['description']) ?></td>
          <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
          <td><?= $log['log_time'] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
  function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('show');
  }
  $('#searchBox').on('input', function () {
    const val = $(this).val().toLowerCase();
    $('#logTable tbody tr').each(function () {
      const rowText = $(this).text().toLowerCase();
      $(this).toggle(rowText.indexOf(val) !== -1);
    });
  });
  function exportToExcel() {
    const tableHTML = document.getElementById('logTable').outerHTML.replace(/ /g, '%20');
    const downloadLink = document.createElement("a");
    downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
    downloadLink.download = 'system_logs.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  }
</script>

<script src="../assets/js/session_monitor.js"></script>


</body>
</html>