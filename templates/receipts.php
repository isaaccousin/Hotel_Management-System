<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';

$booking_id = $_GET['booking_id'] ?? $_GET['id'] ?? 0;
$lang = $_SESSION['lang'] ?? 'fr';

function t($key) {
    $translations = [
        'client_name' => ['en' => 'Client Name', 'fr' => 'Nom du client'],
        'phone' => ['en' => 'Phone', 'fr' => 'Téléphone'],
        'email' => ['en' => 'Email', 'fr' => 'E-mail'],
        'id' => ['en' => 'ID / Passport', 'fr' => 'Identité'],
        'room_number' => ['en' => 'Room Number', 'fr' => 'Numéro de chambre'],
        'room_type' => ['en' => 'Room Type', 'fr' => 'Type de chambre'],
        'price_per_night' => ['en' => 'Price per Night', 'fr' => 'Prix par nuit'],
        'checkin' => ['en' => 'Check-In Date', 'fr' => "Date d'arrivée"],
        'checkout' => ['en' => 'Check-Out Date', 'fr' => 'Date de départ'],
        'initial_paid' => ['en' => 'Initial Payment', 'fr' => 'Montant initial payé'],
        'extension' => ['en' => 'Extension Payment', 'fr' => 'Paiement de prolongation'],
        'partial' => ['en' => 'Partial Payment', 'fr' => 'Paiement partiel'],
        'retained' => ['en' => 'Retained Amount', 'fr' => 'Montant retenu'],
        'refunded' => ['en' => 'Refunded Amount', 'fr' => 'Montant remboursé'],
        'balance_due' => ['en' => 'Balance Due', 'fr' => 'Solde dû'],
        'total_paid' => ['en' => 'Total Paid', 'fr' => 'Montant total payé'],
        'payment_history' => ['en' => 'Payment History', 'fr' => 'Historique des paiements'],
        'thank_you' => [
            'en' => 'Thank you for choosing 47 Points Hotel. We hope to see you again!',
            'fr' => "Merci d'avoir choisi 47 Points Hôtel. Nous espérons vous revoir bientôt !"
        ],
        'client_sign' => ['en' => 'Client Signature', 'fr' => 'Signature du client'],
        'hotel_sign' => ['en' => 'Hotel Representative', 'fr' => "Représentant de l'hôtel"],
    ];

    global $lang;
    return $translations[$key][$lang] ?? $key;
}

if (!$booking_id) {
    die("<h3 style='color:red;'>ID manquant.</h3>");
}

