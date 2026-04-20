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
$openAddBookModal = false;
$bookForm = [
  'title' => '',
  'author' => '',
  'isbn' => '',
  'publication_date' => '',
  'genre' => '',
  'cover_image_url' => '',
];

$storeUploadedBookCover = static function (array $file): array {
  $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
  if ($uploadError === UPLOAD_ERR_NO_FILE) {
    return [
      'ok' => true,
      'message' => '',
      'path' => '',
    ];
  }

  if ($uploadError !== UPLOAD_ERR_OK) {
    return [
      'ok' => false,
      'message' => 'Book cover upload failed. Please try again.',
      'path' => '',
    ];
  }

  $tmpName = (string)($file['tmp_name'] ?? '');
  $fileSize = (int)($file['size'] ?? 0);
  if ($tmpName === '' || !is_uploaded_file($tmpName)) {
    return [
      'ok' => false,
      'message' => 'Uploaded file is invalid.',
      'path' => '',
    ];
  }

  if ($fileSize <= 0 || $fileSize > (5 * 1024 * 1024)) {
    return [
      'ok' => false,
      'message' => 'Book cover must be an image up to 5MB.',
      'path' => '',
    ];
  }

  $imageInfo = @getimagesize($tmpName);
  if (!is_array($imageInfo) || empty($imageInfo['mime'])) {
    return [
      'ok' => false,
      'message' => 'Book cover must be a valid image file.',
      'path' => '',
    ];
  }

  $allowedMimeToExtension = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
  ];
  $mime = strtolower(trim((string)$imageInfo['mime']));
  if (!isset($allowedMimeToExtension[$mime])) {
    return [
      'ok' => false,
      'message' => 'Book cover format must be JPG, PNG, WEBP, or GIF.',
      'path' => '',
    ];
  }

  $uploadDir = APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book-covers';
  if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    return [
      'ok' => false,
      'message' => 'Unable to create upload directory for book covers.',
      'path' => '',
    ];
  }

  try {
    $token = bin2hex(random_bytes(16));
  } catch (Exception $e) {
    $token = uniqid('book', true);
    $token = preg_replace('/[^a-zA-Z0-9]/', '', (string)$token) ?: (string)time();
  }

  $fileName = 'book-cover-' . $token . '.' . $allowedMimeToExtension[$mime];
  $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
  if (!move_uploaded_file($tmpName, $targetPath)) {
    return [
      'ok' => false,
      'message' => 'Unable to save uploaded book cover. Please try again.',
      'path' => '',
    ];
  }

  return [
    'ok' => true,
    'message' => 'Cover uploaded.',
    'path' => 'public/uploads/book-covers/' . $fileName,
  ];
};

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
    $bookForm['cover_image_url'] = '';

    if (!$originCheck['valid']) {
      $openAddBookModal = true;
      logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
      error_log('Blocked librarian-books POST due to origin validation: ' . json_encode($originCheck));
      $page_alerts[] = [
        'type' => 'error',
        'title' => 'Security Validation Failed',
        'message' => 'Origin validation failed. Please refresh and try again.',
      ];
    } elseif (!validateAdminCsrfToken($submittedToken)) {
      $openAddBookModal = true;
      logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
      $page_alerts[] = [
        'type' => 'error',
        'title' => 'Security Validation Failed',
        'message' => 'Invalid or missing security token. Please refresh and try again.',
      ];
    } else {
      $coverUpload = $storeUploadedBookCover($_FILES['cover_image'] ?? []);
      if (!$coverUpload['ok']) {
        $page_alerts[] = [
          'type' => 'warning',
          'title' => 'Book Cover Skipped',
          'message' => (string)$coverUpload['message'] . ' The book will still be added without a cover image.',
        ];
        $bookForm['cover_image_url'] = '';
      } else {
        $bookForm['cover_image_url'] = (string)($coverUpload['path'] ?? '');
      }

      $result = LibrarianPortalRepository::addBook($db, $bookForm);
      if (empty($result['ok']) && $bookForm['cover_image_url'] !== '') {
        $savedCoverPath = APP_ROOT . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$bookForm['cover_image_url']);
        if (is_file($savedCoverPath)) {
          @unlink($savedCoverPath);
        }
      }

      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Book Added' : 'Add Book Failed',
        'message' => (string)$result['message'],
      ];

      if (empty($result['ok'])) {
        $openAddBookModal = true;
      }

      if (!empty($result['ok'])) {
        $bookForm = [
          'title' => '',
          'author' => '',
          'isbn' => '',
          'publication_date' => '',
          'genre' => '',
          'cover_image_url' => '',
        ];
      }
    }
  }
}

