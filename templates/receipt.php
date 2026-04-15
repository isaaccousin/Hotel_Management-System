<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';

$booking_id = $_GET['booking_id'] ?? $_GET['id'] ?? 0;
// Get total amount paid (including balance paid at checkout)
$paymentStmt = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE booking_id = ?");
$paymentStmt->execute([$booking_id]);
$paymentRow = $paymentStmt->fetch(PDO::FETCH_ASSOC);
$total_paid = floatval($paymentRow['total_paid'] ?? 0.00);


if (!$booking_id) {
    die("<h3 style='color:red;'>ID de réservation manquant.</h3>");
}

$stmt = $conn->prepare("
    SELECT b.*, r.room_number, r.type, r.price
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("<h3 style='color:red;'>Réservation introuvable.</h3>");
}

$date_now = date('d F Y - H:i');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu | 47 Points Hôtel</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .receipt {
            max-width: 750px;
            margin: 30px auto;
            background: #fff;
            padding: 40px;
            border: 2px solid gold;
            box-shadow: 0 0 25px rgba(218,165,32,0.3);
            border-radius: 12px;
            position: relative;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 400px;
            transform: translate(-50%, -50%);
            opacity: 0.08;
            z-index: 0;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
            z-index: 2;
            position: relative;
        }
        .logo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .slogan {
            font-style: italic;
            font-size: 14px;
            color: gray;
        }
        .date-now {
            text-align: center;
            font-size: 13px;
            color: gray;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            margin-top: 20px;
            font-size: 15px;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        td.label {
            background: #f8f8f8;
            font-weight: bold;
            width: 40%;
        }
        .thank-you {
            margin-top: 30px;
            font-style: italic;
            text-align: center;
            color: #444;
            font-size: 18px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            font-size: 14px;
        }
        .sign-line {
            border-top: 1px solid #999;
            width: 250px;
            text-align: center;
            margin-top: 5px;
            color: #555;
        }
        @media print {
            body {
                background: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="receipt">
    <img src="../assets/images/47.png" alt="Filigrane" class="watermark">

    <div class="logo">
        <img src="../assets/images/47.png" alt="Logo de l'hôtel">
        <h2>47 Points Hôtel</h2>
        <div class="slogan">Là où le luxe rencontre le confort</div>
    </div>

    <div class="date-now"><?= $date_now ?></div>

    <table>
        <tr><td class="label">Nom du client</td><td><?= htmlspecialchars($data['guest_name']) ?></td></tr>
        <tr><td class="label">Téléphone</td><td><?= htmlspecialchars($data['guest_phone']) ?></td></tr>
        <tr><td class="label">E-mail</td><td><?= htmlspecialchars($data['guest_email']) ?></td></tr>
        <tr><td class="label">Identité</td><td><?= htmlspecialchars($data['id_doc']) ?></td></tr>
        <tr><td class="label">Numéro de chambre</td><td><?= htmlspecialchars($data['room_number']) ?></td></tr>
        <tr><td class="label">Type de chambre</td><td><?= htmlspecialchars($data['type']) ?></td></tr>
        <tr><td class="label">Prix par nuit</td><td>$<?= number_format($data['price'], 2) ?></td></tr>
        <tr><td class="label">Date d'arrivée</td><td><?= htmlspecialchars($data['check_in_date']) ?></td></tr>
        <tr><td class="label">Date de départ</td><td><?= htmlspecialchars($data['check_out_date']) ?></td></tr>
        <tr><td class="label">Montant total dû</td><td>$<?= number_format($data['amount_paid'] + $data['balance_due'], 2) ?></td></tr>
        <tr><td class="label">Montant payé</td><td>$<?= number_format($total_paid, 2) ?></td></tr>

        <?php if ($data['amount_paid'] + $data['balance_due'] > $total_paid): ?>
<tr><td class="label">Solde restant</td><td style="color:red;">$<?= number_format(($data['amount_paid'] + $data['balance_due']) - $total_paid, 2) ?></td></tr>
<?php endif; ?>

    </table>

    <div class="thank-you">
        Merci d'avoir choisi 47 Points Hôtel.<br>
        Nous espérons vous revoir bientôt !
    </div>

    <div class="signatures">
        <div><div class="sign-line">Signature du client</div></div>
        <div><div class="sign-line">Représentant de l'hôtel</div></div>
    </div>
</div>

</body>
</html>