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

$cssPaths = getLibrarianCssPaths();
$mainCssHref = $cssPaths['main'];
$adminCssHref = $cssPaths['admin'];
$librarianCssHref = $cssPaths['librarian'];

$page_alerts = getStoredPageAlerts();

$printFormUrl = appPath('librarian-print-records.php', ['type' => 'fines']);

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
  <link rel="stylesheet" href="<?php echo $librarianCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body portal-role-librarian">
  <div class="admin-shell">
    <?php
    $portalRole = 'librarian';
    $portalCurrentPage = 'fines';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $roleLabel;
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main librarian-main">
      <div class="librarian-page">
        <div class="librarian-shell">
          <section class="librarian-hero">
            <div class="librarian-hero-copy">
              <span class="librarian-eyebrow">Fine reporting</span>
              <h1>Review fine collections</h1>
              <p class="librarian-page-subtitle">View month-to-date and all-time totals.</p>
            </div>
            <aside class="librarian-hero-card">
              <span class="librarian-hero-card-label">Report window</span>
              <strong><?php echo htmlspecialchars((string)$report['period_label'], ENT_QUOTES, 'UTF-8'); ?></strong>
              <p><?php echo (int)$report['total_collections']; ?> month-to-date collections captured in this report.</p>
            </aside>
          </section>

          <section class="librarian-stat-grid is-five" aria-label="Fine totals">
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">MTD Amount</p>
              <p class="librarian-stat-value"><?php echo number_format((float)$report['total_amount'], 2); ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">MTD Collections</p>
              <p class="librarian-stat-value"><?php echo (int)$report['total_collections']; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">MTD Average</p>
              <p class="librarian-stat-value"><?php echo number_format((float)$report['average_amount'], 2); ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">All-Time Amount</p>
              <p class="librarian-stat-value"><?php echo number_format((float)$totals['all_time_amount'], 2); ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">All-Time Collections</p>
              <p class="librarian-stat-value"><?php echo (int)$totals['all_time_collections']; ?></p>
            </article>
          </section>

          <section class="librarian-card librarian-surface-card librarian-table-panel">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Transactions</span>
                <h2>Collection report rows</h2>
              </div>
              <a class="admin-button admin-button-ghost librarian-btn librarian-btn-secondary" href="<?php echo htmlspecialchars($printFormUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Generate Printable Form</a>
            </div>
            <div class="librarian-panel-content">
              <div class="librarian-table-wrap">
                <table class="admin-table librarian-table">
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
