<?php
// index.php - Entry point to Hotel Management System
session_start();

// Redirect to login page
header("Location: auth/login.php");
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting...</title>
    <meta http-equiv="refresh" content="2;url=auth/login.php">
</head>
<body>
    <p>Redirecting to the <a href="auth/login.php">Login Page</a>...</p>
</body>
</html>