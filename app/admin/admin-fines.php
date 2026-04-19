<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/FineReporting.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('admin-fines');

$currentUserEmail = (string)($_SESSION['user_email'] ?? 'admin@local.admin');
$isSuperadmin = isCurrentAdminSuperadmin();
$adminRoleLabel = $isSuperadmin ? 'Super Administrator' : 'Administrator';

$mainCssFile = APP_ROOT . '/public/css/main.css';
$adminCssFile = APP_ROOT . '/public/css/admin.css';
$mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
$adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
$mainCssHref = htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8');
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8');

$page_alerts = [];
$report = [
  'period_label' => date('M 1, Y') . ' to ' . date('M j, Y'),
  'total_collections' => 0,
  'total_amount' => 0.0,
  'average_amount' => 0.0,
  'items' => [],
];

try {
  $report = FineReporting::getMonthToDateReport($db);
} catch (Exception $e) {
  error_log('admin-fines report error: ' . $e->getMessage());
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Report Unavailable',
    'message' => 'Unable to load the month-to-date fines report right now.',
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Fines Report</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <?php
    $portalRole = 'admin';
    $portalCurrentPage = 'fines';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $adminRoleLabel;
    $portalBrandSub = 'Admin Portal';
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Current Collected Fines</h1>
        <p>Fixed month-to-date period: <?php echo htmlspecialchars($report['period_label'], ENT_QUOTES, 'UTF-8'); ?></p>
      </header>

      <section class="admin-card">
        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong><?php echo number_format((float)$report['total_amount'], 2); ?></strong>
            <span>Total Collected (MTD)</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$report['total_collections']; ?></strong>
            <span>Collections (MTD)</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo number_format((float)$report['average_amount'], 2); ?></strong>
            <span>Average Collection</span>
          </article>
        </div>

        <p class="admin-helper-text">This report uses a fixed month-to-date window and does not support custom date filtering.</p>

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
                  <td colspan="7" class="admin-empty-state">No collected fines recorded for the current month-to-date period.</td>
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

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
