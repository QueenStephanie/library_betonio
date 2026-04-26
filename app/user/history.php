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
$userId = (int)($_SESSION['user_id'] ?? 0);

$renewScope = 'borrower_history_renew';
$renewToken = getPublicCsrfToken($renewScope);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('borrower_history_renew_post');
  $submittedToken = getPost('csrf_token');
  $action = strtolower(trim((string)getPost('action', '')));
  $loanId = (int)getPost('loan_id', '0');
  $historyRedirect = appPath('history.php');

  if (!$originCheck['valid']) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    error_log('Blocked borrower history POST due to origin validation: ' . json_encode($originCheck));
    clearPublicCsrfToken($renewScope);
    setFlashPageAlert('error', 'Security Error', 'Security check failed. Please refresh and try again.', $historyRedirect);
  } elseif (!validatePublicCsrfToken($submittedToken, $renewScope)) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    clearPublicCsrfToken($renewScope);
    setFlashPageAlert('error', 'Security Error', 'Invalid or missing security token. Please refresh and try again.', $historyRedirect);
  } elseif ($action !== 'renew') {
    setFlashPageAlert('error', 'Invalid Action', 'Unsupported action.', $historyRedirect);
  } else {
    $result = CirculationRepository::renewBorrowerLoan(
      $db,
      $userId,
      $loanId,
      CirculationRepository::getBorrowerMaxRenewals(),
      CirculationRepository::getBorrowerRenewalExtensionDays()
    );

    if (!empty($result['ok'])) {
      $newDueAt = trim((string)($result['due_at'] ?? ''));
      $message = (string)($result['message'] ?? 'Loan renewed successfully.');
      if ($newDueAt !== '') {
        $message .= ' New due date: ' . $newDueAt . '.';
      }
      setFlashPageAlert('success', 'Loan Renewed!', $message, $historyRedirect);
    } else {
      setFlashPageAlert('error', 'Renewal Failed', (string)($result['message'] ?? 'Unable to renew loan right now.'), $historyRedirect);
    }
  }
}

$activeLoans = [
  'available' => false,
  'message' => 'Active loans are unavailable right now.',
  'rows' => [],
];
$loanHistory = [
  'available' => false,
  'message' => 'Loan history is unavailable right now.',
  'rows' => [],
];
$activeLoanRows = [];
$loanHistoryRows = [];
$renewableLoanCount = 0;
$closedFineTotal = 0.0;

if ($userId > 0) {
  try {
    $activeLoans = CirculationRepository::getBorrowerActiveLoans($db, $userId);
    $activeLoanRows = is_array($activeLoans['rows'] ?? null) ? $activeLoans['rows'] : [];
  } catch (Exception $e) {
    error_log('borrower history active loans error: ' . $e->getMessage());
  }

  try {
    $loanHistory = CirculationRepository::getBorrowerLoanHistory($db, $userId);
    $loanHistoryRows = is_array($loanHistory['rows'] ?? null) ? $loanHistory['rows'] : [];
  } catch (Exception $e) {
    error_log('borrower history closed loans error: ' . $e->getMessage());
  }
}

foreach ($activeLoanRows as $row) {
  if (!empty($row['can_renew'])) {
    $renewableLoanCount++;
  }
}

foreach ($loanHistoryRows as $row) {
  $closedFineTotal += (float)($row['fine_amount'] ?? 0);
}

