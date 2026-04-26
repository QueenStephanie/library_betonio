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

$cssPaths = getLibrarianCssPaths();
$mainCssHref = $cssPaths['main'];
$adminCssHref = $cssPaths['admin'];
$librarianCssHref = $cssPaths['librarian'];

$page_alerts = getStoredPageAlerts();

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
  <link rel="stylesheet" href="<?php echo $librarianCssHref; ?>">
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

    <main class="admin-main librarian-main">
      <div class="librarian-page">
        <div class="librarian-shell">
          <section class="librarian-hero">
            <div class="librarian-hero-copy">
              <span class="librarian-eyebrow">Librarian dashboard</span>
              <h1>Manage library operations</h1>
              <p class="librarian-page-subtitle">Track circulation, reservations, catalog, and fines.</p>
            </div>
            <aside class="librarian-hero-card">
              <span class="librarian-hero-card-label">Access snapshot</span>
              <strong><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
              <p><?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?></p>
            </aside>
          </section>

          <?php if (!$summary['data_available']): ?>
            <div class="librarian-alert librarian-alert-warning" role="status" aria-live="polite">
              Some circulation tables are missing: <?php echo htmlspecialchars(implode(', ', $missingTables), ENT_QUOTES, 'UTF-8'); ?>.
              Dashboard uses safe fallback values until migrations are complete.
            </div>
          <?php endif; ?>

          <section class="librarian-stat-grid" aria-label="Current operations summary">
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Catalog Titles</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['catalog_titles']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Available Copies</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['available_copies']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Active Loans</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['active_loans']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Overdue Loans</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['overdue_loans']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Pending Reservations</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['pending_reservations']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Ready for Pickup</p>
              <p class="librarian-stat-value"><?php echo (int)$summary['stats']['ready_reservations']; ?></p>
            </article>
          </section>

          <section class="librarian-card librarian-surface-card">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Quick access</span>
                <h2>Quick links</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <div class="librarian-action-grid">
                <article class="librarian-action-card"><strong>Circulation</strong><span>Loans and check-ins.</span></article>
                <article class="librarian-action-card"><strong>Books</strong><span>Catalog and copies.</span></article>
                <article class="librarian-action-card"><strong>Reservations</strong><span>Queue actions.</span></article>
                <article class="librarian-action-card"><strong>Fines</strong><span>Collections and totals.</span></article>
                <article class="librarian-action-card"><strong>Role</strong><span class="admin-badge <?php echo htmlspecialchars($roleBadgeClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span></article>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
