<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('librarian-dashboard');

$currentUserEmail = (string)($_SESSION['user_email'] ?? 'librarian@local.librarian');
$currentRole = PermissionGate::resolveAdminRole();
$roleLabel = PermissionGate::getRoleLabel($currentRole);
$roleBadgeClass = PermissionGate::getRoleBadgeClass($currentRole);

$mainCssFile = APP_ROOT . '/public/css/main.css';
$adminCssFile = APP_ROOT . '/public/css/admin.css';
$mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
$adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
$mainCssHref = htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8');
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8');

$page_alerts = [];
$flash = getFlash();
if (is_array($flash) && isset($flash['type'], $flash['message'])) {
  $page_alerts[] = [
    'type' => (string)$flash['type'],
    'title' => 'Notice',
    'message' => (string)$flash['message'],
  ];
}

$summary = [
  'stats' => [
    'catalog_titles' => 0,
    'available_copies' => 0,
    'active_loans' => 0,
    'overdue_loans' => 0,
    'pending_reservations' => 0,
    'ready_reservations' => 0,
  ],
  'missing_tables' => [],
  'data_available' => false,
];

try {
  $summary = LibrarianPortalRepository::getDashboardSummary($db);
} catch (Exception $e) {
  error_log('librarian-dashboard summary error: ' . $e->getMessage());
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Dashboard Unavailable',
    'message' => 'Unable to load circulation summary at this time.',
  ];
}

$missingTables = array_map('strval', $summary['missing_tables'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Librarian Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body portal-role-librarian">
  <div class="admin-shell">
    <?php
    $portalRole = 'librarian';
    $portalCurrentPage = 'dashboard';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $roleLabel;
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Librarian Dashboard</h1>
        <p>Operational summary for circulation, reservations, catalog, and fines.</p>
      </header>

      <?php if (!$summary['data_available']): ?>
        <div class="admin-alert admin-alert-warning" role="status" aria-live="polite">
          Some circulation tables are missing: <?php echo htmlspecialchars(implode(', ', $missingTables), ENT_QUOTES, 'UTF-8'); ?>.
          Dashboard uses safe fallback values until migrations are complete.
        </div>
      <?php endif; ?>

      <section class="admin-card">
        <div class="admin-card-header">
          <h2>Current Operations</h2>
          <p>Live counts from circulation tables with schema-safe fallback.</p>
        </div>
        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['catalog_titles']; ?></strong>
            <span>Catalog Titles</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['available_copies']; ?></strong>
            <span>Available Copies</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['active_loans']; ?></strong>
            <span>Active Loans</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['overdue_loans']; ?></strong>
            <span>Overdue Loans</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['pending_reservations']; ?></strong>
            <span>Pending Reservations</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$summary['stats']['ready_reservations']; ?></strong>
            <span>Ready for Pickup</span>
          </article>
        </div>
      </section>

      <section class="admin-card" style="margin-top:16px;">
        <div class="admin-card-header">
          <h2>Quick Access</h2>
          <p>Use dedicated pages for each workflow.</p>
        </div>
        <div class="admin-stats-row">
          <article class="admin-stat-tile"><strong>Circulation</strong><span>Active loans, overdues, and check-ins.</span></article>
          <article class="admin-stat-tile"><strong>Books</strong><span>Catalog search and copy visibility.</span></article>
          <article class="admin-stat-tile"><strong>Reservations</strong><span>Queue review and approvals.</span></article>
          <article class="admin-stat-tile"><strong>Fines</strong><span>Collected fines and month-to-date summary.</span></article>
          <article class="admin-stat-tile"><strong>Role</strong><span class="admin-badge <?php echo htmlspecialchars($roleBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span></article>
        </div>
      </section>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