$page_alerts = getStoredPageAlerts();

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
  <title>QueenLib | Loan History</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $borrowerCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body portal-role-borrower">
  <div class="admin-shell">
    <?php
    $portalRole = 'borrower';
    $portalCurrentPage = 'history';
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
              <span class="borrower-eyebrow">Loan history</span>
              <h1>Manage loans and history</h1>
              <p class="borrower-page-subtitle">Review active loans and past records.</p>
              <div class="borrower-hero-actions">
                <a href="#active-loans" class="borrower-btn borrower-btn-primary">View Active Loans</a>
                <a href="#borrowing-history" class="borrower-btn borrower-btn-secondary">Open History</a>
              </div>
            </div>
            <aside class="borrower-hero-card">
              <span class="borrower-hero-card-label">Loan snapshot</span>
              <strong><?php echo count($activeLoanRows); ?> active loans</strong>
              <p><?php echo $renewableLoanCount; ?> currently eligible for renewal.</p>
            </aside>
          </section>

          <section class="borrower-dashboard-stats borrower-stat-grid history-summary-stats" aria-label="Loan history summary">
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Active Loans</p>
              <p class="borrower-stat-value"><?php echo count($activeLoanRows); ?></p>
            </article>
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Renewable Now</p>
              <p class="borrower-stat-value"><?php echo $renewableLoanCount; ?></p>
            </article>
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Closed Records</p>
              <p class="borrower-stat-value"><?php echo count($loanHistoryRows); ?></p>
            </article>
            <article class="borrower-card borrower-stat-card">
              <p class="borrower-stat-label">Closed-Loan Fines</p>
              <p class="borrower-stat-value">₱<?php echo number_format($closedFineTotal, 2); ?></p>
            </article>
          </section>

          <?php if (!$activeLoans['available']): ?>
            <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$activeLoans['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <section id="active-loans" class="borrower-card borrower-surface-card history-section">
            <div class="borrower-panel-heading">
              <div>
                <span class="borrower-section-kicker">Active loans</span>
                <h2>Current checkouts</h2>
              </div>
            </div>
            <div class="borrower-panel-content">
              <p class="history-note">Renewal limit: <?php echo (int)CirculationRepository::getBorrowerMaxRenewals(); ?> per loan. Renewal extension: <?php echo (int)CirculationRepository::getBorrowerRenewalExtensionDays(); ?> days.</p>

              <?php if (empty($activeLoanRows)): ?>
                <div class="borrower-empty">You have no active loans to renew.</div>
              <?php else: ?>
                <div class="borrower-table-wrap">
                  <table class="borrower-table history-table">
                    <thead>
                      <tr>
                        <th>Loan ID</th>
                        <th>Book</th>
                        <th>Borrowed At</th>
                        <th>Due At</th>
                        <th>Status</th>
                        <th>Renewals</th>
                        <th class="borrower-col-action">Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($activeLoanRows as $row): ?>
                        <?php
                        $title = trim((string)($row['book_title'] ?? ''));
                        if ($title === '') {
                          $title = 'Unknown title';
                        }
                        $author = trim((string)($row['book_author'] ?? ''));
                        if ($author !== '') {
                          $title .= ' - ' . $author;
                        }
                        $status = strtolower(trim((string)($row['loan_status'] ?? 'active')));
                        $renewalCount = max(0, (int)($row['renewal_count'] ?? 0));
                        $renewalsRemaining = max(0, (int)($row['renewals_remaining'] ?? 0));
                        ?>
                        <tr>
                          <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                          <td><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars((string)($row['checked_out_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars((string)($row['due_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><span class="badge"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?></span></td>
                          <td><?php echo $renewalCount; ?> used / <?php echo $renewalsRemaining; ?> left</td>
                          <td class="borrower-col-action">
                            <?php if (!empty($row['can_renew'])): ?>
                              <form method="POST" action="<?php echo htmlspecialchars(appPath('history.php'), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($renewToken, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="renew">
                                <input type="hidden" name="loan_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                                <button type="submit" class="borrower-btn borrower-btn-secondary">Renew (+<?php echo (int)CirculationRepository::getBorrowerRenewalExtensionDays(); ?>d)</button>
                              </form>
                            <?php else: ?>
                              <span class="history-muted">Not eligible</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </section>

          <?php if (!$loanHistory['available']): ?>
            <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$loanHistory['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <section id="borrowing-history" class="borrower-card borrower-surface-card history-section">
            <div class="borrower-panel-heading">
              <div>
                <span class="borrower-section-kicker">Borrowing history</span>
                <h2>Closed loan records</h2>
              </div>
            </div>
            <div class="borrower-panel-content">
              <?php if (empty($loanHistoryRows)): ?>
                <div class="borrower-empty">No returned or closed loans yet.</div>
              <?php else: ?>
                <div class="borrower-table-wrap">
                  <table class="borrower-table history-table">
                    <thead>
                      <tr>
                        <th>Loan ID</th>
                        <th>Book</th>
                        <th>Borrowed At</th>
                        <th>Due At</th>
                        <th>Returned At</th>
                        <th>Status</th>
                        <th>Fine</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($loanHistoryRows as $row): ?>
                        <?php
                        $title = trim((string)($row['book_title'] ?? ''));
                        if ($title === '') {
                          $title = 'Unknown title';
                        }
                        $author = trim((string)($row['book_author'] ?? ''));
                        if ($author !== '') {
                          $title .= ' - ' . $author;
                        }
                        $status = strtolower(trim((string)($row['loan_status'] ?? 'returned')));
                        $fineAmount = (float)($row['fine_amount'] ?? 0);
                        ?>
                        <tr>
                          <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                          <td><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars((string)($row['checked_out_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars((string)($row['due_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?php echo htmlspecialchars((string)($row['returned_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><span class="badge closed"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?></span></td>
                          <td>₱<?php echo htmlspecialchars(number_format($fineAmount, 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
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