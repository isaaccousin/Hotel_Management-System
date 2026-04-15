<?php
require_once '../includes/db.php';
require_once 'log_room.php';
require_once '../auth/session_check.php';

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'Guest Booking',
    'name' => 'Full Name',
    'email' => 'Email Address',
    'phone' => 'Phone Number',
    'id_doc' => 'ID/Passport Number',
    'room' => 'Room Number',
    'checkin' => 'Check-In Date',
    'checkout' => 'Check-Out Date',
    'amount' => 'Amount Paid (Leave 0 for Credit)',
    'submit' => 'Book Guest',
    'select_room' => '-- Select Available Room --',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout',
    'success' => 'Booking completed successfully!',
    'card_uid' => 'Card UID',
    'error' => 'Booking failed. Please try again.'
  ],
  'fr' => [
    'title' => 'Réservation du Client',
    'name' => 'Nom Complet',
    'email' => 'Adresse E-mail',
    'phone' => 'Numéro de Téléphone',
    'id_doc' => 'Numéro ID/Passeport',
    'room' => 'Numéro de Chambre',
    'checkin' => 'Date d\'Arrivée',
    'checkout' => 'Date de Départ',
    'amount' => 'Montant Payé (0 pour Crédit)',
    'submit' => 'Réserver',
    'select_room' => '-- Sélectionner une Chambre Disponible --',
    'nav_dashboard' => 'Tableau de Bord',
    'nav_checkin' => 'Enregistrement',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réservation',
    'nav_rooms' => 'Gestion des Chambres',
    'nav_staff' => 'Gestion du Personnel',
    'nav_logout' => 'Déconnexion',
    'success' => 'Réservation effectuée avec succès !',
    'card_uid' => 'UID de la Carte',
    'error' => 'La réservation a échoué. Veuillez réessayer.'
  ]
];

$t = $texts[$lang];

$success = $_SESSION['booking_success'] ?? null;
$error = $_SESSION['booking_error'] ?? null;
unset($_SESSION['booking_success'], $_SESSION['booking_error']);

$stmt = $conn->query("SELECT id, room_number FROM rooms WHERE status = 'available'");
$availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/main.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      .hamburger {
        display: block;
      }

      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background: #1a1a1a;
      }

      nav.show {
        display: flex;
      }

      nav a {
        border-top: 1px solid #333;
        padding: 12px;
      }

    .container {
        margin: 20px 15px;
      }


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
  <form action="process_booking.php" method="POST">
    <label><?= $t['name'] ?></label>
    <input type="text" name="guest_name" required>

    <label><?= $t['email'] ?></label>
    <input type="email" name="email" required>

    <label><?= $t['phone'] ?></label>
    <input type="text" name="phone" required>

    <label><?= $t['id_doc'] ?></label>
    <input type="text" name="id_doc" required>

    <label><?= $t['room'] ?></label>
    <select name="room_id" required>
      <option value=""><?= $t['select_room'] ?></option>
      <?php foreach ($availableRooms as $room): ?>
        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['room_number']) ?></option>
      <?php endforeach; ?>
    </select>

    <label><?= $t['checkin'] ?></label>
    <input type="date" name="checkin_date" required>

    <label><?= $t['checkout'] ?></label>
    <input type="date" name="checkout_date" required>

    <label><?= $t['amount'] ?></label>
    <input type="number" name="amount_paid" min="0" step="0.01">
    
   <input type="hidden" name="card_uid" value="<?= strtoupper(bin2hex(random_bytes(4))) ?>">

    <button type="submit"><?= $t['submit'] ?></button>
  </form>
</div>

<?php if ($success && isset($_SESSION['booking_receipt'])): ?>
<script>
  const receiptId = <?= json_encode($_SESSION['booking_receipt']) ?>;

  Swal.fire({
    icon: 'success',
    title: '<?= $t['success'] ?>',
    text: 'Preparing your receipt...',
    timer: 2000,
    showConfirmButton: false,
    willClose: () => {
      const iframe = document.getElementById('receiptFrame');
      iframe.onload = () => {
        try {
          const contentWindow = iframe.contentWindow;
          contentWindow.focus();
          contentWindow.print();

          contentWindow.onafterprint = () => {
            window.location.href = 'dashboard.php';
          };

          // Fallback
          setTimeout(() => window.location.href = 'dashboard.php', 6000);
        } catch (e) {
          console.error("Receipt print error:", e);
          window.location.href = 'dashboard.php';
        }
      };

      iframe.src = '../templates/receipt.php?booking_id=' + receiptId;
    }
  });
</script>
<?php unset($_SESSION['booking_receipt']); endif; ?>




<script>
  function toggleMenu() {
    const nav = document.getElementById('mainNav');
    nav.classList.toggle('show');
  }
</script>



<iframe id="receiptFrame" style="display:none;"></iframe>

<script src="../assets/js/session_monitor.js"></script>


</body>
</html>