<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/LibrarianPortalRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';

PermissionGate::requirePageAccess('librarian-books');

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
$bookForm = [
  'title' => '',
  'author' => '',
  'isbn' => '',
  'publication_date' => '',
  'genre' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('librarian_books_post');
  $submittedToken = $_POST['csrf_token'] ?? '';
  $action = strtolower(trim((string)($_POST['action'] ?? '')));

  if ($action === 'add_book') {
    $bookForm['title'] = trim((string)($_POST['title'] ?? ''));
    $bookForm['author'] = trim((string)($_POST['author'] ?? ''));
    $bookForm['isbn'] = trim((string)($_POST['isbn'] ?? ''));
    $bookForm['publication_date'] = trim((string)($_POST['publication_date'] ?? ''));
    $bookForm['genre'] = trim((string)($_POST['genre'] ?? ''));

    if (!$originCheck['valid']) {
      logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
      error_log('Blocked librarian-books POST due to origin validation: ' . json_encode($originCheck));
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
      $result = LibrarianPortalRepository::addBook($db, $bookForm);
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Book Added' : 'Add Book Failed',
        'message' => (string)$result['message'],
      ];

      if (!empty($result['ok'])) {
        $bookForm = [
          'title' => '',
          'author' => '',
          'isbn' => '',
          'publication_date' => '',
          'genre' => '',
        ];
      }
    }
  }
}

$search = trim((string)($_GET['q'] ?? ''));
$catalog = [
  'rows' => [],
  'available' => false,
  'message' => 'Catalog unavailable.',
];

try {
  $catalog = LibrarianPortalRepository::getBooks($db, $search, 300);
} catch (Exception $e) {
  error_log('librarian-books list error: ' . $e->getMessage());
  $catalog['message'] = 'Unable to load books catalog right now.';
}

$rows = $catalog['rows'];
$resultCount = count($rows);
$totalCopiesCount = 0;
$availableCopiesCount = 0;
foreach ($rows as $rowSummary) {
  $totalCopiesCount += max(0, (int)($rowSummary['total_copies'] ?? 0));
  $availableCopiesCount += max(0, (int)($rowSummary['available_copies'] ?? 0));
}

$truncateCatalogText = static function (string $value, int $limit = 190): string {
  $normalized = trim(preg_replace('/\s+/', ' ', $value) ?? '');
  if ($normalized === '') {
    return '';
  }

  if (function_exists('mb_strlen') && function_exists('mb_substr')) {
    if (mb_strlen($normalized) <= $limit) {
      return $normalized;
    }

    return rtrim((string)mb_substr($normalized, 0, max(1, $limit - 1))) . '...';
  }

  if (strlen($normalized) <= $limit) {
    return $normalized;
  }

  return rtrim(substr($normalized, 0, max(1, $limit - 1))) . '...';
};

