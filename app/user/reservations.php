<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/backend/classes/CirculationRepository.php';

requireLogin();
PermissionGate::requireFrontendRole('borrower', 'index.php');
checkSessionTimeout();

$auth = new AuthManager($db);
$user = $auth->getCurrentUser();

$cancelScope = 'borrower_reservations_cancel';
$cancelToken = getPublicCsrfToken($cancelScope);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('borrower_reservations_cancel_post');
  $submittedToken = getPost('csrf_token');
  $reservationId = (int)getPost('reservation_id', '0');

  if (!$originCheck['valid']) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    error_log('Blocked borrower reservations POST due to origin validation: ' . json_encode($originCheck));
    clearPublicCsrfToken($cancelScope);
    setFlash('error', 'Security check failed. Please refresh and try again.');
  } elseif (!validatePublicCsrfToken($submittedToken, $cancelScope)) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    clearPublicCsrfToken($cancelScope);
    setFlash('error', 'Invalid or missing security token. Please refresh and try again.');
  } else {
    $result = CirculationRepository::cancelBorrowerReservation($db, (int)($_SESSION['user_id'] ?? 0), $reservationId);
    if (!empty($result['ok'])) {
      setFlash('success', (string)($result['message'] ?? 'Reservation cancelled.'));
    } else {
      setFlash('error', (string)($result['message'] ?? 'Unable to cancel reservation.'));
    }
  }

  redirect(appPath('reservations.php'));
}

$activeReservations = [
  'available' => false,
  'message' => 'Reservations are unavailable right now.',
  'rows' => [],
];

try {
  $activeReservations = CirculationRepository::getBorrowerActiveReservations($db, (int)($_SESSION['user_id'] ?? 0), 150);
} catch (Exception $e) {
  error_log('borrower reservations load error: ' . $e->getMessage());
  $activeReservations['message'] = 'Unable to load reservations right now.';
}

$flash = getFlash();
$reservationRows = is_array($activeReservations['rows'] ?? null) ? $activeReservations['rows'] : [];
$readyReservations = 0;
$queuedReservations = 0;
foreach ($reservationRows as $reservationSummary) {
  $reservationStatus = strtolower(trim((string)($reservationSummary['status'] ?? '')));
  if ($reservationStatus === 'ready_for_pickup') {
    $readyReservations++;
  } elseif ($reservationStatus === 'pending') {
    $queuedReservations++;
  }
}

$cssPaths = getBorrowerCssPaths();
$mainCssHref = $cssPaths['main'];
$borrowerCssHref = $cssPaths['borrower'];
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => (string)filemtime(APP_ROOT . '/public/css/admin.css')]), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | My Reservations</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
<link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
<link rel="stylesheet" href="<?php echo $borrowerCssHref; ?>">
</head>

<body class="admin-portal-body portal-role-borrower">
  <div class="admin-shell">
    <?php
    $portalRole = 'borrower';
    $portalCurrentPage = 'reservations';
    $portalIdentityName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
    if ($portalIdentityName === '') {
      $portalIdentityName = 'Borrower User';
    }
    $portalIdentityMeta = (string)($user['email'] ?? '');
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main borrower-main">
      <div class="borrower-page">
        <div class="borrower-shell">
          <section class="borrower-hero borrower-page-hero">
            <div class="borrower-hero-copy">
              <span class="borrower-eyebrow">Reservations</span>
              <h1>Manage your holds</h1>
              <p class="borrower-page-subtitle">Track status and cancel eligible requests.</p>
            </div>
            <aside class="borrower-hero-card">
              <span class="borrower-hero-card-label">Reservation snapshot</span>
              <strong><?php echo count($reservationRows); ?> active requests</strong>
              <p><?php echo $readyReservations; ?> ready for pickup and <?php echo $queuedReservations; ?> still in queue.</p>
            </aside>
          </section>

          <section class="borrower-dashboard-stats borrower-stat-grid reservations-summary-stats" aria-label="Reservation summary">
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">All Active</p>
              <p class="borrower-stat-value"><?php echo count($reservationRows); ?></p>
            </article>
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Ready for Pickup</p>
              <p class="borrower-stat-value"><?php echo $readyReservations; ?></p>
            </article>
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Queued</p>
              <p class="borrower-stat-value"><?php echo $queuedReservations; ?></p>
            </article>
          </section>

          <?php if ($flash): ?>
            <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if (!$activeReservations['available']): ?>
            <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$activeReservations['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if (empty($reservationRows)): ?>
            <div class="borrower-empty">You have no active reservations.</div>
          <?php else: ?>
            <section class="borrower-card borrower-surface-card borrower-table-panel">
              <div class="borrower-panel-heading">
                <div>
                  <span class="borrower-section-kicker">Queue details</span>
                  <h2>Active reservation list</h2>
                </div>
              </div>
              <div class="borrower-table-wrap">
                <table class="borrower-table reservations-table">
            <thead>
              <tr>
                <th>Book</th>
                <th>Status</th>
                <th>Queued At</th>
                <th>Ready Until</th>
                <th class="borrower-col-action">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservationRows as $row): ?>
                <?php $status = strtolower(trim((string)($row['status'] ?? ''))); ?>
                <tr>
                  <td>
                    <strong><?php echo htmlspecialchars((string)($row['book_title'] ?? 'Unknown Title'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                    <span class="reservations-page-note">
                      <?php echo htmlspecialchars((string)($row['book_author'] ?? 'Unknown Author'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?></span>
                  </td>
                  <td><?php echo htmlspecialchars((string)($row['queued_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string)($row['ready_until'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="borrower-col-action">
                    <?php if (CirculationRepository::canBorrowerCancelReservationStatus($status)): ?>
                      <form method="POST" action="<?php echo htmlspecialchars(appPath('reservations.php'), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cancelToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                        <button type="submit" class="borrower-btn borrower-btn-danger">Cancel</button>
                      </form>
                    <?php else: ?>
                      <span class="reservations-page-note">Cannot cancel in this status</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
                </table>
              </div>
            </section>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>

</html>
