<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('librarian-reservations');

$currentUserEmail = (string)($_SESSION['user_email'] ?? 'librarian@local.librarian');
$currentRole = PermissionGate::resolveAdminRole();
$roleLabel = PermissionGate::getRoleLabel($currentRole);

$cssPaths = getLibrarianCssPaths();
$mainCssHref = $cssPaths['main'];
$adminCssHref = $cssPaths['admin'];
$librarianCssHref = $cssPaths['librarian'];

$page_alerts = getStoredPageAlerts();

$csrfToken = getAdminCsrfToken();
$printFormUrl = appPath('librarian-print-records.php', ['type' => 'reservations']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $originCheck = validateStateChangingRequestOrigin('librarian_reservations_post');
    $submittedToken = getPost('csrf_token', '');

    if (!$originCheck['valid']) {
        logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
        error_log('Blocked librarian-reservations POST due to origin validation: ' . json_encode($originCheck));
        $page_alerts[] = [
            'type' => 'error',
            'title' => 'Security Validation Failed',
            'message' => 'Origin validation failed. Please refresh and try again.',
        ];
    } elseif (!validateAdminCsrfToken($submittedToken)) {
        logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
        $page_alerts[] = [
            'type' => 'error',
            'title' => 'Security Validation Failed',
            'message' => 'Invalid or missing security token. Please refresh and try again.',
        ];
} else {
        $action = strtolower(getPost('action'));
        $reservationId = (int)getPost('reservation_id');

        if ($action === 'checkout') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $result = LibrarianPortalRepository::checkoutReadyReservation($db, $reservationId, $actorUserId);
      $alert = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Reservation Checkout Complete' : 'Action Failed',
        'message' => (string)$result['message'],
      ];
      appendReceiptAlertMeta($alert, $result);
      $page_alerts[] = $alert;
    } else {
      $result = LibrarianPortalRepository::updateReservationStatus($db, $reservationId, $action);

      $title = 'Reservation Updated';
      if ($action === 'approve') {
        $title = 'Reservation Approved';
      } elseif ($action === 'reject') {
        $title = 'Reservation Rejected';
      } elseif ($action === 'cancel') {
        $title = 'Reservation Cancelled';
      }

      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? $title : 'Action Failed',
        'message' => (string)$result['message'],
      ];
    }
  }
}

$queue = [
  'rows' => [],
  'available' => false,
  'message' => 'Reservations unavailable.',
];

try {
  $queue = LibrarianPortalRepository::getReservationQueue($db, 300);
} catch (Exception $e) {
  error_log('librarian-reservations queue error: ' . $e->getMessage());
  $queue['message'] = 'Unable to load reservation queue right now.';
}

$rows = $queue['rows'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Librarian Reservations</title>
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
    $portalCurrentPage = 'reservations';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $roleLabel;
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main librarian-main">
      <div class="librarian-page">
        <div class="librarian-shell">
          <section class="librarian-hero">
            <div class="librarian-hero-copy">
              <span class="librarian-eyebrow">Reservations</span>
              <h1>Manage reservation queue</h1>
              <p class="librarian-page-subtitle">Review and process reservation requests.</p>
            </div>
            <aside class="librarian-hero-card">
              <span class="librarian-hero-card-label">Queue snapshot</span>
              <strong><?php echo (int)count($rows); ?> active items</strong>
              <p>Pending and ready items in one queue.</p>
            </aside>
          </section>

          <?php if (!$queue['available']): ?>
            <div class="librarian-alert librarian-alert-warning" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$queue['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <section class="librarian-card librarian-surface-card librarian-table-panel">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Queue table</span>
                <h2>Reservation list</h2>
              </div>
              <a class="admin-button admin-button-ghost librarian-btn librarian-btn-secondary" href="<?php echo htmlspecialchars($printFormUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Generate Printable Form</a>
            </div>
            <div class="librarian-panel-content">
              <div class="librarian-table-wrap">
                <table class="admin-table librarian-table">
            <thead>
              <tr>
                <th>Reservation ID</th>
                <th>Queue Position</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Status</th>
                <th>Queued At</th>
                <th class="librarian-col-action">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="7" class="admin-empty-state">No pending or ready reservations found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $row): ?>
<?php
$status = strtolower(trim((string)($row['status'] ?? '')));
$borrowerName = formatBorrowerName(
    (string)($row['borrower_first_name'] ?? ''),
    (string)($row['borrower_last_name'] ?? ''),
    (string)($row['borrower_email'] ?? 'N/A')
);
$bookLabel = trim((string)($row['book_title'] ?? ''));
                  if ($bookLabel === '') {
                    $bookLabel = 'Unknown title';
                  }
                  $bookAuthor = trim((string)($row['book_author'] ?? ''));
                  if ($bookAuthor !== '') {
                    $bookLabel .= ' - ' . $bookAuthor;
                  }
                  ?>
                  <tr>
                    <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                    <td><?php echo (int)($row['queue_position'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($borrowerName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($bookLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="admin-badge <?php echo $status === 'pending' ? 'is-librarian' : 'is-admin'; ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($row['queued_at'] ?: 'now')), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="librarian-col-action">
                      <div class="admin-actions">
                        <?php if ($status === 'pending'): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-text librarian-btn librarian-btn-secondary" title="Approve reservation">Approve</button>
                          </form>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-danger admin-action-text librarian-btn librarian-btn-danger" title="Reject reservation">Reject</button>
                          </form>
                        <?php endif; ?>
                         <?php if (in_array($status, ['ready_for_pickup', 'ready'], true)): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-text librarian-btn librarian-btn-primary" title="Checkout from ready reservation">Checkout</button>
                          </form>
                        <?php endif; ?>
                        <?php if (in_array($status, ['pending', 'ready_for_pickup', 'ready'], true)): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-danger admin-action-text librarian-btn librarian-btn-danger" title="Cancel reservation">Cancel</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
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
