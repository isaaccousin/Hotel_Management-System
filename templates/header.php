<style>
  header {
    background-color: #1a1a1a;
    color: white;
    padding: 14px 20px;
    font-family: 'Playfair Display', serif;
    font-size: 22px;
  }
  .logo img {
    height: 35px;
  }
  .logo-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
  }
  nav {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 10px;
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
  .logout {
    font-size: 16px;
    padding: 8px 18px;
    border-radius: 6px;
    background: #d4af37;
    color: #1a1a1a;
    font-weight: 700;
    text-decoration: none;
    margin-top: 10px;
  }

  @media screen and (max-width: 768px) {
    nav {
      flex-direction: column;
      gap: 8px;
    }
    .logo-nav {
      flex-direction: column;
      align-items: flex-start;
    }
    .logout {
      align-self: flex-end;
    }
  }
</style>

<header>
  <div class="logo-nav">
    <div class="logo" style="display: flex; align-items: center; gap: 15px;">
      <a href="dashboard.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
        <img src="../assets/images/47.png" alt="Hotel Logo">
        <?= $t['title'] ?>
      </a>
    </div>
    <nav>
      <a href="dashboard.php"><?= $t['nav_dashboard'] ?></a>
      <a href="checkin.php"><?= $t['nav_checkin'] ?></a>
      <a href="checkout.php"><?= $t['nav_checkout'] ?></a>
      <a href="booking.php"><?= $t['nav_book'] ?></a>
      <a href="room_management.php"><?= $t['nav_rooms'] ?></a>
      <a href="staff_management.php"><?= $t['nav_staff'] ?></a>
    </nav>
    <a class="logout" href="../auth/logout.php"><?= $t['nav_logout'] ?></a>
  </div>
</header>