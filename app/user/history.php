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

  if (!$originCheck['valid']) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    error_log('Blocked borrower history POST due to origin validation: ' . json_encode($originCheck));
    clearPublicCsrfToken($renewScope);
    setFlash('error', 'Security check failed. Please refresh and try again.');
  } elseif (!validatePublicCsrfToken($submittedToken, $renewScope)) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    clearPublicCsrfToken($renewScope);
    setFlash('error', 'Invalid or missing security token. Please refresh and try again.');
  } elseif ($action !== 'renew') {
    setFlash('error', 'Unsupported action.');
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
      setFlash('success', $message);
    } else {
      setFlash('error', (string)($result['message'] ?? 'Unable to renew loan right now.'));
    }
  }

  redirect(appPath('history.php'));
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

try {
  $activeLoans = CirculationRepository::getBorrowerActiveLoans($db, $userId, 150);
  $loanHistory = CirculationRepository::getBorrowerLoanHistory($db, $userId, 200);
} catch (Exception $e) {
  error_log('borrower history load error: ' . $e->getMessage());
  $activeLoans['message'] = 'Unable to load active loans right now.';
  $loanHistory['message'] = 'Unable to load borrowing history right now.';
}

$flash = getFlash();
$currentPage = 'history';
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
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/admin.css">
  <link rel="stylesheet" href="public/css/borrower.css">
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
      <header class="borrower-page-header">
        <h1>Loan History &amp; Renewals</h1>
        <p class="borrower-page-subtitle">Review active loans, renew eligible items, and view returned/closed borrowing records.</p>
      </header>

      <?php if ($flash): ?>
        <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!$activeLoans['available']): ?>
        <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$activeLoans['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section id="active-loans" class="borrower-card history-section">
        <h2>Active Loans</h2>
        <p class="history-note">Renewal limit: <?php echo (int)CirculationRepository::getBorrowerMaxRenewals(); ?> per loan. Renewal extension: <?php echo (int)CirculationRepository::getBorrowerRenewalExtensionDays(); ?> days.</p>

        <?php if (empty($activeLoans['rows'])): ?>
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
                <?php foreach ($activeLoans['rows'] as $row): ?>
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
      </section>

      <?php if (!$loanHistory['available']): ?>
        <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$loanHistory['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section id="borrowing-history" class="borrower-card history-section">
        <h2>Borrowing History</h2>

        <?php if (empty($loanHistory['rows'])): ?>
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
                <?php foreach ($loanHistory['rows'] as $row): ?>
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
      </section>
        </div>
      </div>
    </main>
  </div>
</body>

</html>
