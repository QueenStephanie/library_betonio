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

$csrfToken = getAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('librarian_reservations_post');
  $submittedToken = $_POST['csrf_token'] ?? '';

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
    $action = strtolower(trim((string)($_POST['action'] ?? '')));
    $reservationId = (int)($_POST['reservation_id'] ?? 0);

    if ($action === 'checkout') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $result = LibrarianPortalRepository::checkoutReadyReservation($db, $reservationId, $actorUserId);
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Reservation Checkout Complete' : 'Action Failed',
        'message' => (string)$result['message'],
      ];
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
        <a class="admin-nav-item is-active" href="librarian-reservations.php"><span>Reservations</span></a>
        <a class="admin-nav-item" href="librarian-fines.php"><span>Fines</span></a>
        <a class="admin-nav-item admin-nav-logout" href="admin-logout.php"><span>Log Out</span></a>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Reservation Queue</h1>
        <p>Queue list with approve, reject, and cancel actions.</p>
      </header>

      <?php if (!$queue['available']): ?>
        <div class="admin-alert admin-alert-warning" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$queue['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section class="admin-card">
        <p class="admin-demo-note">Showing <?php echo (int)count($rows); ?> active queue item(s).</p>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Reservation ID</th>
                <th>Queue Position</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Status</th>
                <th>Queued At</th>
                <th>Actions</th>
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
                  $borrowerName = trim(((string)($row['borrower_first_name'] ?? '')) . ' ' . ((string)($row['borrower_last_name'] ?? '')));
                  if ($borrowerName === '') {
                    $borrowerName = (string)($row['borrower_email'] ?? 'N/A');
                  }
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
                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string)($row['queued_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <div class="admin-actions">
                        <?php if ($status === 'pending'): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn" title="Approve reservation">A</button>
                          </form>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-danger" title="Reject reservation">R</button>
                          </form>
                        <?php endif; ?>
                         <?php if (in_array($status, ['ready_for_pickup', 'ready'], true)): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn" title="Checkout from ready reservation">P</button>
                          </form>
                        <?php endif; ?>
                        <?php if (in_array($status, ['pending', 'ready_for_pickup', 'ready'], true)): ?>
                          <form method="POST" class="admin-inline-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                            <button type="submit" class="admin-action-btn admin-action-danger" title="Cancel reservation">C</button>
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
      </section>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
