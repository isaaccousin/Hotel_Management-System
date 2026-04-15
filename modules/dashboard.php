<?php

require_once '../auth/session_check.php';

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => '47 Points Hotel',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout',
    'room_stats' => 'Room Stats',
    'booked' => 'Booked',
    'available' => 'Available',
    'maintenance' => 'Maintenance',
    'revenue_stats' => 'Revenue Stats',
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly',
    'balance_due' => 'Balance Due',
    'occupancy' => 'Occupancy Rate',
    'booked_rooms' => '📋 Booked Rooms',
    'available_rooms' => '📋 Available Rooms',
    'search_guest' => '🔍 Search guest or room...',
    'search_available' => '🔍 Search room or type...',
    'export' => 'Export to Excel',
    'room' => 'Room',
    'guest' => 'Guest',
    'checkin' => 'Check-In',
    'checkout' => 'Check-Out',
    'type' => 'Type',
    'price' => 'Price',
    'restricted' => 'Access Restricted'
  ],
  'fr' => [
    'title' => 'Hôtel 47 Points',
    'nav_dashboard' => 'Tableau de bord',
    'nav_checkin' => 'Arrivée',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réserver',
    'nav_rooms' => 'Gestion des chambres',
    'nav_staff' => 'Gestion du personnel',
    'nav_logout' => 'Déconnexion',
    'room_stats' => 'Statistiques des chambres',
    'booked' => 'Occupée',
    'available' => 'Disponibles',
    'maintenance' => 'En maintenance',
    'revenue_stats' => 'Statistiques des revenus',
    'daily' => 'Quotidien',
    'weekly' => 'Hebdomadaire',
    'monthly' => 'Mensuel',
    'yearly' => 'Annuel',
    'balance_due' => 'Solde dû',
    'occupancy' => 'Taux d’occupation',
    'booked_rooms' => '📋 Chambres occupée',
    'available_rooms' => '📋 Chambres disponibles',
    'search_guest' => '🔍 Rechercher un client ou une chambre...',
    'search_available' => '🔍 Rechercher une chambre ou un type...',
    'export' => 'Exporter vers Excel',
    'room' => 'Chambre',
    'guest' => 'Client',
    'checkin' => 'Arrivée',
    'checkout' => 'Départ',
    'type' => 'Type',
    'price' => 'Prix',
    'restricted' => 'Accès restreint'
  ]
];
$t = $texts[$lang];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Playfair+Display&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      margin: 0;
      background: #f9f6f2;
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

    .logout {
      font-size: 15px;
      padding: 6px 14px;
      border-radius: 6px;
    }

    .container {
      padding: 30px;
      max-width: 1200px;
      margin: auto;
    }

    .card {
      background: white;
      border: 2px solid #d4af37;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }

    .card h3 {
      margin-top: 0;
      font-family: 'Playfair Display', serif;
      color: #333;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    canvas { max-width: 100%; }

    /* NEW: Hamburger and responsive nav */
    .hamburger {
      display: none;
      font-size: 24px;
      cursor: pointer;
      margin-left: auto;
    }

    @media screen and (max-width: 768px) {
      .hamburger {
        display: block;
      }

      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background-color: #1a1a1a;
        padding: 10px 0;
      }

      nav.show {
        display: flex;
      }

      nav a {
        border-top: 1px solid #333;
        padding: 12px;
      }

      input[type="text"], button {
        font-size: 16px;
      }
      
      

    }
    
    
    
    .dropdown {
  position: relative;
  display: inline-block;
}

.dropbtn {
  background-color: #1a1a1a;
  color: white;
  padding: 6px 14px;
  font-size: 15px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  border-radius: 5px;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #333;
  min-width: 160px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.2);
  z-index: 1;
  border-radius: 6px;
  overflow: hidden;
}

.dropdown-content a {
  color: white;
  padding: 10px 14px;
  text-decoration: none;
  display: block;
  font-size: 14px;
}

.dropdown-content a:hover {
  background-color: #555;
}

.dropdown:hover .dropdown-content {
  display: block;
}

