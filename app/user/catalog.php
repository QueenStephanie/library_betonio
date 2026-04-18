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
$catalogRows = is_array($catalog['rows'] ?? null) ? $catalog['rows'] : [];
$catalogResultCount = count($catalogRows);
$catalogInStockTitles = 0;
$catalogAvailableCopies = 0;
foreach ($catalogRows as $catalogRowSummary) {
  $itemAvailableCopies = max(0, (int)($catalogRowSummary['available_copies'] ?? 0));
  if ($itemAvailableCopies > 0) {
    $catalogInStockTitles++;
  }
  $catalogAvailableCopies += $itemAvailableCopies;
}
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
  <link rel="stylesheet" href="public/css/admin.css">
  <link rel="stylesheet" href="public/css/borrower.css">
</head>

<body class="admin-portal-body portal-role-borrower">
  <div class="admin-shell">
    <?php
    $portalRole = 'borrower';
    $portalCurrentPage = 'catalog';
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
        <h1>Book Catalog</h1>
        <p class="borrower-page-subtitle">Search by title, author, or ISBN and reserve available titles.</p>
      </header>

      <section class="borrower-dashboard-stats catalog-summary-stats" aria-label="Catalog summary">
        <article class="borrower-card borrower-stat-card">
          <p class="borrower-stat-label">Results</p>
          <p class="borrower-stat-value"><?php echo (int)$catalogResultCount; ?></p>
        </article>
        <article class="borrower-card borrower-stat-card">
          <p class="borrower-stat-label">In-Stock Titles</p>
          <p class="borrower-stat-value"><?php echo (int)$catalogInStockTitles; ?></p>
        </article>
        <article class="borrower-card borrower-stat-card">
          <p class="borrower-stat-label">Available Copies</p>
          <p class="borrower-stat-value"><?php echo (int)$catalogAvailableCopies; ?></p>
        </article>
      </section>

      <?php if ($flash): ?>
        <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <?php if (!$catalog['available']): ?>
        <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$catalog['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section class="borrower-card catalog-filter-card" aria-label="Catalog search and filters">
        <form method="GET" action="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="catalog-search">
          <div class="catalog-filter-group">
            <label for="catalog-query">Search Catalog</label>
            <input id="catalog-query" type="search" name="q" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search title, author, ISBN">
          </div>
          <div class="catalog-filter-group">
            <label for="catalog-category">Category</label>
            <select id="catalog-category" name="category">
              <option value="">All Categories</option>
              <?php foreach (($catalog['categories'] ?? []) as $categoryOption): ?>
                <?php $categoryOption = (string)$categoryOption; ?>
                <option value="<?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $categoryOption === $category ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($categoryOption, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="catalog-filter-actions">
            <button type="submit" class="borrower-btn borrower-btn-primary">Search</button>
            <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-btn borrower-btn-secondary">Clear Filters</a>
          </div>
        </form>
      </section>

      <?php if (empty($catalogRows)): ?>
        <div class="borrower-empty">No catalog titles matched your filters.</div>
      <?php else: ?>
        <div class="catalog-grid">
          <?php foreach ($catalogRows as $row): ?>
            <?php
            $availableCopies = max(0, (int)($row['available_copies'] ?? 0));
            $totalCopies = max(0, (int)($row['total_copies'] ?? 0));
            if ($totalCopies < $availableCopies) {
              $totalCopies = $availableCopies;
            }
            ?>
            <article class="catalog-item borrower-card">
              <div class="catalog-item-main">
                <h3><?php echo htmlspecialchars((string)($row['title'] ?? 'Unknown Title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="catalog-meta catalog-meta-primary"><?php echo htmlspecialchars((string)($row['author'] ?? 'Unknown Author'), ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="catalog-meta catalog-meta-secondary">
                  <?php if (!empty($row['category'])): ?>
                    <span>Category: <?php echo htmlspecialchars((string)$row['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php endif; ?>
                  <span>ISBN: <?php echo htmlspecialchars((string)($row['isbn'] ?: 'N/A'), ENT_QUOTES, 'UTF-8'); ?></span>
                </p>
              </div>
              <div class="catalog-actions">
                <div class="availability<?php echo $availableCopies > 0 ? ' is-available' : ' is-unavailable'; ?>">
                  <strong><?php echo $availableCopies > 0 ? 'Available' : 'Unavailable'; ?></strong>
                  <span><?php echo $availableCopies; ?> of <?php echo $totalCopies; ?> copies ready</span>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars(appPath('catalog.php', array_filter(['q' => $query, 'category' => $category], static function ($value) {
                                                return $value !== '';
                                              })), ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reserveCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="book_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                  <button type="submit" class="borrower-btn borrower-btn-secondary">Reserve</button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>

</html>
