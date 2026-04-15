<?php
require_once '../includes/db.php';

$id = intval($_POST['id']);
$status = $_POST['status']; // 'done' or 'error'

$stmt = $conn->prepare("UPDATE card_queue SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

echo json_encode(['success' => true]);