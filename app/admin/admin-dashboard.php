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

$page_alerts = [];


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

<body class="admin-portal-body portal-role-admin">
  <div class="admin-shell">
    <?php
    $portalRole = 'admin';
    $portalCurrentPage = 'dashboard';
    $portalIdentityName = (string)$admin_profile['name'];
    $portalIdentityMeta = $adminRoleLabel;
    $portalBrandSub = 'Admin Portal';
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

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
