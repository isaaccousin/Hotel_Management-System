<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

$stmt = $conn->query("SELECT id, name, location, UPPER(HEX(lock_serial)) AS lock_serial FROM locks ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_UNESCAPED_SLASHES);