<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/AdminProfileRepository.php';
require_once APP_ROOT . '/backend/classes/CirculationRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('admin-dashboard');

$adminUsername = (string)($_SESSION['user_email'] ?? 'admin@local.admin');
$isSuperadmin = isCurrentAdminSuperadmin();
$adminRoleLabel = $isSuperadmin ? 'System Administrator / Developer' : 'Administrator / Developer';
$admin_profile = [
  'name' => 'System Administrator',
  'email' => 'queenstephanie.betonio@nmsc.edu.ph',
  'phone' => '(555) 123-4567',
  'admin_id' => 'ADM-0045',
  'address' => 'Administrator Office',
  'appointment_date' => date('F j, Y'),
  'appointment_date_value' => date('Y-m-d'),
  'access_level' => 'Full Access - Super Administrator',
];

try {
  $admin_profile = AdminProfileRepository::getOrCreate($db, $adminUsername);
} catch (Exception $e) {
  error_log('admin-dashboard profile load error: ' . $e->getMessage());
}

// Enforce requested profile values for dashboard presentation.
$admin_profile['email'] = 'queenstephanie.betonio@nmsc.edu.ph';
$admin_profile['phone'] = '(555) 123-4567';
$admin_profile['admin_id'] = 'ADM-0045';
$admin_profile['address'] = 'Administrator Office';

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

$circulationOverview = [
  'catalog_titles' => 0,
  'available_copies' => 0,
  'active_loans' => 0,
  'active_reservations' => 0,
];
$circulationDataAvailable = false;
$circulationUnavailableMessage = 'Circulation metrics are temporarily unavailable. Please try again later.';

try {
  $circulationOverview = CirculationRepository::getAdminOverview($db);
  $circulationDataAvailable = true;
} catch (Exception $e) {
  error_log('admin dashboard circulation summary error: ' . $e->getMessage());

  $errorMessage = strtolower($e->getMessage());
  if (strpos($errorMessage, 'doesn\'t exist') !== false || strpos($errorMessage, 'unknown table') !== false) {
    $circulationUnavailableMessage = 'Circulation module is scaffolded, but circulation tables are not available yet. Run the circulation migration to enable live metrics.';
  }
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
        <div class="admin-brand">QueenLib</div>
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
          <div class="admin-sidebar-name"><?php echo htmlspecialchars($admin_profile['name'], ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="admin-sidebar-role"><?php echo htmlspecialchars($adminRoleLabel, ENT_QUOTES, 'UTF-8'); ?></div>
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
        <a class="admin-nav-item" href="admin-change-password.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 10V8C6 5.79 7.79 4 10 4H14C16.21 4 18 5.79 18 8V10" stroke="currentColor" stroke-width="1.6" />
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Change Password</span>
        </a>
        <a class="admin-nav-item" href="admin-fines.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 5H20V19H4V5Z" stroke="currentColor" stroke-width="1.6" />
            <path d="M8 14L11 11L13 13L16 10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <span>Fines Report</span>
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

      <section class="admin-card" id="about-me">
        <div class="admin-card-header">
          <h2>About Me</h2>
          <p>Portfolio-style overview of your administrator profile and capabilities.</p>
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
                <h3><?php echo htmlspecialchars($admin_profile['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <span><?php echo htmlspecialchars($adminRoleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>

            <div class="admin-info-list">
              <div class="admin-info-row">
                <span>Email</span>
                <strong><?php echo htmlspecialchars($admin_profile['email'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
              <div class="admin-info-row">
                <span>Phone</span>
                <strong><?php echo htmlspecialchars($admin_profile['phone'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
              <div class="admin-info-row">
                <span>Administrator ID</span>
                <strong><?php echo htmlspecialchars($admin_profile['admin_id'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
              <div class="admin-info-row">
                <span>Address</span>
                <strong><?php echo htmlspecialchars($admin_profile['address'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
            </div>
          </article>

          <article class="admin-info-card admin-workspace-card">
            <span class="admin-card-caption">Profile Snapshot</span>
            <img src="images/admin_pic.jpg" alt="Developer workspace">
            <p class="admin-card-caption">Trusted access owner for the QueenLib administration workspace.</p>

            <div class="admin-info-list admin-aboutme-meta">
              <div class="admin-info-row">
                <span>Appointment Date</span>
                <strong><?php echo htmlspecialchars($admin_profile['appointment_date'], ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
              <div class="admin-info-row">
                <span>Access Level</span>
                <span class="admin-access-pill"><?php echo htmlspecialchars($admin_profile['access_level'], ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </article>
        </div>

        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong>Full Stack Developer</strong>
            <span>Skilled in end-to-end development across frontend and backend systems.</span>
          </article>
          <article class="admin-stat-tile">
            <strong>Frontend Skills</strong>
            <span>HTML and CSS for responsive, modern, and user-friendly interfaces.</span>
          </article>
          <article class="admin-stat-tile">
            <strong>Backend Skills</strong>
            <span>PHP and MySQL for robust server-side logic and database management.</span>
          </article>
        </div>
      </section>

      <section class="admin-card" id="circulation-overview">
        <div class="admin-card-header">
          <h2>Circulation Overview</h2>
          <p>Live snapshot of books, inventory, loans, and reservations.</p>
        </div>

        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong><?php echo (int)$circulationOverview['catalog_titles']; ?></strong>
            <span>Catalog Titles</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$circulationOverview['available_copies']; ?></strong>
            <span>Available Copies</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$circulationOverview['active_loans']; ?></strong>
            <span>Active / Overdue Loans</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$circulationOverview['active_reservations']; ?></strong>
            <span>Active Reservations</span>
          </article>
        </div>

        <?php if (!$circulationDataAvailable): ?>
          <p class="admin-demo-note" style="margin-top: 12px;"><?php echo htmlspecialchars($circulationUnavailableMessage, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
