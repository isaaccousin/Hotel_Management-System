<?php
session_start();
require_once '../includes/db.php';

$lang = $_SESSION['lang'] ?? 'en';

$texts = [
  'en' => [
    'title' => 'Reset Password',
    'heading' => 'Reset Your Password',
    'success' => 'Password reset successfully!',
    'error' => 'Invalid or expired reset token.',
    'mismatch' => 'Passwords do not match.',
    'invalid' => 'Must include uppercase, lowercase, number, special char.',
    'new' => 'New Password',
    'confirm' => 'Confirm Password',
    'submit' => 'Reset Password',
    'strength' => 'Password Strength'
  ],
  'fr' => [
    'title' => 'Réinitialiser le mot de passe',
    'heading' => 'Réinitialisez votre mot de passe',
    'success' => 'Mot de passe réinitialisé avec succès !',
    'error' => 'Lien invalide ou expiré.',
    'mismatch' => 'Les mots de passe ne correspondent pas.',
    'invalid' => 'Inclure une majuscule, minuscule, chiffre, et caractère spécial.',
    'new' => 'Nouveau mot de passe',
    'confirm' => 'Confirmer le mot de passe',
    'submit' => 'Réinitialiser',
    'strength' => 'Solidité du mot de passe'
  ]
];

$t = $texts[$lang];
$valid = false;

if (isset($_GET['token'])) {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at >= NOW()");
    $stmt->execute([$_GET['token']]);
    $reset = $stmt->fetch();

    if ($reset) {
        $valid = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new = $_POST['new_password'];
            $confirm = $_POST['confirm_password'];

            if ($new !== $confirm) {
                $popup = ['type' => 'error', 'msg' => $t['mismatch']];
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$%^&*!]).{8,}$/', $new)) {
                $popup = ['type' => 'error', 'msg' => $t['invalid']];
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $conn->prepare("UPDATE users SET password = ?, force_password_reset = 0 WHERE id = ?")
                    ->execute([$hash, $reset['user_id']]);
                $conn->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);

                $_SESSION['reset_success'] = true;
                header("Location: login.php");
                exit();
            }
        }
    } else {
        $popup = ['type' => 'error', 'msg' => $t['error']];
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?></title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: 'Open Sans', sans-serif;
      background: #f9f6f2;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .box {
      background: white;
      border: 2px solid #d4af37;
      border-radius: 12px;
      padding: 30px;
      width: 100%;
      max-width: 420px;
    }
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-top: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
    #strengthBar {
      height: 8px;
      background-color: #ccc;
      margin-top: 5px;
      border-radius: 4px;
    }
    button {
      width: 100%;
      padding: 12px;
      background: #d4af37;
      border: none;
      color: white;
      font-weight: bold;
      border-radius: 8px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
<?php if ($valid): ?>
<div class="box">
  <h2><?= $t['heading'] ?></h2>
  <form method="POST">
    <label><?= $t['new'] ?></label>
    <input type="password" name="new_password" id="new_password" required>
    <div id="strengthText" style="font-size:13px"><?= $t['strength'] ?>:</div>
    <div id="strengthBar"></div>
    <label style="margin-top:15px;"><?= $t['confirm'] ?></label>
    <input type="password" name="confirm_password" required>
    <button type="submit"><?= $t['submit'] ?></button>
  </form>
</div>
<?php endif; ?>

<script>
  const bar = document.getElementById("strengthBar");
  const input = document.getElementById("new_password");
  input.addEventListener("input", () => {
    const v = input.value;
    let s = 0;
    if (v.length >= 8) s++;
    if (/[A-Z]/.test(v)) s++;
    if (/[a-z]/.test(v)) s++;
    if (/\d/.test(v)) s++;
    if (/[@#$%^&*!]/.test(v)) s++;

    const colors = ["", "red", "orange", "yellowgreen", "green"];
    const widths = ["10%", "25%", "50%", "75%", "100%"];
    bar.style.width = widths[s - 1] || "0%";
    bar.style.background = colors[s - 1] || "#ccc";
  });

  <?php if (!empty($popup)): ?>
  Swal.fire({
    icon: '<?= $popup['type'] ?>',
    title: '<?= $popup['msg'] ?>',
    showConfirmButton: true
  });
  <?php endif; ?>
</script>
</body>
</html>