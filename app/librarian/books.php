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
        <p>Catalog listing with lightweight search by title, author, ISBN, and category.</p>
      </header>

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

        <div class="admin-table-wrap">
          <table class="admin-table admin-table-compact librarian-books-table">
            <thead>
              <tr>
                <th>Book ID</th>
                <th>Book Details</th>
                <th>Inventory</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="3" class="admin-empty-state">No books matched the current search.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rows as $row): ?>
                  <?php
                  $isbn = trim((string)($row['isbn'] ?? ''));
                  $categoryLabel = trim((string)($row['category'] ?? ''));
                  $authorLabel = trim((string)($row['author'] ?? ''));
                  $titleLabel = trim((string)($row['title'] ?? ''));
                  $yearLabel = trim((string)($row['published_year'] ?? ''));
                  $bookTotalCopies = max(0, (int)($row['total_copies'] ?? 0));
                  $bookAvailableCopies = max(0, (int)($row['available_copies'] ?? 0));
                  ?>
                  <tr>
                    <td>#<?php echo (int)($row['id'] ?? 0); ?></td>
                    <td>
                      <div class="admin-table-identity"><?php echo htmlspecialchars($titleLabel !== '' ? $titleLabel : 'Unknown title', ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="admin-table-meta"><?php echo htmlspecialchars($authorLabel !== '' ? $authorLabel : 'Unknown author', ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="admin-table-submeta">
                        <span>ISBN: <?php echo htmlspecialchars($isbn !== '' ? $isbn : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>Category: <?php echo htmlspecialchars($categoryLabel !== '' ? $categoryLabel : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>Year: <?php echo htmlspecialchars($yearLabel !== '' ? $yearLabel : '-', ENT_QUOTES, 'UTF-8'); ?></span>
                      </div>
                    </td>
                    <td>
                      <div class="admin-inventory-stack">
                        <span><strong><?php echo $bookAvailableCopies; ?></strong> available</span>
                        <span><?php echo $bookTotalCopies; ?> total copies</span>
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
