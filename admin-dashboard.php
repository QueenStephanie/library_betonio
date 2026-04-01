<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
  redirect('admin-login.php');
}

// Check for admin welcome alert
$show_admin_welcome = isset($_SESSION['show_admin_welcome']);
if ($show_admin_welcome) {
  unset($_SESSION['show_admin_welcome']);
}

$page_alerts = [];
if ($show_admin_welcome) {
  $page_alerts[] = [
    'type' => 'success',
    'title' => 'Admin Access Granted',
    'message' => 'Welcome back, Administrator. You now have full system access.'
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | QueenLib Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/admin.css">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-page-body">
  <header class="site-header">
    <div class="container nav-wrap">
      <a href="admin-dashboard.php" class="brand">QueenLib Admin</a>
      <div class="nav-actions">
        <a class="nav-link" href="admin-profile.php">Profile</a>
        <a class="button button-small button-primary" href="#" onclick="adminLogoutConfirm(event)">Logout</a>
      </div>
    </div>
  </header>

  <main class="container admin-content-wrap">
    <section class="admin-main-card">
      <h2>Developer Information</h2>
      <p class="admin-subtitle">System administrator access granted</p>

      <div class="admin-grid-two">
        <article class="admin-panel-card">
          <div class="admin-user-header">
            <span class="admin-avatar" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              </svg>
            </span>
            <div>
              <h3>Queen Stephanie C. Betonio</h3>
              <p>System Administrator</p>
            </div>
          </div>

          <dl class="admin-meta-list">
            <div>
              <dt>Email</dt>
              <dd>queenstephanie@nmsc.edu.ph</dd>
            </div>
            <div>
              <dt>Phone</dt>
              <dd>09106370493</dd>
            </div>
            <div>
              <dt>Date of Birth</dt>
              <dd>March 5, 2005</dd>
            </div>
            <div>
              <dt>Role</dt>
              <dd>Super Administrator</dd>
            </div>
            <div>
              <dt>Last Login</dt>
              <dd>Mar 27, 2026, 08:45 AM</dd>
            </div>
          </dl>

          <div class="admin-access-row">
            <span>Access Level</span>
            <strong>Full Access</strong>
          </div>
        </article>

        <article class="admin-panel-card admin-profile-pic">
          <img src="images/admin_pic.jpg" alt="Administrator Profile Picture">
          <p class="admin-image-caption">Queen Stephanie C. Betonio</p>
        </article>
      </div>

      <div class="admin-stats-grid">
        <article class="admin-stat-card">
          <strong>1,247</strong>
          <span>Total Users</span>
        </article>
        <article class="admin-stat-card">
          <strong>3,542</strong>
          <span>Books in Catalog</span>
        </article>
        <article class="admin-stat-card">
          <strong>892</strong>
          <span>Active Loans</span>
        </article>
      </div>
    </section>
  </main>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
  <script>
    function adminLogoutConfirm(e) {
      e.preventDefault();
      SweetAlerts.warning(
        'Logout',
        'Are you sure you want to logout from admin?',
        'Yes, Logout',
        'Cancel',
        function() {
          window.location.href = <?php echo json_encode(appPath('admin-logout.php')); ?>;
        }
      );
    }
  </script>
</body>

</html>
