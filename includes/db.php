<?php
$host = 'localhost';
$dbname = 'isakal_sevenpointhotel';
$username = 'isakal_sevenpointhotel';
$password = 'BeckyIlungaBishop#77';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>