<?php
require_once '../auth/session_check.php';
require_once '../includes/db.php';
require_once 'log_room.php';


if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header('Location: dashboard.php'); // or use access_denied.php if you have one
    exit();
}



$lang = $_SESSION['lang'] ?? 'en';
$role = $_SESSION['role'] ?? 'staff';
$texts = [
  'en' => [
    'title' => 'Room Management',
    'add_room' => 'Add Room',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'maintenance' => 'Set to Maintenance',
    'restore' => 'Restore from Maintenance',
    'available' => 'Available',
    'booked' => 'Booked',
    'refund' => 'Pending Balance',
    'partial' => 'Partial Refund',
    'confirm_delete' => 'Are you sure you want to delete this room?',
    'confirm_refund' => 'Refund 75% of the paid amount?',
    'confirm_partial' => 'Enter amount to refund:',
    'room_number' => 'Room Number',
    'type' => 'Type',
    'price' => 'Price',
    'status' => 'Status',
    'actions' => 'Actions',
    'submit' => 'Submit',
    'dashboard' => '← Return to Dashboard',
    'maintenance_tab' => 'Maintenance',
    'amenities' => 'Amenities',
    'notes' => 'Notes',
    'cancel' => 'Cancel',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout',
    'extend_stay' => 'Extend Stay',
    'current_checkout' => 'Current checkout',
    'pay_now' => 'Pay Now',
    'payment' => 'Payment',
    'storage_tab' => 'Storage',
    'pay_later' => 'Pay Later',
    'payment_option' => 'Payment Option',
    'invalid_date' => 'New date must be after current checkout',
    'success' => 'Extended',
    'error' => 'Error',
  ],
  'fr' => [
    'title' => 'Gestion des Chambres',
    'add_room' => 'Ajouter Chambre',
    'edit' => 'Modifier',
    'delete' => 'Supprimer',
    'maintenance' => 'Mettre en Maintenance',
    'restore' => 'Restaurer',
    'available' => 'Disponible',
    'booked' => 'Occupée',
    'refund' => 'Solde Impayé',
    'partial' => 'Remboursement Partiel',
    'confirm_delete' => 'Supprimer cette chambre ?',
    'confirm_refund' => 'Rembourser 75% du montant payé ?',
    'confirm_partial' => 'Entrer le montant à rembourser :',
    'room_number' => 'Numéro Chambre',
    'type' => 'Type',
    'price' => 'Prix',
    'status' => 'Statut',
    'actions' => 'Actions',
    'submit' => 'Valider',
    'dashboard' => '← Retour au Tableau de Bord',
    'maintenance_tab' => 'Maintenance',
    'amenities' => 'Commodités',
    'notes' => 'Remarques',
    'cancel' => 'Annuler',
    'nav_dashboard' => 'Tableau de bord',
    'nav_checkin' => 'Enregistrement',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réserver',
    'payment' => 'Paiement',
    'storage_tab' => 'Rangement',
    'nav_rooms' => 'Chambres',
    'nav_staff' => 'Personnel',
    'nav_logout' => 'Déconnexion',
    'extend_stay' => 'Prolonger le séjour',
    'current_checkout' => 'Départ actuel',
    'pay_now' => 'Payer maintenant',
    'pay_later' => 'Payer plus tard',
    'payment_option' => 'Option de paiement',
    'invalid_date' => 'La nouvelle date doit être après la date de départ actuelle',
    'success' => 'Prolongé',
    'error' => 'Erreur',
  ]
];
$t = $texts[$lang];
?><!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Playfair+Display&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/room_styles.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: linear-gradient(to right, #f5f5f5, #eae4dc);
      font-family: 'Open Sans', sans-serif;
      font-size: 16px;
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
    .logo-nav img { height: 45px; }
    nav {
      display: flex;
      gap: 12px;
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
    nav a:hover { background-color: #333; }
    .hamburger {
      display: none;
      font-size: 24px;
      cursor: pointer;
    }
    @media (max-width: 768px) {
      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background-color: #1a1a1a;
        padding: 10px 0;
      }
      nav.show { display: flex; }
      .hamburger { display: block; }
    }
    h2 {
      font-family: 'Playfair Display', serif;
      font-size: 30px;
      color: #2e2e2e;
      border-bottom: 3px solid #c5a253;
      padding-bottom: 12px;
      margin-bottom: 30px;
    }
    label, input, select, textarea, th, td, small {
      font-size: 16px;
    }
    .btn-dashboard {
      background: #2e2e2e;
      color: white;
      padding: 10px 18px;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
    }
    .btn-dashboard:hover {
      background-color: #444;
    }
    .btn-primary {
      background-color: #c5a253;
      border: none;
      font-weight: bold;
    }
    .btn-primary:hover {
      background-color: #b39144;
    }
    .btn-secondary {
      background-color: #7f8c8d;
      border: none;
    }
    .table {
      border-radius: 12px;
      overflow: hidden;
      background-color: white;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .table th {
      background-color: #f2f2f2;
      color: #333;
    }
    .table td, .table th {
      vertical-align: middle !important;
    }
    .nav-tabs .nav-link.active {
      background-color: #c5a253;
      color: white;
      font-weight: bold;
    }
    .nav-tabs .nav-link {
      color: #444;
    }
    small.text-danger {
      color: #dc3545 !important;
    }
    small.text-muted {
      color: #6c757d;
    }
   
    }
  </style>
</head>
<body>
<header>
  <div class="logo-nav">
    <img src="../assets/images/47.png" alt="Hotel Logo">
    <strong>47 Points Hotel</strong>
  </div>
  <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
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

    
    <a href="../auth/logout.php"><?= $t['nav_logout'] ?></a>
  </nav>
</header>

<div class="container mt-5 mb-5 p-4 bg-white rounded shadow-lg">
  <a href="dashboard.php" class="btn-dashboard mb-4 d-inline-block"><?= $t['dashboard'] ?></a>
  <h2 class="text-center"><?= $t['title'] ?></h2>

  <ul class="nav nav-tabs mb-4" id="roomTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#availableTab"><?= $t['available'] ?></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#bookedTab"><?= $t['booked'] ?></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pendingTab"><?= $t['refund'] ?></a></li>
    
  
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#maintenanceTab"><?= $t['maintenance_tab'] ?></a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#storageTab">Storage</a></li>
    
    </ul>
   
    
 

  

  <div class="tab-content">
    <div class="tab-pane fade show active" id="availableTab">
      <div class="d-flex justify-content-between mt-4 mb-2">
        <?php if (in_array($role, ['admin', 'manager'])): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">+ <?= $t['add_room'] ?></button>
        <?php endif; ?>
       
        <?php

if ($role === 'admin') {
  echo '<a href="audit_room.php" class="btn btn-dark mb-3">Room Audit Trail</a>';
}
?>

        <button class="btn btn-secondary" onclick="exportTable('availableTable')">Export</button>
      </div>
      <input type="text" id="searchAvailable" class="form-control mb-2" placeholder="Search Available Rooms...">
      <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
        <table class="table table-bordered" id="availableTable">
          <thead><tr><th><?= $t['room_number'] ?></th><th><?= $t['type'] ?></th><th><?= $t['price'] ?></th><th><?= $t['status'] ?></th><th><?= $t['actions'] ?></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    
    <div class="tab-pane fade mt-0" id="bookedTab">
  <div class="d-flex justify-content-between mb-2">
    <div></div>
    <button class="btn btn-secondary" onclick="exportTable('bookedTable')">Export</button>
  </div>
  <input type="text" id="searchBooked" class="form-control mb-2" placeholder="Search Booked Rooms...">
  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
    <table class="table table-bordered" id="bookedTable">
      <thead>
        <tr>
          <th><?= $t['room_number'] ?></th>
          <th><?= $t['type'] ?></th>
          <th><?= $t['price'] ?></th>
          <th><?= $t['status'] ?></th>
          <th><?= $t['actions'] ?></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>


<div class="tab-pane fade mt-0" id="pendingTab">
  <div class="d-flex justify-content-between mb-2">
    <div></div>
    <button class="btn btn-secondary" onclick="exportTable('pendingTable')">Export</button>
  </div>
  <input type="text" id="searchPending" class="form-control mb-2" placeholder="Search Pending Balances...">
  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
    <table class="table table-bordered" id="pendingTable">
      <thead>
        <tr>
          <th><?= $t['room_number'] ?></th>
          <th><?= $t['type'] ?></th>
          <th><?= $t['price'] ?></th>
          <th><?= $t['status'] ?></th>
          <th><?= $t['actions'] ?></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>








<div class="tab-pane fade mt-0" id="maintenanceTab">
  <div class="d-flex justify-content-between mb-2">
    <div></div>
    <button class="btn btn-secondary" onclick="exportTable('maintenanceTable')">Export</button>
  </div>
  <input type="text" id="searchMaintenance" class="form-control mb-2" placeholder="Search Maintenance Rooms...">
  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
    <table class="table table-bordered" id="maintenanceTable">
      <thead>
        <tr>
          <th><?= $t['room_number'] ?></th>
          <th><?= $t['type'] ?></th>
          <th><?= $t['price'] ?></th>
          <th><?= $t['status'] ?></th>
          <th><?= $t['actions'] ?></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>


<div class="tab-pane fade mt-0" id="storageTab">
  <div class="d-flex justify-content-between mb-2">
    <div></div>
    <button class="btn btn-secondary" onclick="exportTable('storageTable')">Export</button>
  </div>
  <input type="text" id="searchStorage" class="form-control mb-2" placeholder="Search Storage Rooms...">
  <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
    <table class="table table-bordered" id="storageTable">
      <thead>
        <tr>
          <th><?= $t['room_number'] ?></th>
          <th><?= $t['type'] ?></th>
          <th><?= $t['price'] ?></th>
          <th><?= $t['status'] ?></th>
          <th><?= $t['actions'] ?></th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
        
 
    
  </div>
  
 
  
  
</div>







<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="addRoomLabel"><?= $t['add_room'] ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-4">
        <form id="addRoomForm">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="room_number" class="form-label"><?= $t['room_number'] ?></label>
              <input type="text" class="form-control" id="room_number" required>
            </div>
            <div class="col-md-6">
              <label for="room_type" class="form-label"><?= $t['type'] ?></label>
              <input type="text" class="form-control" id="room_type" required>
            </div>
            <div class="col-md-6">
              <label for="room_price" class="form-label"><?= $t['price'] ?></label>
              <input type="number" class="form-control" id="room_price" required min="0">
            </div>
            <div class="col-md-6">
              <label for="room_amenities" class="form-label"><?= $t['amenities'] ?></label>
              <input type="text" class="form-control" id="room_amenities" placeholder="WiFi, TV, AC">
            </div>
            <div class="col-12">
              <label for="room_notes" class="form-label"><?= $t['notes'] ?></label>
              <textarea class="form-control" id="room_notes" rows="3" placeholder="e.g. Ocean view, king-size bed..."></textarea>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= $t['cancel'] ?></button>
        <button type="button" class="btn btn-primary" onclick="submitRoomModal()"><?= $t['submit'] ?></button>
      </div>
    </div>
  </div>
</div>




<script>
  sessionStorage.setItem('user_role', '<?= $_SESSION['role'] ?>');
  sessionStorage.setItem('room_lang', '<?= json_encode($texts[$lang], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>');
</script>

<script>
function toggleMenu() {
  document.getElementById('mainNav').classList.toggle('show');
}
</script>


<script src="../assets/js/room_ajax.js"></script>
<script src="../assets/js/session_monitor.js"></script>


</body>
</html>