$stmt = $conn->prepare("SELECT b.*, r.room_number, r.type, r.price FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.id = ?");
$stmt->execute([$booking_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $stmt = $conn->prepare("SELECT b.*, r.room_number, r.type, r.price FROM bookings b JOIN rooms r ON b.room_id = r.id WHERE b.room_id = ? ORDER BY b.id DESC LIMIT 1");
    $stmt->execute([$booking_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$data) {
    die("<h3 style='color:red;'>Réservation introuvable.</h3>");
}

$date_now = date('d F Y - H:i');
$paymentsStmt = $conn->prepare("SELECT amount_paid, payment_method, payment_date FROM payments WHERE booking_id = ?");
$paymentsStmt->execute([$data['id']]);

$amount_paid = 0;
$extension_amount = 0;
$partial_amount = 0;
$retained_amount = 0;
$refunded_amount = 0;
$payment_rows = '';

while ($p = $paymentsStmt->fetch(PDO::FETCH_ASSOC)) {
    $amount_paid += floatval($p['amount_paid']);
    $method = $p['payment_method'];
    $date = $p['payment_date'];
    $amt = number_format($p['amount_paid'], 2);

    $payment_rows .= "<tr><td>$date</td><td>$method</td><td>$$amt</td></tr>";

    if ($method === 'retained-refund') {
        $retained_amount += floatval($p['amount_paid']);
    } elseif ($method === 'extension') {
        $extension_amount += floatval($p['amount_paid']);
    } elseif ($method === 'partial') {
        $partial_amount += floatval($p['amount_paid']);
    } elseif ($method === 'refund') {
        $refunded_amount += floatval($p['amount_paid']);
    }
}

$checkin_display = $data['created_at'] ?? $data['check_in_date'];
$stored_balance = floatval($data['balance_due'] ?? 0);
$balance_due = max(0, $stored_balance);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title>Reçu | 47 Points Hôtel</title>
  <style>
    body { font-family: 'Times New Roman', serif; background: #fff; margin: 0; padding: 0; }
    .receipt { max-width: 750px; margin: 30px auto; background: #fff; padding: 40px; border: 2px solid gold; box-shadow: 0 0 25px rgba(218,165,32,0.3); border-radius: 12px; position: relative; }
    .watermark { position: absolute; top: 50%; left: 50%; width: 400px; transform: translate(-50%, -50%); opacity: 0.08; z-index: 0; }
    .logo { text-align: center; margin-bottom: 10px; z-index: 2; position: relative; }
    .logo img { width: 100px; height: 100px; border-radius: 50%; margin-bottom: 5px; }
    h2 { font-size: 28px; color: #2c3e50; margin-bottom: 5px; }
    .slogan { font-style: italic; font-size: 14px; color: gray; }
    .date-now { text-align: center; font-size: 13px; color: gray; margin-bottom: 20px; }
    table { width: 100%; margin-top: 20px; font-size: 15px; border-collapse: collapse; }
    td, th { padding: 10px; border-bottom: 1px solid #ddd; }
    td.label { background: #f8f8f8; font-weight: bold; width: 40%; }
    .thank-you { margin-top: 30px; font-style: italic; text-align: center; color: #444; font-size: 18px; }
    .signatures { display: flex; justify-content: space-between; margin-top: 50px; font-size: 14px; }
    .sign-line { border-top: 1px solid #999; width: 250px; text-align: center; margin-top: 5px; color: #555; }
    @media print { body { background: none; } }
  </style>
</head>
<body>
<div class="receipt">
  <img src="../assets/images/47.png" alt="Watermark" class="watermark">
  <div class="logo">
    <img src="../assets/images/47.png" alt="Logo">
    <h2>47 Points Hôtel</h2>
    <div class="slogan">Là où le luxe rencontre le confort</div>
  </div>
  <div class="date-now"><?= $date_now ?></div>
  <table>
    <tr><td class="label"><?= t('client_name') ?></td><td><?= htmlspecialchars($data['guest_name']) ?></td></tr>
    <tr><td class="label"><?= t('phone') ?></td><td><?= htmlspecialchars($data['guest_phone']) ?></td></tr>
    <tr><td class="label"><?= t('email') ?></td><td><?= htmlspecialchars($data['guest_email']) ?></td></tr>
    <tr><td class="label"><?= t('id') ?></td><td><?= htmlspecialchars($data['id_doc']) ?></td></tr>
    <tr><td class="label"><?= t('room_number') ?></td><td><?= htmlspecialchars($data['room_number']) ?></td></tr>
    <tr><td class="label"><?= t('room_type') ?></td><td><?= htmlspecialchars($data['type']) ?></td></tr>
    <tr><td class="label"><?= t('price_per_night') ?></td><td>$<?= number_format($data['price'], 2) ?></td></tr>
    <tr><td class="label"><?= t('checkin') ?></td><td><?= $checkin_display ?></td></tr>
    <tr><td class="label"><?= t('checkout') ?></td><td><?= $data['check_out_date'] ?></td></tr>
    <tr><td class="label"><?= t('initial_paid') ?></td><td>$<?= number_format($data['amount_paid'], 2) ?></td></tr>
    <tr><td class="label"><?= t('extension') ?></td><td>$<?= number_format($extension_amount, 2) ?></td></tr>
    <tr><td class="label"><?= t('partial') ?></td><td>$<?= number_format($partial_amount, 2) ?></td></tr>
    <tr><td class="label"><?= t('retained') ?></td><td>$<?= number_format($retained_amount, 2) ?></td></tr>
    <tr><td class="label"><?= t('refunded') ?></td><td>$<?= number_format($refunded_amount, 2) ?></td></tr>
    <tr><td class="label"><?= t('balance_due') ?></td><td>$<?= number_format($balance_due, 2) ?></td></tr>
    <tr><td class="label"><?= t('total_paid') ?></td><td>$<?= number_format($amount_paid, 2) ?></td></tr>
  </table>
  <h3 style="margin-top:30px;"><?= t('payment_history') ?></h3>
  <table>
    <thead>
      <tr><th>Date</th><th>Method</th><th>Amount</th></tr>
    </thead>
    <tbody>
      <?= $payment_rows ?>
    </tbody>
  </table>
  <div class="thank-you"><?= t('thank_you') ?></div>
  <div class="signatures">
    <div><div class="sign-line"><?= t('client_sign') ?></div></div>
    <div><div class="sign-line"><?= t('hotel_sign') ?></div></div>
  </div>
</div>

<script>
  window.onload = function () {
    window.print();
    window.onafterprint = () => {
      window.close();
    };
  };
</script>
</body>
</html>