<?php 
session_start(); 
if (isset($_GET['timeout']) && isset($_SESSION['timeout_message'])): ?>
  <div class="error">
    <?= $_SESSION['timeout_message']; ?>
    <?php unset($_SESSION['timeout_message']); ?>
  </div>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title id="login_title">Login | Hotel Management</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body {
            background: linear-gradient(to bottom right, #f9f6f2, #e8d9c9);
            font-family: 'Open Sans', sans-serif;
        }
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-box {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            border: 2px solid #d4af37;
        }
        .login-box img {
            width: 90px;
            margin-bottom: 20px;
        }
        .login-box h2 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            margin-bottom: 25px;
            color: #333;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            background-color: #d4af37;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-login:hover {
            background-color: #b8962c;
        }
        .footer-links {
            margin-top: 18px;
        }
        .footer-links a {
            color: #b8962c;
            font-size: 14px;
            text-decoration: none;
        }
        .lang-switch {
            margin-top: 25px;
        }
        .lang-switch button {
            text-decoration: none;
            padding: 6px 14px;
            margin: 0 5px;
            background: #f2f2f2;
            border-radius: 20px;
            color: #555;
            font-weight: 600;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .lang-switch button:hover {
            background-color: #d4af37;
            color: white;
            border-color: #d4af37;
        }
        .error {
            background-color: #ffe0e0;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-box">
        <img src="../assets/images/47.png" alt="Hotel Logo">
        <h2 id="login_heading">Login to your account</h2>
        
        <?php if (isset($_GET['error'])): ?>
      <div class="error">
        <?= htmlspecialchars($_GET['error']); ?>
      </div>
    <?php endif; ?>

        <form method="POST" action="process_login.php">
            <input type="hidden" name="lang" id="lang_input" value="en">

            <div class="form-group">
                <label id="label_username">Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label id="label_password">Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" class="btn-login" id="login_button">Login</button>
        </form>

        <div class="footer-links">
            <a href="forgot_password.php" id="forgot_password">Forgot password?</a>
        </div>

        <div class="lang-switch">
            <button type="button" class="lang-btn" data-lang="en">English</button>
            <button type="button" class="lang-btn" data-lang="fr">Français</button>
        </div>
    </div>
</div>

<script>
    // Set hidden input and submit form on language click
    $('.lang-btn').click(function () {
        let lang = $(this).data('lang');
        $('#lang_input').val(lang);
        $('form').submit();
    });

    // Optional: preload based on sessionStorage if desired
    $(document).ready(function () {
        let storedLang = sessionStorage.getItem('selected_lang');
        if (storedLang) {
            $('#lang_input').val(storedLang);
        }
    });
</script>
</body>
</html>