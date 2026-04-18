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

$query = trim((string)($_GET['q'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$reserveScope = 'borrower_catalog_reserve';
$reserveCsrfToken = getPublicCsrfToken($reserveScope);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('borrower_catalog_reserve_post');
  $submittedToken = getPost('csrf_token');
  $bookId = (int)getPost('book_id', '0');

  if (!$originCheck['valid']) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    error_log('Blocked borrower catalog POST due to origin validation: ' . json_encode($originCheck));
    clearPublicCsrfToken($reserveScope);
    setFlash('error', 'Security check failed. Please refresh and try again.');
  } elseif (!validatePublicCsrfToken($submittedToken, $reserveScope)) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    clearPublicCsrfToken($reserveScope);
    setFlash('error', 'Invalid or missing security token. Please refresh and try again.');
  } else {
    $result = CirculationRepository::createBorrowerReservation(
      $db,
      (int)($_SESSION['user_id'] ?? 0),
      $bookId,
      CirculationRepository::getBorrowerMaxActiveReservations()
    );

    if (!empty($result['ok'])) {
      $queuePosition = isset($result['queue_position']) ? (int)$result['queue_position'] : 0;
      $message = 'Reservation placed successfully.';
      if ($queuePosition > 0) {
        $message .= ' Queue position: ' . $queuePosition . '.';
      }
      setFlash('success', $message);
    } else {
      setFlash('error', (string)($result['message'] ?? 'Unable to create reservation.'));
    }
  }

  $redirectQuery = [];
  if ($query !== '') {
    $redirectQuery['q'] = $query;
  }
  if ($category !== '') {
    $redirectQuery['category'] = $category;
  }

  redirect(appPath('catalog.php', $redirectQuery));
}

$catalog = [
  'available' => false,
  'message' => 'Catalog is unavailable right now.',
  'rows' => [],
  'categories' => [],
];

try {
  $catalog = CirculationRepository::getBorrowerCatalog($db, $query, $category, 250);
} catch (Exception $e) {
  error_log('borrower catalog load error: ' . $e->getMessage());
  $catalog['message'] = 'Unable to load catalog right now.';
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Catalog</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/dashboard.css">
  <style>
    .catalog-wrap {
      max-width: 1100px;
      margin: 0 auto;
      padding: 30px 24px 48px;
    }

    .catalog-header {
      margin-bottom: 18px;
    }

    .catalog-search {
      display: grid;
      grid-template-columns: 1.5fr 1fr auto;
      gap: 12px;
      margin-bottom: 22px;
    }

    .catalog-search input,
    .catalog-search select {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--line);
      border-radius: 10px;
      font: inherit;
      background: #fff;
    }

    .catalog-search button {
      border: 0;
      border-radius: 10px;
      padding: 0 18px;
      font: inherit;
      font-weight: 600;
      background: var(--accent);
      color: #fff;
      cursor: pointer;
    }

    .catalog-grid {
      display: grid;
      gap: 14px;
    }

    .catalog-item {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 16px;
      display: grid;
      grid-template-columns: 1.8fr auto;
      gap: 14px;
      align-items: center;
    }

    .catalog-meta {
      color: var(--muted);
      font-size: 0.92rem;
      margin-top: 4px;
    }

    .catalog-actions {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
      min-width: 180px;
    }

    .availability {
      font-size: 0.92rem;
      color: var(--muted);
    }

    .availability strong {
      color: var(--text);
    }

    .btn-reserve {
      border: 0;
      border-radius: 10px;
      padding: 10px 14px;
      font: inherit;
      font-weight: 600;
      cursor: pointer;
      background: #efe6d2;
      color: #4b301c;
    }

    .btn-reserve:hover {
      background: #e5d9c1;
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

    @media (max-width: 760px) {
      .catalog-search {
        grid-template-columns: 1fr;
      }

      .catalog-item {
        grid-template-columns: 1fr;
      }

      .catalog-actions {
        align-items: flex-start;
      }
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
        <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Dashboard</a>
        <a href="<?php echo htmlspecialchars(appPath('reservations.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">My Reservations</a>
<<<<<<< ours
=======
        <a href="<?php echo htmlspecialchars(appPath('history.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Loan History</a>
>>>>>>> theirs
        <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link logout">Logout</a>
      </div>
    </div>
  </nav>

  <main class="catalog-wrap">
    <header class="catalog-header">
      <h1>Book Catalog</h1>
      <p>Search by title, author, or ISBN and reserve available titles.</p>
    </header>

    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status" aria-live="polite">
        <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <?php if (!$catalog['available']): ?>
      <div class="alert alert-error" role="status" aria-live="polite">
        <?php echo htmlspecialchars((string)$catalog['message'], ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <form method="GET" action="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="catalog-search">
      <input type="search" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search title, author, ISBN">
      <select name="category">
        <option value="">All Categories</option>
        <?php foreach (($catalog['categories'] ?? []) as $categoryOption): ?>
          <?php $categoryOption = (string)$categoryOption; ?>
          <option value="<?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryOption === $category ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Search</button>
    </form>

    <?php if (empty($catalog['rows'])): ?>
      <div class="empty-note">No catalog titles matched your filters.</div>
    <?php else: ?>
      <div class="catalog-grid">
        <?php foreach ($catalog['rows'] as $row): ?>
          <?php
          $availableCopies = max(0, (int)($row['available_copies'] ?? 0));
          $totalCopies = max(0, (int)($row['total_copies'] ?? 0));
          if ($totalCopies < $availableCopies) {
            $totalCopies = $availableCopies;
          }
          ?>
          <article class="catalog-item">
            <div>
              <h3><?php echo htmlspecialchars((string)($row['title'] ?? 'Unknown Title'), ENT_QUOTES, 'UTF-8'); ?></h3>
              <p class="catalog-meta">
                <?php echo htmlspecialchars((string)($row['author'] ?? 'Unknown Author'), ENT_QUOTES, 'UTF-8'); ?>
                <?php if (!empty($row['category'])): ?>
                  • <?php echo htmlspecialchars((string)$row['category'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
                <?php if (!empty($row['isbn'])): ?>
                  • ISBN: <?php echo htmlspecialchars((string)$row['isbn'], ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </p>
            </div>
            <div class="catalog-actions">
              <div class="availability">
                <strong><?php echo $availableCopies; ?></strong> available of <?php echo $totalCopies; ?> copy/copies
              </div>
              <form method="POST" action="<?php echo htmlspecialchars(appPath('catalog.php', array_filter(['q' => $query, 'category' => $category], static function ($value) {
                                                return $value !== '';
                                              })), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reserveCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="book_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                <button type="submit" class="btn-reserve">Reserve</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>

</html>
