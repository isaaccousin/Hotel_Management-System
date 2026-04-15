<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || (!isset($_SESSION['force_reset']) && !isset($_SESSION['role']))) {
    header("Location: login.php");
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';

// Language strings
$texts = [
    'en' => [
        'title' => 'Change Password',
        'heading' => 'Change Your Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'submit' => 'Change Password',
        'mismatch' => 'Passwords do not match.',
        'invalid' => 'Password must be at least 8 characters, include uppercase, lowercase, a number, and a special character.',
        'success' => 'Password updated successfully!',
        'error' => 'Error updating password.',
        'strength' => 'Password Strength'
    ],
    'fr' => [
        'title' => 'Changer le mot de passe',
        'heading' => 'Changez votre mot de passe',
        'new_password' => 'Nouveau mot de passe',
        'confirm_password' => 'Confirmez le mot de passe',
        'submit' => 'Changer le mot de passe',
        'mismatch' => 'Les mots de passe ne correspondent pas.',
        'invalid' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.',
        'success' => 'Mot de passe mis à jour avec succès !',
        'error' => 'Erreur lors de la mise à jour du mot de passe.',
        'strength' => 'Solidité du mot de passe'
    ]
];

$t = $texts[$lang];
$popupMessage = '';
$popupType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $popupMessage = $t['mismatch'];
        $popupType = 'error';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@#$%^&*!]).{8,}$/', $new_password)) {
        $popupMessage = $t['invalid'];
        $popupType = 'error';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, force_password_reset = 0 WHERE id = ?");
        if ($stmt->execute([$hashed, $_SESSION['user_id']])) {
            // ✅ Success — destroy session and redirect with JS
            $_SESSION['password_reset_success'] = true;
        } else {
            $popupMessage = $t['error'];
            $popupType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $t['title'] ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(to bottom right, #f9f6f2, #e8d9c9);
            font-family: 'Open Sans', sans-serif;
        }
        .change-container {
            max-width: 420px;
            margin: 80px auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            border: 2px solid #d4af37;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        .change-container h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .btn-submit {
            background: #d4af37;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn-submit:hover {
            background: #b8962c;
        }
        #strengthBar {
            height: 8px;
            background-color: #ccc;
            border-radius: 4px;
            margin-top: 5px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
<div class="change-container">
    <h2><?= $t['heading'] ?></h2>

    <form method="POST" id="changeForm">
        <div class="form-group">
            <label><?= $t['new_password'] ?></label>
            <input type="password" name="new_password" id="new_password" required>
            <div id="strengthText" style="margin-top:6px;font-size:13px;"><?= $t['strength'] ?>:</div>
            <div id="strengthBar"></div>
        </div>
        <div class="form-group">
            <label><?= $t['confirm_password'] ?></label>
            <input type="password" name="confirm_password" id="confirm_password" required>
        </div>
        <button type="submit" class="btn-submit"><?= $t['submit'] ?></button>
    </form>
</div>

<script>
    // Password Strength Meter
    const passwordInput = document.getElementById("new_password");
    const strengthBar = document.getElementById("strengthBar");
    const strengthText = document.getElementById("strengthText");

    passwordInput.addEventListener("input", function () {
        const val = passwordInput.value;
        let score = 0;
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/\d/.test(val)) score++;
        if (/[@#$%^&*!]/.test(val)) score++;

        let strength = ["", "25%", "50%", "75%", "100%"][score];
        strengthBar.style.width = strength;
        strengthBar.style.background = score < 3 ? 'red' : score < 4 ? 'orange' : 'green';
    });

    // SweetAlert2: Show result after POST
    <?php if (!empty($popupMessage)): ?>
    Swal.fire({
        icon: '<?= $popupType ?>',
        title: '<?= $popupMessage ?>',
        showConfirmButton: true
    });
    <?php endif; ?>

    // Redirect on success using session flag
    <?php if (isset($_SESSION['password_reset_success'])): ?>
    Swal.fire({
        icon: 'success',
        title: '<?= $t['success'] ?>',
        timer: 2000,
        showConfirmButton: false
    }).then(() => {
        window.location.href = '../modules/dashboard.php';
    });
    <?php unset($_SESSION['password_reset_success']); endif; ?>
</script>
</body>
</html>