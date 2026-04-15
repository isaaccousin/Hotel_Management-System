<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';
require_once '../auth/session_check.php';

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'Guest Check-Out',
    'room_number' => 'Room Number',
    'guest_name' => 'Guest Name',
    'balance' => 'Balance Due',
    'checkout' => 'Check Out Guest',
    'error' => 'Checkout failed. Please clear the balance first.',
    'success' => 'Guest checked out successfully!',
    'print' => '🗞 Print Receipt',
    'enter_payment' => 'Enter Payment Amount ($)',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout'
  ],
  'fr' => [
    'title' => 'Départ du client',
    'room_number' => 'Numéro de chambre',
    'guest_name' => 'Nom du client',
    'balance' => 'Solde dû',
    'checkout' => 'Effectuer le départ',
    'error' => 'Échec du départ. Veuillez d\'abord régler le solde.',
    'success' => 'Le client a été enregistré comme parti avec succès !',
    'print' => '🗞 Imprimer le reçu',
    'enter_payment' => 'Saisir le montant du paiement ($)',
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

$stmt = $conn->query("SELECT b.id, b.guest_name, b.balance_due, r.room_number 
                      FROM bookings b 
                      JOIN rooms r ON b.room_id = r.id 
                      WHERE b.status IN ('checked_in', 'booked')");
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['checkout_success'] ?? '';
$error = $_SESSION['checkout_error'] ?? '';
$receipt_id = $_SESSION['checkout_receipt'] ?? null;
unset($_SESSION['checkout_success'], $_SESSION['checkout_error'], $_SESSION['checkout_receipt']);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Playfair+Display&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
    .hamburger {
      display: none;
      font-size: 24px;
      cursor: pointer;
      margin-left: auto;
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
      background: #d4af37;
      color: #1a1a1a;
      font-weight: 600;
      text-decoration: none;
    }
    .container {
      max-width: 600px;
      margin: 60px auto;
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 26px;
      color: #1a1a1a;
      font-family: 'Playfair Display', serif;
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

    @media screen and (max-width: 768px) {
      .hamburger {
        display: block;
      }
      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background-color: #1a1a1a;
      }
      nav.show {
        display: flex;
      }
      nav a {
        border-top: 1px solid #333;
        padding: 10px;
      }
      .container {
        margin: 30px 15px;
        padding: 25px;
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
  <form id="checkoutForm" method="POST" action="process_checkout.php">
    <label><?= $t['room_number'] ?></label>
    <select name="booking_id" id="bookingSelect" required>
      <option value="">-- <?= $t['room_number'] ?> --</option>
      <?php foreach ($guests as $guest): ?>
        <?php
          $stmt_status = $conn->prepare("SELECT status FROM bookings WHERE id = ?");
          $stmt_status->execute([$guest['id']]);
          $status = $stmt_status->fetchColumn();
          $balance = floatval($guest['balance_due']);
          $is_pending = $balance > 0;
          $icon = $is_pending ? '❗' : ($status === 'booked' ? '📌' : '✅');
          $optionStyle = $is_pending ? 'style="color:red;"' : '';
        ?>
        <option value="<?= $guest['id'] ?>" data-balance="<?= $balance ?>" <?= $optionStyle ?>>
          <?= $icon ?> Room <?= htmlspecialchars($guest['room_number']) ?> - <?= htmlspecialchars($guest['guest_name']) ?> (<?= $t['balance'] ?>: $<?= number_format($balance, 2) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <div id="balanceSection" style="display:none;">
      <label><?= $t['enter_payment'] ?></label>
      <input type="number" step="0.01" min="0" name="payment_amount" id="paymentAmount">
      <small id="balanceDueLabel" class="text-muted mt-1 d-block"></small>
    </div>

    <button type="submit"><?= $t['checkout'] ?></button>
  </form>
</div>

<?php if ($success && $receipt_id): ?>
<script>
  Swal.fire({
    icon: 'success',
    title: '<?= addslashes($t['success']) ?>',
    showConfirmButton: false,
    timer: 1600
  }).then(() => {
    const iframe = document.getElementById('receiptFrame');
    iframe.onload = () => {
      try {
        const win = iframe.contentWindow;
        win.focus();
        win.print();
        win.onafterprint = () => {
          window.location.href = 'dashboard.php';
        };
      } catch (e) {
        console.error("Print error:", e);
        window.location.href = 'dashboard.php';
      }
    };
    iframe.src = '../templates/receipt.php?booking_id=<?= $receipt_id ?>';
  });
</script>
<?php elseif ($error): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: '<?= addslashes($t['error']) ?>',
    text: '<?= addslashes($error) ?>',
    confirmButtonColor: '#d4af37'
  });
</script>
<?php endif; ?>


<script>
  const bookingSelect = document.getElementById('bookingSelect');
  const balanceSection = document.getElementById('balanceSection');
  const balanceLabel = document.getElementById('balanceDueLabel');
  const paymentInput = document.getElementById('paymentAmount');

  bookingSelect.addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const balance = parseFloat(selected.getAttribute('data-balance') || 0);
    if (balance > 0) {
      balanceSection.style.display = 'block';
      balanceLabel.innerHTML = `<span style="color:red;"><strong><?= $t['balance'] ?>: $${balance.toFixed(2)}</strong></span>`;
      paymentInput.value = balance.toFixed(2);
    } else {
      balanceSection.style.display = 'none';
      paymentInput.value = '';
    }
  });

  document.getElementById('checkoutForm').addEventListener('submit', function (e) {
    const selected = bookingSelect.options[bookingSelect.selectedIndex];
    const balance = parseFloat(selected.getAttribute('data-balance') || 0);
    const payment = parseFloat(paymentInput.value || 0);

    if (balance > 0 && payment !== balance) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Payment mismatch',
        text: `You must pay exactly $${balance.toFixed(2)} to proceed.`,
        confirmButtonColor: '#d4af37'
      });
    }
  });

  function toggleMenu() {
    document.getElementById('mainNav').classList.toggle('show');
  }
</script>

<iframe id="receiptFrame" style="display:none;"></iframe>
<script src="../assets/js/session_monitor.js"></script>


</body>
</html>