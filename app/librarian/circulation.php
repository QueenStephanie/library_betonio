<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('librarian-circulation');

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
  $originCheck = validateStateChangingRequestOrigin('librarian_circulation_post');
  $submittedToken = $_POST['csrf_token'] ?? '';

  if (!$originCheck['valid']) {
    logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
    error_log('Blocked librarian-circulation POST due to origin validation: ' . json_encode($originCheck));
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
    if ($action === 'checkin') {
      $loanId = (int)($_POST['loan_id'] ?? 0);
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $result = LibrarianPortalRepository::checkInLoan($db, $loanId, $actorUserId);
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Check-In Complete' : 'Check-In Failed',
        'message' => (string)$result['message'],
      ];
    } elseif ($action === 'checkout') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $borrowerUserId = (int)($_POST['borrower_user_id'] ?? 0);
      $bookId = (int)($_POST['book_id'] ?? 0);

      $result = LibrarianPortalRepository::checkoutLoan($db, $borrowerUserId, $bookId, $actorUserId);
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Checkout Complete' : 'Checkout Failed',
        'message' => (string)$result['message'],
      ];
    } elseif ($action === 'checkout_reservation') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $reservationId = (int)($_POST['reservation_id'] ?? 0);

      $result = LibrarianPortalRepository::checkoutReadyReservation($db, $reservationId, $actorUserId);
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Reservation Checkout Complete' : 'Reservation Checkout Failed',
        'message' => (string)$result['message'],
      ];
    }
  }
}

$checkoutCandidates = [
  'rows' => [
    'borrowers' => [],
    'books' => [],
  ],
  'available' => false,
  'message' => 'Checkout form data unavailable.',
];

try {
  $checkoutCandidates = LibrarianPortalRepository::getCheckoutCandidates($db, 250, 250);
} catch (Exception $e) {
  error_log('librarian-circulation checkout candidate error: ' . $e->getMessage());
  $checkoutCandidates['message'] = 'Unable to load checkout form options right now.';
}

$readyReservationRows = [
  'rows' => [],
  'available' => false,
  'message' => 'Ready reservations unavailable.',
];

try {
  $readyReservationRows = LibrarianPortalRepository::getReadyReservationCheckoutRows($db, 150);
} catch (Exception $e) {
  error_log('librarian-circulation ready reservation error: ' . $e->getMessage());
  $readyReservationRows['message'] = 'Unable to load ready reservations for checkout right now.';
}

$circulation = [
  'rows' => [],
  'available' => false,
  'message' => 'Circulation data unavailable.',
];

try {
  $circulation = LibrarianPortalRepository::getCirculationRows($db, 250);
} catch (Exception $e) {
  error_log('librarian-circulation list error: ' . $e->getMessage());
  $circulation['message'] = 'Unable to load circulation records right now.';
}