$resolveCatalogCoverUrl = static function (string $raw): string {
  $value = trim($raw);
  if ($value === '') {
    return '';
  }

  if (preg_match('/^https?:\/\//i', $value) === 1) {
    return $value;
  }

  if (str_starts_with($value, '/')) {
    return $value;
  }

  return appPath(ltrim($value, '/'));
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Librarian Books</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body portal-role-librarian">
  <div class="admin-shell">
    <?php
    $portalRole = 'librarian';
    $portalCurrentPage = 'books';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $roleLabel;
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Books Catalog</h1>
        <p>Add and manage catalog entries with search by title, author, ISBN, and category.</p>
      </header>

      <section class="admin-card" style="margin-bottom:16px;">
        <div class="admin-card-header">
          <h2>Add Book</h2>
          <p>Enter core bibliographic details to add a new catalog title.</p>
        </div>

        <form method="POST" class="admin-inline-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;align-items:end;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="add_book">

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Title</span>
            <input type="text" name="title" required maxlength="255" value="<?php echo htmlspecialchars((string)$bookForm['title'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Book title">
          </label>

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Author</span>
            <input type="text" name="author" required maxlength="255" value="<?php echo htmlspecialchars((string)$bookForm['author'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Author name">
          </label>

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">ISBN</span>
            <input type="text" name="isbn" required maxlength="32" value="<?php echo htmlspecialchars((string)$bookForm['isbn'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="ISBN-10 or ISBN-13">
          </label>

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Publication Date</span>
            <input type="date" name="publication_date" required value="<?php echo htmlspecialchars((string)$bookForm['publication_date'], ENT_QUOTES, 'UTF-8'); ?>">
          </label>

          <label style="display:flex;flex-direction:column;gap:6px;">
            <span class="admin-demo-note">Genre</span>
            <input type="text" name="genre" required maxlength="100" value="<?php echo htmlspecialchars((string)$bookForm['genre'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Fiction, Science">
          </label>

          <div>
            <button type="submit" class="admin-button admin-button-primary">Add Book</button>
          </div>
        </form>
      </section>

      <?php if (!$catalog['available']): ?>
        <div class="admin-alert admin-alert-warning" role="status" aria-live="polite">
          <?php echo htmlspecialchars((string)$catalog['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section class="admin-card">
        <form method="GET" class="admin-toolbar" action="librarian-books.php">
          <label class="admin-search" aria-label="Search books">
            <input type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by title, author, ISBN, category">
          </label>
          <button type="submit" class="admin-button admin-button-primary">Search</button>
          <a href="librarian-books.php" class="admin-button admin-button-ghost">Reset</a>
        </form>

        <div class="admin-stats-row admin-inline-stats">
          <article class="admin-stat-tile">
            <strong><?php echo (int)$resultCount; ?></strong>
            <span>Results</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$totalCopiesCount; ?></strong>
            <span>Total Copies</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$availableCopiesCount; ?></strong>
            <span>Available Copies</span>
          </article>
        </div>

        <?php if (empty($rows)): ?>
          <div class="admin-empty-state">No books matched the current search.</div>
        <?php else: ?>
          <div class="librarian-books-grid">
            <?php foreach ($rows as $row): ?>
              <?php
              $isbn = trim((string)($row['isbn'] ?? ''));
              $categoryLabel = trim((string)($row['category'] ?? ''));
              $authorLabel = trim((string)($row['author'] ?? ''));
              if ($authorLabel === '') {
                $authorLabel = 'Unknown author';
              }
              $titleLabel = trim((string)($row['title'] ?? ''));
              if ($titleLabel === '') {
                $titleLabel = 'Unknown title';
              }
              $yearLabel = trim((string)($row['published_year'] ?? ''));
              $bookTotalCopies = max(0, (int)($row['total_copies'] ?? 0));
              $bookAvailableCopies = max(0, (int)($row['available_copies'] ?? 0));
              $bookDescription = trim((string)($row['description'] ?? ''));
              if ($bookDescription === '') {
                $bookDescription = 'Catalog title by ' . $authorLabel
                  . ($categoryLabel !== '' ? ' in ' . $categoryLabel : '')
                  . ($yearLabel !== '' ? ' (' . $yearLabel . ')' : '') . '.';
              }
              $bookDescription = $truncateCatalogText($bookDescription, 185);

              $coverUrl = $resolveCatalogCoverUrl((string)($row['cover_image_url'] ?? ''));
              $placeholderSeed = strtoupper(substr($titleLabel, 0, 1));
              if (!preg_match('/[A-Z0-9]/', $placeholderSeed)) {
                $placeholderSeed = '#';
              }

              $statusClass = $bookAvailableCopies > 0 ? 'is-available' : 'is-unavailable';
              $statusLabel = $bookAvailableCopies > 0 ? 'Available' : 'Unavailable';
              ?>
              <article class="librarian-book-card admin-card">
                <div class="librarian-book-cover">
                  <?php if ($coverUrl !== ''): ?>
                    <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Cover of <?php echo htmlspecialchars($titleLabel, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                  <?php else: ?>
                    <div class="librarian-book-placeholder" aria-hidden="true"><?php echo htmlspecialchars($placeholderSeed, ENT_QUOTES, 'UTF-8'); ?></div>
                  <?php endif; ?>
                </div>

                <div class="librarian-book-body">
                  <div class="librarian-book-header">
                    <span class="librarian-book-id">#<?php echo (int)($row['id'] ?? 0); ?></span>
                    <span class="librarian-book-status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                  </div>

                  <h3><?php echo htmlspecialchars($titleLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p class="librarian-book-author"><?php echo htmlspecialchars($authorLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                  <p class="librarian-book-description"><?php echo htmlspecialchars($bookDescription, ENT_QUOTES, 'UTF-8'); ?></p>

                  <div class="librarian-book-meta">
                    <span>ISBN: <?php echo htmlspecialchars($isbn !== '' ? $isbn : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>Category: <?php echo htmlspecialchars($categoryLabel !== '' ? $categoryLabel : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    <span>Year: <?php echo htmlspecialchars($yearLabel !== '' ? $yearLabel : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>

                  <div class="librarian-book-inventory">
                    <span><strong><?php echo $bookAvailableCopies; ?></strong> available</span>
                    <span><?php echo $bookTotalCopies; ?> total copies</span>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