.dropdown:hover .dropbtn {
  background-color: #333;
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
   
   <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
  <a href="room_management.php"><?= $t['nav_rooms'] ?></a>
<?php endif; ?>

  <?php if ($_SESSION['role'] === 'admin'): ?>
  <a href="staff_management.php"><?= $t['nav_staff'] ?></a>
<?php endif; ?>
 
   
  
   <div class="dropdown">
  <button class="dropbtn"><?= $t['nav_logout'] ?> ▼</button>
  <div class="dropdown-content">
    <a href="../auth/change_password.php">🔐 Change Password</a>
    <a href="../auth/logout.php">🚪 <?= $t['nav_logout'] ?></a>
  </div>
</div>

  </nav>
</header>

<div id="pageContent" class="container">
  <div class="stats-grid">
    <div class="card">
      <h3><?= $t['room_stats'] ?></h3>
      <p><strong><?= $t['booked'] ?>:</strong> <span id="booked_count">0</span></p>
      <p><strong><?= $t['available'] ?>:</strong> <span id="available_count">0</span></p>
      <p><strong><?= $t['maintenance'] ?>:</strong> <span id="maintenance_count">0</span></p>
    </div>
    <div class="card" id="revenue_card">
      <h3><?= $t['revenue_stats'] ?></h3>
      <p><strong><?= $t['daily'] ?>:</strong> $<span id="rev_day">0.00</span></p>
      <p><strong><?= $t['weekly'] ?>:</strong> $<span id="rev_week">0.00</span></p>
      <p><strong><?= $t['monthly'] ?>:</strong> $<span id="rev_month">0.00</span></p>
      <p id="rev_year_row"><strong><?= $t['yearly'] ?>:</strong> $<span id="rev_year">0.00</span></p>
      <p id="rev_due_row"><strong><?= $t['balance_due'] ?>:</strong> $<span id="rev_due">0.00</span></p>
    </div>
    <div class="card">
      <h3><?= $t['occupancy'] ?></h3>
      <canvas id="occupancyChart" height="150"></canvas>
    </div>
  </div>

  <div class="card">
    <h3><?= $t['booked_rooms'] ?></h3>
    <input type="text" id="search_booked" placeholder="<?= $t['search_guest'] ?>" style="margin-bottom: 10px; width:100%; padding:8px;">
    <button onclick="exportTableToExcel('booked_table', 'booked_rooms')" style="margin-bottom: 10px;"><?= $t['export'] ?></button>
    <div style="overflow-x:auto; max-height:300px;">
      <table id="booked_table" border="1" cellpadding="6" style="width:100%; border-collapse: collapse;">
        <thead>
          <tr style="background:#f0f0f0;">
            <th><?= $t['room'] ?></th>
            <th><?= $t['guest'] ?></th>
            <th><?= $t['checkin'] ?></th>
            <th><?= $t['checkout'] ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3><?= $t['available_rooms'] ?></h3>
    <input type="text" id="search_available" placeholder="<?= $t['search_available'] ?>" style="margin-bottom: 10px; width:100%; padding:8px;">
    <button onclick="exportTableToExcel('available_table', 'available_rooms')" style="margin-bottom: 10px;"><?= $t['export'] ?></button>
    <div style="overflow-x:auto; max-height:300px;">
      <table id="available_table" border="1" cellpadding="6" style="width:100%; border-collapse: collapse;">
        <thead>
          <tr style="background:#f0f0f0;">
            <th><?= $t['room'] ?></th>
            <th><?= $t['type'] ?></th>
            <th><?= $t['price'] ?></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<script>
window.occupancyChart = null;

function drawOccupancyChart(booked, available) {
  const ctx = document.getElementById('occupancyChart').getContext('2d');
  if (window.occupancyChart && typeof window.occupancyChart.destroy === 'function') {
    window.occupancyChart.destroy();
  }
  window.occupancyChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ["<?= $t['booked'] ?>", "<?= $t['available'] ?>"],
      datasets: [{
        data: [booked, available],
        backgroundColor: ['#d4af37', '#28a745']

      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'bottom' } }
    }
  });
}

function populateTables(booked, available) {
  $('#booked_table tbody').html(booked.map(row => `
  <tr>
    <td>${row.room_number}</td>
    <td>${row.guest_name}</td>
    <td>${row.check_in_date}</td>
    <td>${row.check_out_date}</td>
    <td>${row.created_at || '-'}</td>
  </tr>
`).join(''));


  $('#available_table tbody').html(available.map(row => `
    <tr>
      <td>${row.room_number}</td>
      <td>${row.type}</td>
      <td>$${parseFloat(row.price).toFixed(2)}</td>
    </tr>
  `).join(''));
}

function exportTableToExcel(tableID, filename = '') {
  const tableHTML = document.getElementById(tableID).outerHTML.replace(/ /g, '%20');
  const downloadLink = document.createElement("a");
  downloadLink.href = 'data:application/vnd.ms-excel,' + tableHTML;
  downloadLink.download = filename ? filename + '.xls' : 'export.xls';
  document.body.appendChild(downloadLink);
  downloadLink.click();
  document.body.removeChild(downloadLink);
}

function loadDashboardData() {
  $.getJSON('../api/dashboard_data.php', function(data) {
    console.log('Dashboard Data:', data);
    if (data.status !== 'success') {
      Swal.fire('Error', 'Failed to load dashboard stats', 'error');
      return;
    }

    $('#booked_count').text(data.room_stats.booked);
    $('#available_count').text(data.room_stats.available);
    $('#maintenance_count').text(data.room_stats.maintenance);

    const rev = data.revenue;
    $('#rev_day').html(`<span style="${rev.styles.daily || ''}">${parseFloat(rev.daily).toFixed(2)}</span>`);
    $('#rev_week').html(`<span style="${rev.styles.weekly || ''}">${parseFloat(rev.weekly).toFixed(2)}</span>`);
    $('#rev_month').html(`<span style="${rev.styles.monthly || ''}">${parseFloat(rev.monthly).toFixed(2)}</span>`);
    $('#rev_year').html(`<span style="${rev.styles.yearly || ''}">${parseFloat(rev.yearly).toFixed(2)}</span>`);
    $('#rev_due').html(`<span style="${rev.styles.balance_due || ''}">${parseFloat(rev.balance_due).toFixed(2)}</span>`);

    if (rev.daily === 0 && rev.weekly === 0 && rev.monthly === 0 && rev.yearly === 0 && rev.balance_due === 0) {
      $('#revenue_card').html(`<h3><?= $t['revenue_stats'] ?></h3><p><em><?= $t['restricted'] ?></em></p>`);
    } else {
      if (rev.yearly === 0) $('#rev_year_row').hide();
      if (rev.balance_due === 0) $('#rev_due_row').hide();
    }

    populateTables(data.booked_rooms, data.available_rooms);
    drawOccupancyChart(data.room_stats.booked, data.room_stats.available);
  });
}

$(document).ready(function () {
  loadDashboardData();
  setInterval(loadDashboardData, 20000);
});
</script>

<script>
  function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('show');
  }
</script>

<script src="../assets/js/session_monitor.js"></script>


</body>
</html>