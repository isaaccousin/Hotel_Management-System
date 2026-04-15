<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

$stmt = $conn->prepare("SELECT * FROM card_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['status' => $task ? 'ok' : 'empty', 'task' => $task]);