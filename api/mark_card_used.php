<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE card_queue SET status='used', used_at=NOW() WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Invalid card ID']);
}