<?php
session_start();
require_once '../../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'director'])) {
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'load') {
  $users = $conn->query("SELECT id, username, email, role FROM users WHERE role != 'director'")->fetchAll(PDO::FETCH_ASSOC);
  foreach ($users as $u) {
    echo "<tr>
            <td>" . htmlspecialchars($u['username']) . "</td>
            <td>" . htmlspecialchars($u['email']) . "</td>
            <td>" . ucfirst($u['role']) . "</td>
            <td>
              <button class='btn btn-sm btn-danger' onclick='deleteUser({$u['id']})'>❌ Delete</button>
              <button class='btn btn-sm btn-secondary ms-2' onclick='resetPassword({$u['id']})'>↺ Reset</button>
            </td>
          </tr>";
  }
  exit();
}

if ($action === 'add') {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
  $role = $_POST['role'];

  if ($username && $email && $password && $role) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $role]);
    echo json_encode(['status' => 'success', 'message' => 'User added successfully.']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'All fields required.']);
  }
  exit();
}

if ($action === 'delete') {
  $id = intval($_POST['id']);
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'director'");
  $stmt->execute([$id]);
  echo json_encode(['status' => 'success', 'message' => 'User deleted.']);
  exit();
}

if ($action === 'reset_password') {
  $id = intval($_POST['id']);
  $defaultPassword = password_hash("Reset#123", PASSWORD_DEFAULT);
  $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  $stmt->execute([$defaultPassword, $id]);
  echo json_encode(['status' => 'success', 'message' => 'Password reset to default.']);
  exit();
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
exit();