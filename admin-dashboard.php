<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

requireAdminAuth();

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
  <title>QueenLib | Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/admin.css">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-brand-wrap">
        <div class="admin-brand">Libris</div>
        <div class="admin-brand-sub">Admin Portal</div>
      </div>

      <div class="admin-sidebar-profile">
        <span class="admin-sidebar-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <div class="admin-sidebar-name">Admin</div>
          <div class="admin-sidebar-role">System Administrator</div>
        </div>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-item is-active" href="admin-dashboard.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 10.5L12 3L21 10.5V21H3V10.5Z" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Dashboard</span>
        </a>
        <a class="admin-nav-item" href="admin-users.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 13C18.21 13 20 11.21 20 9C20 6.79 18.21 5 16 5" stroke="currentColor" stroke-width="1.6" />
            <path d="M4 20C4.9 17.3 7.7 15.5 11 15.5C14.3 15.5 17.1 17.3 18 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M11 13C13.21 13 15 11.21 15 9C15 6.79 13.21 5 11 5C8.79 5 7 6.79 7 9C7 11.21 8.79 13 11 13Z" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>User Management</span>
        </a>
        <a class="admin-nav-item" href="admin-profile.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.6" />
            <path d="M4.5 20C5.4 17.3 8.1 15.5 12 15.5C15.9 15.5 18.6 17.3 19.5 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
          </svg>
          <span>Profile</span>
        </a>
        <a class="admin-nav-item" href="admin-change-password.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 10V8C6 5.79 7.79 4 10 4H14C16.21 4 18 5.79 18 8V10" stroke="currentColor" stroke-width="1.6" />
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Change Password</span>
        </a>
        <a class="admin-nav-item admin-nav-logout" href="admin-logout.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 7L20 12L15 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M20 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M4 4H9V20H4" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Log Out</span>
        </a>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, Administrator</p>
      </header>

      <section class="admin-card">
        <div class="admin-card-header">
          <h2>Developer Information</h2>
          <p>System administrator access granted</p>
        </div>

        <div class="admin-dashboard-grid">
          <article class="admin-info-card">
            <div class="admin-info-header">
              <span class="admin-sidebar-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.6" />
                  <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                </svg>
              </span>
              <div>
                <h3>Administrator</h3>
                <span>System Developer</span>
              </div>
            </div>

            <div class="admin-info-list">
              <div class="admin-info-row">
                <span>Email</span>
                <strong>admin@libris.dev</strong>
              </div>
              <div class="admin-info-row">
                <span>Role</span>
                <strong>Super Administrator</strong>
              </div>
              <div class="admin-info-row">
                <span>Last Login</span>
                <strong>Apr 1, 2026, 03:22 PM</strong>
              </div>
              <div class="admin-info-row">
                <span>Access Level</span>
                <span class="admin-access-pill">Full Access</span>
              </div>
            </div>
          </article>

          <article class="admin-info-card admin-workspace-card">
            <span class="admin-card-caption">Developer Workspace</span>
            <img src="images/admin_pic.jpg" alt="Developer workspace">
            <p class="admin-card-caption">Admin workspace environment</p>
          </article>
        </div>

        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong>1,247</strong>
            <span>Total Users</span>
          </article>
          <article class="admin-stat-tile">
            <strong>3,542</strong>
            <span>Books in Catalog</span>
          </article>
          <article class="admin-stat-tile">
            <strong>892</strong>
            <span>Active Loans</span>
          </article>
        </div>
      </section>
    </main>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>