$rows = $circulation['rows'];
$activeCount = count($rows);
$overdueCount = 0;
foreach ($rows as $row) {
  if (!empty($row['is_overdue'])) {
    $overdueCount++;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Librarian Circulation</title>
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
        <a class="admin-nav-item is-active" href="librarian-circulation.php"><span>Circulation</span></a>
        <a class="admin-nav-item" href="librarian-books.php"><span>Books</span></a>
        <a class="admin-nav-item" href="librarian-reservations.php"><span>Reservations</span></a>
        <a class="admin-nav-item" href="librarian-fines.php"><span>Fines</span></a>
        <a class="admin-nav-item admin-nav-logout" href="admin-logout.php"><span>Log Out</span></a>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Circulation</h1>
        <p>Checkout, check-in, and reservation pickup workflows.</p>
      </header>

      <section class="admin-card" style="margin-bottom:16px;">
        <div class="admin-card-header">
          <h2>Manual Checkout</h2>
          <p>Create a new loan by assigning an available copy to a borrower.</p>
        </div>

        <?php if (!$checkoutCandidates['available']): ?>
          <p class="admin-demo-note"><?php echo htmlspecialchars((string)$checkoutCandidates['message'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form method="POST" class="admin-inline-form" style="display:grid;grid-template-columns:1fr 1fr auto;gap:10px;align-items:end;max-width:100%;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="checkout">

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Borrower</span>
            <select name="borrower_user_id" required>
              <option value="">Select borrower</option>
              <?php foreach (($checkoutCandidates['rows']['borrowers'] ?? []) as $borrower): ?>
                <?php
                $displayName = trim((string)($borrower['display_name'] ?? ''));
                $email = trim((string)($borrower['email'] ?? ''));
                $label = $displayName !== '' ? $displayName : ('User #' . (int)($borrower['id'] ?? 0));
                if ($email !== '') {
                  $label .= ' (' . $email . ')';
                }
                ?>
                <option value="<?php echo (int)($borrower['id'] ?? 0); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Title</span>
            <select name="book_id" required>
              <option value="">Select book with available copies</option>
              <?php foreach (($checkoutCandidates['rows']['books'] ?? []) as $book): ?>
                <?php
                $title = trim((string)($book['title'] ?? 'Unknown title'));
                $author = trim((string)($book['author'] ?? ''));
                $label = $title;
                if ($author !== '') {
                  $label .= ' - ' . $author;
                }
                $label .= ' [' . max(0, (int)($book['available_copies'] ?? 0)) . ' available]';
                ?>
                <option value="<?php echo (int)($book['id'] ?? 0); ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <button type="submit" class="admin-button admin-button-primary">Check Out</button>
        </form>
      </section>

      <section class="admin-card" style="margin-bottom:16px;">
        <div class="admin-card-header">
          <h2>Ready Reservation Pickup</h2>
          <p>Bridge ready-for-pickup reservations directly into checkout.</p>
        </div>

        <?php if (!$readyReservationRows['available']): ?>
          <p class="admin-demo-note"><?php echo htmlspecialchars((string)$readyReservationRows['message'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Reservation ID</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Ready Until</th>
                <th>Copies</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($readyReservationRows['rows'])): ?>
                <tr>
                  <td colspan="6" class="admin-empty-state">No ready reservations for pickup checkout.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($readyReservationRows['rows'] as $readyRow): ?>
                  <?php
                  $borrowerName = trim(((string)($readyRow['borrower_first_name'] ?? '')) . ' ' . ((string)($readyRow['borrower_last_name'] ?? '')));
                  if ($borrowerName === '') {
                    $borrowerName = (string)($readyRow['borrower_email'] ?? 'N/A');
                  }
                  $bookLabel = trim((string)($readyRow['book_title'] ?? ''));
                  if ($bookLabel === '') {
                    $bookLabel = 'Unknown title';
                  }
                  $bookAuthor = trim((string)($readyRow['book_author'] ?? ''));
                  if ($bookAuthor !== '') {
                    $bookLabel .= ' - ' . $bookAuthor;
                  }
                  ?>
                  <tr>
                    <td>#<?php echo (int)($readyRow['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($borrowerName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($bookLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)($readyRow['ready_until'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo max(0, (int)($readyRow['available_copies'] ?? 0)); ?></td>
                    <td>
                      <?php if (!empty($readyRow['can_checkout'])): ?>
                        <form method="POST" style="margin:0;">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="action" value="checkout_reservation">
                          <input type="hidden" name="reservation_id" value="<?php echo (int)($readyRow['id'] ?? 0); ?>">
                          <button type="submit" class="admin-button admin-button-primary">Pick Up + Checkout</button>
                        </form>
                      <?php else: ?>
                        <span class="admin-demo-note">No copies available</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <?php if (!$circulation['available']): ?>
        <div class="admin-alert admin-alert-warning" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$circulation['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section class="admin-card">
        <div class="admin-stats-row">
          <article class="admin-stat-tile"><strong><?php echo (int)$activeCount; ?></strong><span>Active Loans</span></article>
          <article class="admin-stat-tile"><strong><?php echo (int)$overdueCount; ?></strong><span>Overdue Loans</span></article>
        </div>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Loan ID</th>
                <th>Borrower</th>
                <th>Book</th>
                <th>Barcode</th>
                <th>Due</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="7" class="admin-empty-state">No active circulation records found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $row): ?>
                  <?php
                  $borrowerName = trim(((string)($row['borrower_first_name'] ?? '')) . ' ' . ((string)($row['borrower_last_name'] ?? '')));
                  if ($borrowerName === '') {
                    $borrowerName = (string)($row['borrower_email'] ?? 'N/A');
                  }
                  $bookLabel = trim((string)($row['title'] ?? ''));
                  if ($bookLabel === '') {
                    $bookLabel = 'Unknown title';
                  }
                  $author = trim((string)($row['author'] ?? ''));
                  if ($author !== '') {
                    $bookLabel .= ' - ' . $author;
                  }
                  $isOverdue = !empty($row['is_overdue']);
                  ?>
                  <tr>
                    <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                    <td><?php echo htmlspecialchars($borrowerName, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($bookLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)($row['barcode'] ?: 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars((string)date('M j, Y g:i A', strtotime((string)($row['due_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <span class="admin-badge <?php echo $isOverdue ? 'is-admin' : 'is-librarian'; ?>">
                        <?php echo $isOverdue ? 'Overdue' : 'Active'; ?>
                      </span>
                    </td>
                    <td>
                      <?php if (!empty($row['can_checkin'])): ?>
                        <form method="POST" style="margin:0;">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="action" value="checkin">
                          <input type="hidden" name="loan_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                          <button type="submit" class="admin-button admin-button-primary">Check In</button>
                        </form>
                      <?php else: ?>
                        <span class="admin-demo-note">Not available</span>
                      <?php endif; ?>
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
