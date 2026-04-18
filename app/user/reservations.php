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
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/dashboard.css">
  <style>
    .reservations-wrap {
      max-width: 1050px;
      margin: 0 auto;
      padding: 30px 24px 48px;
    }

    .reservations-table-wrap {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow: auto;
    }

    .reservations-table {
      width: 100%;
      border-collapse: collapse;
      min-width: 820px;
    }

    .reservations-table th,
    .reservations-table td {
      padding: 12px 14px;
      text-align: left;
      border-bottom: 1px solid var(--line);
      vertical-align: middle;
    }

    .reservations-table th {
      font-size: 0.88rem;
      letter-spacing: 0.02em;
      color: var(--muted);
      text-transform: uppercase;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 0.82rem;
      background: #f5f0e6;
      color: #6a4e37;
    }

    .btn-cancel {
      border: 1px solid #dcb4a7;
      background: #fff3ef;
      color: #9b3920;
      border-radius: 8px;
      padding: 8px 12px;
      font: inherit;
      font-size: 0.88rem;
      cursor: pointer;
    }

    .btn-cancel:hover {
      background: #fce6df;
    }

    .alert {
      margin-bottom: 18px;
      border-radius: 10px;
      padding: 12px 14px;
      border-left: 4px solid;
    }

    .alert-success {
      background: #edf6ea;
      border-left-color: #5d8049;
      color: #335d24;
    }

    .alert-error {
      background: #fff2ef;
      border-left-color: #c64c2a;
      color: #8f3219;
    }

    .empty-note {
      background: #fff;
      border: 1px dashed var(--line);
      border-radius: 12px;
      padding: 20px;
      color: var(--muted);
    }
  </style>
</head>

<body>
  <nav class="navbar">
    <div class="navbar-brand">
      <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="logo">QueenLib</a>
    </div>
    <div class="navbar-menu">
      <div class="user-menu">
        <span class="user-greeting">Welcome, <?php echo htmlspecialchars((string)($user['first_name'] ?? 'Borrower'), ENT_QUOTES, 'UTF-8'); ?>!</span>
        <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Catalog</a>
<<<<<<< ours
=======
        <a href="<?php echo htmlspecialchars(appPath('history.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Loan History</a>
>>>>>>> theirs
        <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Dashboard</a>
        <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link logout">Logout</a>
      </div>
    </div>
  </nav>

  <main class="reservations-wrap">
    <header style="margin-bottom: 18px;">
      <h1>My Active Reservations</h1>
      <p>Track reservation status and cancel active queue entries.</p>
    </header>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status" aria-live="polite">
        <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (!$activeReservations['available']): ?>
      <div class="alert alert-error" role="status" aria-live="polite">
        <?php echo htmlspecialchars((string)$activeReservations['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (empty($activeReservations['rows'])): ?>
      <div class="empty-note">You have no active reservations.</div>
    <?php else: ?>
      <div class="reservations-table-wrap">
        <table class="reservations-table">
          <thead>
            <tr>
              <th>Book</th>
              <th>Status</th>
              <th>Queued At</th>
              <th>Ready Until</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($activeReservations['rows'] as $row): ?>
              <?php $status = strtolower(trim((string)($row['status'] ?? ''))); ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars((string)($row['book_title'] ?? 'Unknown Title'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                  <span style="font-size: 0.88rem; color: var(--muted);">
                    <?php echo htmlspecialchars((string)($row['book_author'] ?? 'Unknown Author'), ENT_QUOTES, 'UTF-8'); ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8'); ?></span>
                </td>
                <td><?php echo htmlspecialchars((string)($row['queued_at'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars((string)($row['ready_until'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <?php if (CirculationRepository::canBorrowerCancelReservationStatus($status)): ?>
                    <form method="POST" action="<?php echo htmlspecialchars(appPath('reservations.php'), ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($cancelToken, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="reservation_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                      <button type="submit" class="btn-cancel">Cancel</button>
                    </form>
                  <?php else: ?>
<<<<<<< ours
                    <span style="color: var(--muted);">Not available</span>
=======
                    <span style="color: var(--muted);">Cannot cancel in this status</span>
>>>>>>> theirs
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
