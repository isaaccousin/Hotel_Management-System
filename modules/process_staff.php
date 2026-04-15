<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/db.php';
require_once 'log_room.php'; // Load the logging function


if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'director'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'load') {
  $page = max(1, intval($_GET['page'] ?? 1));
  $limit = max(1, intval($_GET['limit'] ?? 5));
  $search = trim($_GET['search'] ?? '');
  $role = trim($_GET['role'] ?? '');

  $where = "WHERE role != 'director'";
  $params = [];

  if ($search) {
    $where .= " AND (username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
  }
  if ($role) {
    $where .= " AND role = :role";
    $params[':role'] = $role;
  }

  $totalStmt = $conn->prepare("SELECT COUNT(*) FROM users $where");
  $totalStmt->execute($params);
  $total = $totalStmt->fetchColumn();
  $totalPages = ceil($total / $limit);
  $offset = ($page - 1) * $limit;

  $stmt = $conn->prepare("SELECT id, username, email, role FROM users $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
  $stmt->execute($params);
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $html = '';
  foreach ($users as $u) {
    $html .= "<tr>
      <td>".htmlspecialchars($u['username'])."</td>
      <td contenteditable='true' class='editable' data-id='{$u['id']}' data-field='email'>".htmlspecialchars($u['email'])."</td>
      <td contenteditable='true' class='editable' data-id='{$u['id']}' data-field='role'>".htmlspecialchars($u['role'])."</td>
      <td>
        <button class='btn btn-sm btn-danger' onclick='deleteUser({$u['id']})'>Delete</button>
        <button class='btn btn-sm btn-warning' onclick='resetPassword({$u['id']})'>Reset</button>
      </td>
    </tr>";
  }

  echo json_encode(['status' => 'success', 'html' => $html, 'totalPages' => $totalPages]);
  exit;
}

if ($action === 'add') {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role'];
  $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
  $stmt->execute([$username, $email, $password, $role]);
  echo json_encode(['status' => 'success', 'message' => 'User added successfully.']);
  exit;
}

if ($action === 'delete') {
  $id = intval($_POST['id']);
  $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
  echo json_encode(['status' => 'success', 'message' => 'User deleted.']);
  exit;
}

if ($action === 'reset_password') {
  $id = intval($_POST['id']);
  $newPassword = password_hash('47points#2025', PASSWORD_DEFAULT);
  $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newPassword, $id]);
  echo json_encode(['status' => 'success', 'message' => 'Password reset to default.']);
  exit;
}

if ($action === 'update_field') {
  $id = intval($_POST['id']);
  $field = $_POST['field'];
  $value = trim($_POST['value']);

  if (!in_array($field, ['email', 'role'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid field']);
    exit;
  }

  $stmt = $conn->prepare("UPDATE users SET $field = ? WHERE id = ?");
  $stmt->execute([$value, $id]);
  echo json_encode(['status' => 'success', 'message' => ucfirst($field) . ' updated.']);
  exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
exit;