<?php

require_once '../includes/db.php';
require_once '../auth/session_check.php';

// Only allow access if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$lang = $_SESSION['lang'] ?? 'en';
$texts = [
  'en' => [
    'title' => 'Staff Management',
    'add' => 'Add Staff',
    'username' => 'Username',
    'email' => 'Email',
    'password' => 'Password',
    'role' => 'Role',
    'action' => 'Action',
    'delete' => 'Delete',
    'reset' => 'Reset Password',
    'logs' => 'System Logs',
    'nav_dashboard' => 'Dashboard',
    'nav_checkin' => 'Check-In',
    'nav_checkout' => 'Check-Out',
    'nav_book' => 'Booking',
    'nav_rooms' => 'Room Management',
    'nav_staff' => 'Staff Management',
    'nav_logout' => 'Logout'
  ],
  'fr' => [
    'title' => 'Gestion du Personnel',
    'add' => 'Ajouter Personnel',
    'username' => "Nom d'utilisateur",
    'email' => 'Email',
    'password' => 'Mot de passe',
    'role' => 'Rôle',
    'action' => 'Action',
    'delete' => 'Supprimer',
    'reset' => 'Réinitialiser le mot de passe',
    'logs' => 'Journaux système',
    'nav_dashboard' => 'Tableau de bord',
    'nav_checkin' => 'Enregistrement',
    'nav_checkout' => 'Départ',
    'nav_book' => 'Réserver',
    'nav_rooms' => 'Chambres',
    'nav_staff' => 'Personnel',
    'nav_logout' => 'Déconnexion'
  ]
];
$t = $texts[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['title'] ?> | 47 Points Hotel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background: #f9f6f2; font-family: 'Open Sans', sans-serif; }
    header {
      background-color: #1a1a1a;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }
    .logo-nav {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logo-nav img {
      height: 45px;
    }
    .hamburger {
      display: none;
      font-size: 24px;
      cursor: pointer;
    }
    nav {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    nav a {
      color: white;
      text-decoration: none;
      font-weight: 500;
      padding: 6px 12px;
      font-size: 15px;
      border-radius: 5px;
      transition: background 0.3s ease;
    }
    nav a:hover {
      background-color: #333;
    }
    @media screen and (max-width: 768px) {
      .hamburger {
        display: block;
      }
      nav {
        display: none;
        flex-direction: column;
        width: 100%;
        background-color: #1a1a1a;
      }
      nav.show {
        display: flex;
      }
    }
    .container { max-width: 1000px; margin: auto; padding: 40px 20px; }
    .card-header { background-color: #d4af37; color: #1a1a1a; font-weight: bold; }
    .btn-primary { background-color: #d4af37; border: none; }
    .btn-primary:hover { background-color: #c1a232; }
    table th { background: #eaeaea; }
    td[contenteditable] { background-color: #fff9e6; cursor: pointer; }
  </style>
</head>
<body>
<header>
  <div class="logo-nav">
    <img src="../assets/images/47.png" alt="Hotel Logo">
    <strong>47 Points Hotel</strong>
    <div class="hamburger" onclick="toggleMenu()">&#9776;</div>
  </div>
  <nav id="mainNav">
    <a href="dashboard.php"><?= $t['nav_dashboard'] ?></a>
    <a href="checkin.php"><?= $t['nav_checkin'] ?></a>
    <a href="checkout.php"><?= $t['nav_checkout'] ?></a>
    <a href="booking.php"><?= $t['nav_book'] ?></a>
    
    <?php if (in_array($_SESSION['role'], ['admin', 'manager'])): ?>
  <a href="room_management.php"><?= $t['nav_rooms'] ?></a>
<?php endif; ?>

   <?php if ($_SESSION['role'] === 'admin'): ?>
  <a href="staff_management.php"><?= $t['nav_staff'] ?></a>
<?php endif; ?>
 
    
   
    <a href="../auth/logout.php"><?= $t['nav_logout'] ?></a>
  </nav>
</header>
<script>
function toggleMenu() {
  document.getElementById('mainNav').classList.toggle('show');
}
</script>
<div class="container">
  <h2 class="text-center my-4"><?= $t['title'] ?></h2>
  <div class="card shadow-sm mb-4">
    <div class="card-header">+ <?= $t['add'] ?></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><input id="username" class="form-control" placeholder="<?= $t['username'] ?>"></div>
        <div class="col-md-3"><input id="email" type="email" class="form-control" placeholder="<?= $t['email'] ?>"></div>
        <div class="col-md-3"><input id="password" type="password" class="form-control" placeholder="<?= $t['password'] ?>"></div>
        <div class="col-md-2">
          <select id="role" class="form-select">
            <option value="staff">Staff</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="col-md-1"><button class="btn btn-primary w-100" onclick="addUser()">+ </button></div>
      </div>
    </div>
  </div>
  <div class="row mb-3">
    <div class="col-md-6"><input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by name or email..."></div>
    <div class="col-md-3">
      <select id="roleFilter" class="form-select">
        <option value="">All Roles</option>
        <option value="staff">Staff</option>
        <option value="manager">Manager</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div class="col-md-3 text-end"><span id="paginationControls"></span></div>
  </div>
  <table class="table table-bordered bg-white">
    <thead>
      <tr>
        <th><?= $t['username'] ?></th>
        <th><?= $t['email'] ?></th>
        <th><?= $t['role'] ?></th>
        <th><?= $t['action'] ?></th>
      </tr>
    </thead>
    <tbody id="userTable"><tr><td colspan="4">Loading...</td></tr></tbody>
  </table>
  <div class="text-end mt-4">
    <a href="system_logs.php" class="btn btn-outline-dark">📜 <?= $t['logs'] ?></a>
  </div>
</div>
<script>
let currentPage = 1;
const itemsPerPage = 5;
function loadUsers() {
  const search = $('#searchInput').val().trim();
  const role = $('#roleFilter').val();
  $.get('process_staff.php', {
    action: 'load', page: currentPage, limit: itemsPerPage, search, role, t: new Date().getTime()
  }, function(res) {
    if (res.status === 'success') {
      $('#userTable').html(res.html);
      updatePagination(res.totalPages);
    } else {
      $('#userTable').html('<tr><td colspan="4" class="text-danger text-center">Failed to load staff.</td></tr>');
    }
  }, 'json');
}
function updatePagination(totalPages) {
  let html = '';
  for (let i = 1; i <= totalPages; i++) {
    html += `<button class="btn btn-sm ${i === currentPage ? 'btn-dark' : 'btn-outline-dark'} me-1" onclick="goToPage(${i})">${i}</button>`;
  }
  $('#paginationControls').html(html);
}
function goToPage(page) {
  currentPage = page;
  loadUsers();
}
function addUser() {
  const username = $('#username').val().trim();
  const email = $('#email').val().trim();
  const password = $('#password').val().trim();
  const role = $('#role').val();
  if (!username || !email || !password || !role) {
    Swal.fire('Missing Fields', 'Please fill in all fields.', 'warning');
    return;
  }
  $.post('process_staff.php', {
    action: 'add', username, email, password, role
  }, function(res) {
    if (res.status === 'success') {
      Swal.fire('Success', res.message, 'success');
      $('#username, #email, #password').val('');
      $('#role').val('staff');
      loadUsers();
    } else {
      Swal.fire('Error', res.message, 'error');
    }
  }, 'json');
}
function deleteUser(id) {
  Swal.fire({
    title: 'Confirm Deletion', text: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('process_staff.php', { action: 'delete', id }, function(res) {
        Swal.fire(res.status === 'success' ? 'Deleted' : 'Error', res.message, res.status);
        if (res.status === 'success') loadUsers();
      }, 'json');
    }
  });
}
function resetPassword(id) {
  Swal.fire({
    title: 'Reset Password', text: 'Reset to default (47points#2025)?', icon: 'question', showCancelButton: true, confirmButtonText: 'Yes'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post('process_staff.php', { action: 'reset_password', id }, function(res) {
        Swal.fire(res.status === 'success' ? 'Success' : 'Error', res.message, res.status);
      }, 'json');
    }
  });
}
$(document).on('blur', '.editable', function () {
  const id = $(this).data('id');
  const field = $(this).data('field');
  const value = $(this).text().trim();
  $.post('process_staff.php', { action: 'update_field', id, field, value }, function (res) {
    if (res.status === 'success') {
      Swal.fire('Updated', res.message, 'success');
    } else {
      Swal.fire('Error', res.message, 'error');
    }
  }, 'json');
});
$(document).ready(function () {
  loadUsers();
  $('#searchInput, #roleFilter').on('input change', function () {
    currentPage = 1;
    loadUsers();
  });
});
</script>

<script src="../assets/js/session_monitor.js"></script>

</body>
</html>