<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
require_once '../auth/session_check.php';

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'Guest Check-In',
    'name' => 'Full Name',
    'email' => 'Email Address',
    'phone' => 'Phone Number',
    'id_doc' => 'ID/Passport Number',
    'room' => 'Room Number',
    'checkin' => 'Check-In Date',
    'checkout' => 'Check-Out Date',
    'submit' => 'Check In Guest',
    'success' => 'Guest checked in successfully!',
    'error' => 'Check-in failed. Please try again.',
    'select_room' => '-- Select Available Room --',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'card_uid' => 'Card UID',
    'nav_logout' => 'Logout'
  ],
  'fr' => [
    'title' => 'Enregistrement du client',
    'name' => 'Nom complet',
    'email' => 'Adresse e-mail',
    'phone' => 'Numéro de téléphone',
    'id_doc' => 'Numéro d\'identité ou passeport',
    'room' => 'Numéro de chambre',
    'checkin' => 'Date d\'arrivée',
    'checkout' => 'Date de départ',
    'submit' => 'Enregistrer le client',
    'success' => 'Client enregistré avec succès !',
    'error' => 'Échec de l\'enregistrement. Veuillez réessayer.',
    'select_room' => '-- Sélectionner une chambre disponible --',
    'nav_dashboard' => 'Tableau de bord',
    'nav_checkin' => 'Enregistrement',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réserver',
    'nav_rooms' => 'Chambres',
    'nav_staff' => 'Personnel',
    'card_uid' => 'UID de la Carte',
    'nav_logout' => 'Déconnexion'
  ]
];
$t = $texts[$lang];

// Fetch available rooms
$stmt = $conn->query("SELECT id, room_number FROM rooms WHERE status = 'available'");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      margin: 0;
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
    .container {
      max-width: 600px;
      margin: 40px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 24px;
      color: #1a1a1a;
    }
    label {
      font-weight: bold;
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin-bottom: 18px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    button {
      background-color: #d4af37;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
    }
    button:hover {
      background-color: #b8932b;
    }
    .hamburger {
      display: none;
      font-size: 24px;
      cursor: pointer;
      margin-left: auto;
    }
    @media screen and (max-width: 768px) {
      .hamburger { display: block; }
      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #1a1a1a;
      }
      nav.show { display: flex; }
      nav a {
        border-top: 1px solid #333;
        padding: 12px;
      }
      .container { margin: 20px 15px; }
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
    <a href="../auth/logout.php"><?= $t['nav_logout'] ?></a>
  </nav>
</header>

<div class="container">
  <h2><?= $t['title'] ?></h2>
  <form id="checkinForm">
    <label><?= $t['name'] ?></label>
    <input type="text" name="name" required>

    <label><?= $t['email'] ?></label>
    <input type="email" name="email" required>

    <label><?= $t['phone'] ?></label>
    <input type="text" name="phone" required>

    <label><?= $t['id_doc'] ?></label>
    <input type="text" name="id_doc" required>

    <label><?= $t['room'] ?></label>
    <select name="room_id" required>
      <option value=""><?= $t['select_room'] ?></option>
      <?php foreach ($rooms as $room): ?>
        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_number']) ?></option>
      <?php endforeach; ?>
    </select>

    <label><?= $t['checkin'] ?></label>
    <input type="date" name="checkin_date" required value="<?= date('Y-m-d') ?>">

    <label><?= $t['checkout'] ?></label>
    <input type="date" name="checkout_date" required>

    <input type="text" name="card_uid" id="card_uid" required readonly style="opacity: 0; position: absolute; left: -9999px;">
  </form>
  <button type="submit" form="checkinForm"><?= $t['submit'] ?></button>
</div>

<script>
  // Auto-focus UID field
  window.addEventListener('DOMContentLoaded', () => {
    const uidInput = document.getElementById("card_uid");
    uidInput.focus();
    uidInput.addEventListener('input', () => {
      console.log("RFID UID Scanned:", uidInput.value);
    });
  });

  // Poll for unused UID
  setInterval(() => {
    $.get('../api/latest_uid.php', function(res) {
      if (res.status === 'success') {
        $('#card_uid').val(res.uid);
        $('#card_uid').data('card-id', res.card_id);
      }
    });
  }, 3000);

  function toggleMenu() {
    document.getElementById('mainNav').classList.toggle('show');
  }

  $(document).ready(function () {
    $('#checkinForm').on('submit', function (e) {
      e.preventDefault();

      const formData = $(this).serialize();
      const cardId = $('#card_uid').data('card-id') || '';
      const finalData = formData + '&card_id=' + encodeURIComponent(cardId);

      $.ajax({
        url: 'process_checkin.php',
        method: 'POST',
        data: finalData,
        dataType: 'json',
        success: function (response) {
          if (response.status === 'success') {
            Swal.fire({
              icon: 'success',
              title: '<?= addslashes($t['success']) ?>',
              text: 'Printing receipt...',
              timer: 2500,
              showConfirmButton: false
            }).then(() => {
              const iframe = document.getElementById('receiptFrame');
              iframe.src = `../templates/receipt.php?id=${response.receipt_id}`;
              iframe.onload = function () {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                setTimeout(() => window.location.href = 'dashboard.php', 1000);
              };
            });
          } else {
            Swal.fire('<?= addslashes($t['error']) ?>', response.message, 'error');
          }
        },
        error: function () {
          Swal.fire('<?= addslashes($t['error']) ?>', 'Server error occurred.', 'error');
        }
      });
    });
  });
</script>

<iframe id="receiptFrame" style="display: none;"></iframe>
<script src="../assets/js/session_monitor.js"></script>


</body>
</html>