$search = trim((string)($_GET['q'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? ''));
$catalog = [
  'rows' => [],
  'available' => false,
  'message' => 'Catalog unavailable.',
];

$bookTypeOptions = [];

try {
  $bookTypeOptions = LibrarianPortalRepository::getBookTypes($db, 250);
  $catalog = LibrarianPortalRepository::getBooks($db, $search, 300, $typeFilter);
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
  <link rel="stylesheet" href="<?php echo $librarianCssHref; ?>">
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

    <main class="admin-main librarian-main">
      <div class="librarian-page">
        <div class="librarian-shell">
          <section class="librarian-hero">
            <div class="librarian-hero-copy">
              <span class="librarian-eyebrow">Catalog operations</span>
              <h1>Manage catalog books</h1>
              <p class="librarian-page-subtitle">Add books and update catalog entries.</p>
              <div class="librarian-hero-actions">
                <button type="button" class="admin-button admin-button-primary librarian-btn librarian-btn-primary" data-open-modal="#addBookModal">Add Book</button>
              </div>
            </div>
            <aside class="librarian-hero-card">
              <span class="librarian-hero-card-label">Catalog snapshot</span>
              <strong><?php echo (int)$resultCount; ?> results</strong>
              <p><?php echo (int)$availableCopiesCount; ?> of <?php echo (int)$totalCopiesCount; ?> copies available in current result set.</p>
            </aside>
          </section>

          <div id="addBookModal" class="admin-modal-backdrop" aria-hidden="true">
            <div class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="addBookTitle">
              <div class="admin-modal-header">
                <h2 id="addBookTitle">Add Book</h2>
                <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
              </div>

              <form method="POST" class="admin-form-grid" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add_book">

                <div class="admin-form-field">
                  <label for="add_book_title">Title</label>
                  <input id="add_book_title" type="text" name="title" required maxlength="255" value="<?php echo htmlspecialchars((string)$bookForm['title'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Book title">
                </div>

                <div class="admin-form-field">
                  <label for="add_book_author">Author</label>
                  <input id="add_book_author" type="text" name="author" required maxlength="255" value="<?php echo htmlspecialchars((string)$bookForm['author'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Author name">
                </div>

                <div class="admin-form-field">
                  <label for="add_book_isbn">ISBN</label>
                  <input id="add_book_isbn" type="text" name="isbn" required maxlength="32" value="<?php echo htmlspecialchars((string)$bookForm['isbn'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="ISBN-10 or ISBN-13">
                </div>

                <div class="admin-form-field">
                  <label for="add_book_publication_date">Publication Date</label>
                  <input id="add_book_publication_date" type="date" name="publication_date" required value="<?php echo htmlspecialchars((string)$bookForm['publication_date'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="admin-form-field admin-span-2">
                  <label for="add_book_genre">Genre</label>
                  <input id="add_book_genre" type="text" name="genre" required maxlength="100" value="<?php echo htmlspecialchars((string)$bookForm['genre'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Fiction, Science">
                </div>

                <div class="admin-form-field admin-span-2">
                  <label for="add_book_cover_image">Book Cover</label>
                  <input id="add_book_cover_image" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif">
                  <small class="admin-form-help">Optional. Upload JPG, PNG, WEBP, or GIF (max 5MB).</small>
                </div>

                <div class="admin-modal-actions">
                  <button type="button" class="admin-button admin-button-ghost" data-close-modal>Cancel</button>
                  <button type="submit" class="admin-button admin-button-primary">Add Book</button>
                </div>
              </form>
            </div>
          </div>

          <?php if (!$catalog['available']): ?>
            <div class="librarian-alert librarian-alert-warning" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$catalog['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <section class="librarian-card librarian-surface-card">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Search</span>
                <h2>Find catalog entries</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <form method="GET" class="librarian-toolbar" action="librarian-books.php">
                <div class="librarian-search">
                  <label for="librarian-book-search">Search books</label>
                  <input id="librarian-book-search" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search by title, author, ISBN, category">
                </div>
                <div class="librarian-search">
                  <label for="librarian-book-type">Filter by type</label>
                  <select id="librarian-book-type" name="type">
                    <option value="">All types</option>
                    <?php foreach ($bookTypeOptions as $bookType): ?>
                      <?php $selected = strtolower($typeFilter) === strtolower((string)$bookType); ?>
                      <option value="<?php echo htmlspecialchars((string)$bookType, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$bookType, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" class="admin-button admin-button-primary librarian-btn librarian-btn-primary">Search</button>
                <a href="librarian-books.php" class="admin-button admin-button-ghost librarian-btn librarian-btn-secondary">Reset</a>
              </form>
            </div>
          </section>

          <section class="librarian-stat-grid is-three" aria-label="Books summary">
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Results</p>
              <p class="librarian-stat-value"><?php echo (int)$resultCount; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Total Copies</p>
              <p class="librarian-stat-value"><?php echo (int)$totalCopiesCount; ?></p>
            </article>
            <article class="librarian-card librarian-stat-card">
              <p class="librarian-stat-label">Available Copies</p>
              <p class="librarian-stat-value"><?php echo (int)$availableCopiesCount; ?></p>
            </article>
          </section>

          <section class="librarian-card librarian-surface-card">
            <div class="librarian-panel-heading">
              <div>
                <span class="librarian-section-kicker">Catalog cards</span>
                <h2>Books</h2>
              </div>
            </div>
            <div class="librarian-panel-content">
              <?php if (empty($rows)): ?>
                <div class="librarian-empty">No books matched the current search.</div>
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
                    <article class="librarian-book-card admin-card librarian-card">
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
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>

  <script>
    function openModal(target) {
      if (!target) {
        return;
      }

      target.classList.add('is-open');
      target.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
      if (!modal) {
        return;
      }

      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        var target = document.querySelector(button.getAttribute('data-open-modal'));
        openModal(target);
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        closeModal(button.closest('.admin-modal-backdrop'));
      });
    });

    document.querySelectorAll('.admin-modal-backdrop').forEach(function(backdrop) {
      backdrop.addEventListener('click', function(event) {
        if (event.target === backdrop) {
          closeModal(backdrop);
        }
      });
    });

    document.addEventListener('keydown', function(event) {
      if (event.key !== 'Escape') {
        return;
      }

      document.querySelectorAll('.admin-modal-backdrop.is-open').forEach(function(openBackdrop) {
        closeModal(openBackdrop);
      });
    });

    <?php if ($openAddBookModal): ?>
    openModal(document.getElementById('addBookModal'));
    <?php endif; ?>
  </script>
</body>

</html>
