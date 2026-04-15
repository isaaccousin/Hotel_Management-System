<?php
session_start();
require_once '../includes/db.php';

$lang = $_SESSION['lang'] ?? 'en';

// Bilingual strings
$texts = [
  'en' => [
    'title' => 'Admin Password Reset',
    'heading' => 'Reset Your Password',
    'instruction' => 'Enter your admin username and special passcode.',
    'username' => 'Admin Username',
    'passcode' => 'Special Passcode',
    'submit' => 'Verify',
    'invalid' => 'Invalid admin credentials or passcode.',
    'success' => 'Verification successful. Redirecting...',
  ],
  'fr' => [
    'title' => 'Réinitialisation du mot de passe Admin',
    'heading' => 'Réinitialisez votre mot de passe',
    'instruction' => 'Entrez votre nom d\'utilisateur admin et le mot de passe spécial.',
    'username' => 'Nom d\'utilisateur Admin',
    'passcode' => 'Code spécial',
    'submit' => 'Vérifier',
    'invalid' => 'Identifiants admin ou code invalide.',
    'success' => 'Vérification réussie. Redirection...',
  ]
];

$t = $texts[$lang];
$popup = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $passcode = trim($_POST['passcode'] ?? '');

    // Stored hashed special passcode
    $expected_passcode = '$2y$10$N1/l.67nPk6AyGKfCucGKuvt.meSSfEXX0imY5QCwiA9OT1QZsFf6';

    // Validate admin account
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND role = 'admin' AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Validate admin + special passcode
    if ($user && password_verify($passcode, $expected_passcode)) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['force_reset'] = true;

        header("Location: change_password.php");
        exit();
    } else {
        $popup = ['type' => 'error', 'msg' => $t['invalid']];
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
      max-width: 400px;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      margin-top: 10px;
      border-radius: 8px;
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
<div class="box">
  <h2><?= $t['heading'] ?></h2>
  <form method="POST">
    <label><?= $t['instruction'] ?></label><br>
    <input type="text" name="username" placeholder="<?= $t['username'] ?>" required>
    <input type="password" name="passcode" placeholder="<?= $t['passcode'] ?>" required>
    <button type="submit"><?= $t['submit'] ?></button>
  </form>
</div>

<script>
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