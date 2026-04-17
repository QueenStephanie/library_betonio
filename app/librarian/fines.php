<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/FineReporting.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('librarian-fines');

$currentUserEmail = (string)($_SESSION['user_email'] ?? 'librarian@local.librarian');
$currentRole = PermissionGate::resolveAdminRole();
$roleLabel = PermissionGate::getRoleLabel($currentRole);

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

$report = [
  'period_label' => date('M 1, Y') . ' to ' . date('M j, Y'),
  'total_collections' => 0,
  'total_amount' => 0.0,
  'average_amount' => 0.0,
  'items' => [],
];
$totals = [
  'all_time_collections' => 0,
  'all_time_amount' => 0.0,
];

try {
  $report = FineReporting::getMonthToDateReport($db);
} catch (Exception $e) {
  error_log('librarian-fines report error: ' . $e->getMessage());
  $page_alerts[] = [
    'type' => 'warning',
    'title' => 'Report Limited',
    'message' => 'Month-to-date fines report is unavailable right now.',
  ];
}

try {
  $totals = LibrarianPortalRepository::getFineCollectionTotals($db);
} catch (Exception $e) {
  error_log('librarian-fines totals error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Librarian Fines</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-brand-wrap">
        <div class="admin-brand">QueenLib</div>
        <div class="admin-brand-sub">Librarian Portal</div>
      </div>

      <div class="admin-sidebar-profile">
        <span class="admin-sidebar-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <div class="admin-sidebar-name"><?php echo htmlspecialchars($currentUserEmail, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="admin-sidebar-role"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-item" href="librarian-dashboard.php"><span>Dashboard</span></a>
        <a class="admin-nav-item" href="librarian-circulation.php"><span>Circulation</span></a>
        <a class="admin-nav-item" href="librarian-books.php"><span>Books</span></a>
        <a class="admin-nav-item" href="librarian-reservations.php"><span>Reservations</span></a>
        <a class="admin-nav-item is-active" href="librarian-fines.php"><span>Fines</span></a>
        <a class="admin-nav-item admin-nav-logout" href="admin-logout.php"><span>Log Out</span></a>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Collected Fines</h1>
        <p>Month-to-date report with all-time collection totals.</p>
      </header>

      <section class="admin-card">
        <div class="admin-stats-row">
          <article class="admin-stat-tile"><strong><?php echo number_format((float)$report['total_amount'], 2); ?></strong><span>MTD Amount</span></article>
          <article class="admin-stat-tile"><strong><?php echo (int)$report['total_collections']; ?></strong><span>MTD Collections</span></article>
          <article class="admin-stat-tile"><strong><?php echo number_format((float)$report['average_amount'], 2); ?></strong><span>MTD Average</span></article>
          <article class="admin-stat-tile"><strong><?php echo number_format((float)$totals['all_time_amount'], 2); ?></strong><span>All-Time Amount</span></article>
          <article class="admin-stat-tile"><strong><?php echo (int)$totals['all_time_collections']; ?></strong><span>All-Time Collections</span></article>
        </div>

        <p class="admin-helper-text">Current month-to-date period: <?php echo htmlspecialchars((string)$report['period_label'], ENT_QUOTES, 'UTF-8'); ?></p>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Receipt</th>
                <th>Borrower</th>
                <th>Collector</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($report['items'])): ?>
                <tr>
                  <td colspan="7" class="admin-empty-state">No collected fines recorded in the current month-to-date period.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($report['items'] as $item): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string)$item['collected_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)($item['receipt_code'] ?: 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(trim((string)$item['borrower_name']) !== '' ? trim((string)$item['borrower_name']) : 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(trim((string)$item['collector_name']) !== '' ? trim((string)$item['collector_name']) : 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo number_format((float)$item['amount'], 2); ?></td>
                    <td><span class="admin-badge is-active"><?php echo htmlspecialchars(ucfirst((string)$item['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars((string)($item['notes'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
