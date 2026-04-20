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
$librarianCssFile = APP_ROOT . '/public/css/librarian.css';
$mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
$adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
$librarianCssVersion = file_exists($librarianCssFile) ? (string)filemtime($librarianCssFile) : (string)time();
$mainCssHref = htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8');
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8');
$librarianCssHref = htmlspecialchars(appPath('public/css/librarian.css', ['v' => $librarianCssVersion]), ENT_QUOTES, 'UTF-8');

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
$checkoutSearchEndpoint = appPath('backend/api/librarian-checkout-search.php');

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

    $appendReceiptAlertMeta = static function (array &$alert, array $result): void {
      if (empty($result['ok'])) {
        return;
      }

      $receiptId = (int)($result['receipt_id'] ?? 0);
      if ($receiptId <= 0) {
        return;
      }

      $receiptCode = trim((string)($result['receipt_code'] ?? ''));
      $receiptViewUrl = trim((string)($result['receipt_print_url'] ?? ''));
      if ($receiptViewUrl === '') {
        $receiptViewUrl = appPath('librarian-receipt.php', [
          'receipt_id' => $receiptId,
        ]);
      }

      $appendQuery = static function (string $url, string $query): string {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . $query;
      };

      $receiptPrintUrl = $appendQuery($receiptViewUrl, 'auto_print=1');
      $receiptDownloadUrl = $appendQuery($receiptViewUrl, 'download=1');
      $alert['onConfirmOpen'] = $receiptPrintUrl;
      $alert['receipt'] = [
        'id' => $receiptId,
        'code' => $receiptCode,
        'viewUrl' => $receiptViewUrl,
        'printUrl' => $receiptPrintUrl,
        'downloadUrl' => $receiptDownloadUrl,
        'mobileFileName' => ($receiptCode !== '' ? strtolower($receiptCode) : ('receipt-' . $receiptId)) . '.html',
      ];

      if ($receiptCode !== '') {
        $alert['message'] .= ' Receipt: ' . $receiptCode . '.';
      }
    };

    if ($action === 'checkin') {
      $loanId = (int)($_POST['loan_id'] ?? 0);
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $result = LibrarianPortalRepository::checkInLoan($db, $loanId, $actorUserId);
      $alert = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Check-In Complete' : 'Check-In Failed',
        'message' => (string)$result['message'],
      ];
      $appendReceiptAlertMeta($alert, $result);
      $page_alerts[] = $alert;
    } elseif ($action === 'checkout') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $borrowerUserId = (int)($_POST['borrower_user_id'] ?? 0);
      $bookId = (int)($_POST['book_id'] ?? 0);

      $result = LibrarianPortalRepository::checkoutLoan($db, $borrowerUserId, $bookId, $actorUserId);
      $alert = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Checkout Complete' : 'Checkout Failed',
        'message' => (string)$result['message'],
      ];
      $appendReceiptAlertMeta($alert, $result);
      $page_alerts[] = $alert;
    } elseif ($action === 'checkout_reservation') {
      $actorUserId = (int)($_SESSION['user_id'] ?? 0);
      $reservationId = (int)($_POST['reservation_id'] ?? 0);

      $result = LibrarianPortalRepository::checkoutReadyReservation($db, $reservationId, $actorUserId);
      $alert = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Reservation Checkout Complete' : 'Reservation Checkout Failed',
        'message' => (string)$result['message'],
      ];
      $appendReceiptAlertMeta($alert, $result);
      $page_alerts[] = $alert;
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
  $checkoutCandidates = LibrarianPortalRepository::getCheckoutCandidates($db, 25, 25);
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
  <link rel="stylesheet" href="<?php echo $librarianCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body portal-role-librarian">
  <div class="admin-shell">
    <?php
    $portalRole = 'librarian';
    $portalCurrentPage = 'circulation';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $roleLabel;
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main librarian-main">
      <div class="librarian-page">
        <div class="librarian-shell">
          <section class="librarian-hero">
            <div class="librarian-hero-copy">
              <span class="librarian-eyebrow">Circulation</span>
              <h1>Manage checkouts and returns</h1>
              <p class="librarian-page-subtitle">Handle checkouts, check-ins, and pickup checkouts.</p>
            </div>
            <aside class="librarian-hero-card">
              <span class="librarian-hero-card-label">Loan snapshot</span>
              <strong><?php echo (int)$activeCount; ?> active loans</strong>
              <p><?php echo (int)$overdueCount; ?> currently overdue records in active circulation.</p>
            </aside>
          </section>

          <section class="librarian-card librarian-surface-card">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Manual checkout</span>
                <h2>Create a loan</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <?php if (!$checkoutCandidates['available']): ?>
                <p class="librarian-inline-note"><?php echo htmlspecialchars((string)$checkoutCandidates['message'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>

              <form method="POST" class="admin-inline-form librarian-form-row librarian-checkout-form" id="manual-checkout-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="checkout">

                <label class="librarian-form-group">
                  <span>Borrower</span>
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

                <label class="librarian-form-group">
                  <span>Title</span>
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

                <button type="submit" class="admin-button admin-button-primary librarian-btn librarian-btn-primary">Check Out</button>
              </form>
            </div>
          </section>

          <section class="librarian-card librarian-surface-card librarian-table-panel">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Reservation pickup</span>
                <h2>Ready reservations</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <?php if (!$readyReservationRows['available']): ?>
                <p class="librarian-inline-note"><?php echo htmlspecialchars((string)$readyReservationRows['message'], ENT_QUOTES, 'UTF-8'); ?></p>
              <?php endif; ?>

              <div class="librarian-table-wrap">
                <table class="admin-table librarian-table">
                  <thead>
                    <tr>
                      <th>Reservation ID</th>
                      <th>Borrower</th>
                      <th>Book</th>
                      <th>Ready Until</th>
                      <th>Copies</th>
                      <th class="librarian-col-action">Action</th>
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
                          <td class="librarian-col-action">
                            <?php if (!empty($readyRow['can_checkout'])): ?>
                              <form method="POST" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="checkout_reservation">
                                <input type="hidden" name="reservation_id" value="<?php echo (int)($readyRow['id'] ?? 0); ?>">
                                <button type="submit" class="admin-button admin-button-primary librarian-btn librarian-btn-primary">Pick Up + Checkout</button>
                              </form>
                            <?php else: ?>
                              <span class="librarian-inline-note">No copies available</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <?php if (!$circulation['available']): ?>
            <div class="librarian-alert librarian-alert-warning" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$circulation['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <section class="librarian-stat-grid is-three" aria-label="Circulation summary">
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Active Loans</p>
              <p class="librarian-stat-value"><?php echo (int)$activeCount; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Overdue Loans</p>
              <p class="librarian-stat-value"><?php echo (int)$overdueCount; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Ready Pickups</p>
              <p class="librarian-stat-value"><?php echo (int)count($readyReservationRows['rows'] ?? []); ?></p>
            </article>
          </section>

          <section class="librarian-card librarian-surface-card librarian-table-panel">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Active circulation</span>
                <h2>Loan records</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <div class="librarian-table-wrap">
                <table class="admin-table librarian-table">
                  <thead>
                    <tr>
                      <th>Loan ID</th>
                      <th>Borrower</th>
                      <th>Book</th>
                      <th>Barcode</th>
                      <th>Due</th>
                      <th>Status</th>
                      <th class="librarian-col-action">Action</th>
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
                          <td class="librarian-col-action">
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
                                <button type="submit" class="admin-button admin-button-primary librarian-btn librarian-btn-primary">Check In</button>
                              </form>
                            <?php else: ?>
                              <span class="librarian-inline-note">Not available</span>
                            <?php endif; ?>
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
