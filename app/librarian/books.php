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

$cssPaths = getLibrarianCssPaths();
$mainCssHref = $cssPaths['main'];
$adminCssHref = $cssPaths['admin'];
$librarianCssHref = $cssPaths['librarian'];

$page_alerts = getStoredPageAlerts();

$csrfToken = getAdminCsrfToken();
$openAddBookModal = false;
$bookAddResult = null; // Will hold {ok, title, message} for SweetAlert
$bookForm = [
  'title' => '',
  'author' => '',
  'isbn' => '',
  'publication_date' => '',
  'genre' => '',
  'cover_image_url' => '',
  'initial_copies' => 1,
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

// ---- GET handler: fetch book data as JSON for edit modal ----
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_book_id'])) {
  $fetchBookId = max(0, (int)$_GET['fetch_book_id']);
  header('Content-Type: application/json; charset=utf-8');
  if ($fetchBookId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid book ID.']);
    exit;
  }
  $bookData = LibrarianPortalRepository::getBookById($db, $fetchBookId);
  if ($bookData === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Book not found.']);
    exit;
  }
  echo json_encode(['ok' => true, 'book' => $bookData]);
  exit;
}

$bookEditResult = null; // Stores {ok, title, message} for SweetAlert after edit

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $originCheck = validateStateChangingRequestOrigin('librarian_books_post');


  $submittedToken = getPost('csrf_token', '');
  $action = strtolower(getPost('action', ''));

  if ($action === 'add_book') {
    $bookForm['title'] = getPost('title');
    $bookForm['author'] = getPost('author');
    $bookForm['isbn'] = getPost('isbn');
    $bookForm['publication_date'] = getPost('publication_date');
    $bookForm['genre'] = getPost('genre');
    $bookForm['initial_copies'] = (int)getPost('initial_copies', '1');
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

      // Store result for SweetAlert display
      $bookAddResult = [
        'ok' => !empty($result['ok']),
        'title' => $result['ok'] ? 'Book Added Successfully!' : 'Failed to Add Book',
        'message' => (string)$result['message'],
      ];

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
          'initial_copies' => 1,
        ];
      }
    }
  }

  if ($action === 'edit_book') {
    $editBookId = (int)getPost('book_id', '0');
    if (!$originCheck['valid']) {
      logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
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
      $editInput = [
        'book_id' => $editBookId,
        'title' => getPost('title', ''),
        'author' => getPost('author', ''),
        'isbn' => getPost('isbn', ''),
        'publication_date' => getPost('publication_date', ''),
        'genre' => getPost('genre', ''),
        'total_copies' => getPost('total_copies', '-1'),
        'cover_image_url' => getPost('existing_cover_url', ''),
      ];
      $coverUpload = $storeUploadedBookCover($_FILES['edit_cover_image'] ?? []);
      if ($coverUpload['ok'] && $coverUpload['path'] !== '') {
        $editInput['cover_image_url'] = (string)$coverUpload['path'];
      }
      $result = LibrarianPortalRepository::updateBook($db, $editInput);
      $bookEditResult = [
        'ok' => !empty($result['ok']),
        'title' => $result['ok'] ? 'Book Updated Successfully!' : 'Failed to Update Book',
        'message' => (string)$result['message'],
      ];
      $page_alerts[] = [
        'type' => $result['ok'] ? 'success' : 'error',
        'title' => $result['ok'] ? 'Book Updated' : 'Update Book Failed',
        'message' => (string)$result['message'],
      ];
    }
  }
}

$search = trim((string)($_GET['q'] ?? ''));


$typeFilter = trim((string)($_GET['type'] ?? ''));
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$catalog = [
  'rows' => [],
  'available' => false,
  'message' => 'Catalog unavailable.',
];

$bookTypeOptions = [];

try {
  $bookTypeOptions = LibrarianPortalRepository::getBookTypes($db, 250);
  $catalog = LibrarianPortalRepository::getBooks($db, $search, $perPage, $typeFilter, $currentPage);
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

// Build pagination URLs
$paginationBaseUrl = appPath('librarian-books.php');
$paginationQuery = [];
if ($search !== '') {
  $paginationQuery['q'] = $search;
}
if ($typeFilter !== '') {
  $paginationQuery['type'] = $typeFilter;
}
$paginationQueryString = http_build_query($paginationQuery);

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

$resolveCatalogCoverUrl = static function (string $raw, string $isbn = ''): string {
  $value = trim($raw);
  if ($value !== '') {
    if (preg_match('/^https?:\/\//i', $value) === 1) {
      return $value;
    }

    if (str_starts_with($value, '//')) {
      return appPath('images/admin_pic.jpg');
    }

    if (str_starts_with($value, '/')) {
      return $value;
    }

    // If the path starts with "public/", it's a relative path from the web root
    // that needs to be resolved via appPath
    if (str_starts_with($value, 'public/')) {
      return appPath($value);
    }

    // Seed files may store paths as "uploads/...". Normalize to "public/uploads/..."
    // so the URL resolves under the app public directory.
    if (str_starts_with($value, 'uploads/')) {
      return appPath('public/' . ltrim($value, '/'));
    }

    return appPath(ltrim($value, '/'));
  }

  $placeholderPath = appPath('images/book-covers-big-2019101610.jpg');
  return $placeholderPath;
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
  <style>
    .edit-book-modal-card {
      max-height: 95vh !important;
      min-height: 60vh;
      overflow-y: auto !important;
      width: 650px !important;
      max-width: 95vw !important;
    }

    .edit-book-modal-card .admin-form-grid {
      max-height: 72vh;
      overflow-y: auto;
      padding-right: 8px;
    }
  </style>
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

                <div class="admin-form-field">
                  <label for="add_book_initial_copies">Initial Copies</label>
                  <input
                    id="add_book_initial_copies"
                    type="number"
                    name="initial_copies"
                    min="0"
                    max="200"
                    value="<?php echo (int)($bookForm['initial_copies'] ?? 1); ?>">
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

          <!-- Edit Book Modal -->
          <div id="editBookModal" class="admin-modal-backdrop" aria-hidden="true">
            <div class="admin-modal-card admin-modal-card--wide edit-book-modal-card" role="dialog" aria-modal="true" aria-labelledby="editBookTitle">
              <div class="admin-modal-header">
                <h2 id="editBookTitle">Edit Book</h2>
                <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
              </div>

              <form method="POST" class="admin-form-grid" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="edit_book">
                <input type="hidden" name="book_id" id="edit_book_id" value="">
                <input type="hidden" name="existing_cover_url" id="edit_existing_cover_url" value="">

                <div class="admin-form-field">
                  <label for="edit_book_title">Title</label>
                  <input id="edit_book_title" type="text" name="title" required maxlength="255" placeholder="Book title">
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_author">Author</label>
                  <input id="edit_book_author" type="text" name="author" required maxlength="255" placeholder="Author name">
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_isbn">ISBN</label>
                  <input id="edit_book_isbn" type="text" name="isbn" required maxlength="32" placeholder="ISBN-10 or ISBN-13">
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_publication_date">Publication Date</label>
                  <input id="edit_book_publication_date" type="date" name="publication_date" required>
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_genre">Genre</label>
                  <input id="edit_book_genre" type="text" name="genre" required maxlength="100" placeholder="e.g. Fiction, Science">
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_total_copies">Total Copies</label>
                  <input
                    id="edit_book_total_copies"
                    type="number"
                    name="total_copies"
                    min="0"
                    max="200">
                  <small class="admin-form-help">Total physical copies. Available copies adjust automatically.</small>
                </div>

                <div class="admin-form-field">
                  <label for="edit_book_cover_image">New Book Cover (optional)</label>
                  <input id="edit_book_cover_image" type="file" name="edit_cover_image" accept="image/jpeg,image/png,image/webp,image/gif">
                  <small class="admin-form-help">Upload new cover to replace existing. JPG, PNG, WEBP, GIF (max 5MB).</small>
                  <img id="edit_cover_preview" src="" alt="Cover preview" style="display:none;max-width:140px;margin-top:8px;border-radius:10px;border:1px solid var(--admin-line);">
                </div>

                <div class="admin-modal-actions">
                  <button type="button" class="admin-button admin-button-ghost" data-close-modal>Cancel</button>
                  <button type="submit" class="admin-button admin-button-primary">Save Changes</button>
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
              <form method="GET" class="librarian-toolbar" action="<?php echo htmlspecialchars(appPath('librarian-books.php'), ENT_QUOTES, 'UTF-8'); ?>">
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
                <div class="librarian-pagination">
                  <span class="librarian-pagination-info">Page <?php echo $currentPage; ?> &middot; <?php echo $resultCount; ?> books shown</span>
                  <div class="librarian-pagination-controls">
                    <?php if ($currentPage > 1): ?>
                      <a class="admin-button admin-button-ghost librarian-btn librarian-btn-secondary" href="<?php echo htmlspecialchars($paginationBaseUrl . '?' . ($paginationQueryString !== '' ? $paginationQueryString . '&' : '') . 'page=' . ($currentPage - 1), ENT_QUOTES, 'UTF-8'); ?>">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php if (!empty($catalog['has_more'])): ?>
                      <a class="admin-button admin-button-primary librarian-btn librarian-btn-primary" href="<?php echo htmlspecialchars($paginationBaseUrl . '?' . ($paginationQueryString !== '' ? $paginationQueryString . '&' : '') . 'page=' . ($currentPage + 1), ENT_QUOTES, 'UTF-8'); ?>">Next &raquo;</a>
                    <?php endif; ?>
                  </div>
                </div>
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

                    $coverUrl = $resolveCatalogCoverUrl((string)($row['cover_image_url'] ?? ''), $isbn);
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

                        <button
                          type="button"
                          class="admin-button admin-button-ghost librarian-btn librarian-btn-secondary librarian-book-edit-btn"
                          data-book-id="<?php echo (int)($row['id'] ?? 0); ?>">Edit</button>
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

  <?php if ($bookAddResult !== null): ?>
    <script>
      (function() {
        var result = <?php echo json_encode($bookAddResult, JSON_UNESCAPED_SLASHES); ?>;
        if (result.ok) {
          Swal.fire({
            icon: 'success',
            title: result.title,
            text: result.message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d24718',
            allowOutsideClick: false,
            allowEscapeKey: false
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: result.title,
            text: result.message,
            confirmButtonText: 'OK',
            confirmButtonColor: '#d24718',
            allowOutsideClick: false,
            allowEscapeKey: false
          });
        }
      })();
    </script>
  <?php endif; ?>

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



  <script>
    (function() {
      var editModal = document.getElementById('editBookModal');
      if (!editModal) return;

      var editForm = editModal.querySelector('form');
      var editSubmitBtn = editModal.querySelector('[type="submit"]');

      function showCoverPreview(coverUrl) {
        var preview = document.getElementById('edit_cover_preview');
        if (!preview) return;
        if (coverUrl) {
          preview.src = coverUrl;
          preview.style.display = 'block';
        } else {
          preview.style.display = 'none';
          preview.src = '';
        }
      }

      function populateEditForm(book) {
        document.getElementById('edit_book_id').value = book.id || '';
        document.getElementById('edit_existing_cover_url').value = book.cover_image || '';
        document.getElementById('edit_book_title').value = book.title || '';
        document.getElementById('edit_book_author').value = book.author || '';
        document.getElementById('edit_book_isbn').value = book.isbn || '';
        document.getElementById('edit_book_publication_date').value = book.publication_date || '';
        document.getElementById('edit_book_genre').value = book.category || '';
        document.getElementById('edit_book_total_copies').value = book.total_copies || 0;

        // Show existing cover preview if present
        var coverRaw = book.cover_image || '';
        if (coverRaw) {
          showCoverPreview(coverRaw.indexOf('//') >= 0 || coverRaw.indexOf('/') === 0
            ? coverRaw
            : (coverRaw.indexOf('public/') === 0 ? '' : '') + coverRaw);
        } else {
          showCoverPreview('');
        }
      }

      // Cover file input preview
      var coverInput = document.getElementById('edit_book_cover_image');
      if (coverInput) {
        coverInput.addEventListener('change', function() {
          var file = coverInput.files && coverInput.files[0];
          if (file) {
            var reader = new FileReader();
            reader.onload = function(e) { showCoverPreview(e.target.result); };
            reader.readAsDataURL(file);
          }
        });
      }

      // Form reset, loading state, autofocus on modal close
      editModal.querySelectorAll('[data-close-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
          editForm.reset();
          showCoverPreview('');
          document.getElementById('edit_book_id').value = '';
          document.getElementById('edit_existing_cover_url').value = '';
          editSubmitBtn.disabled = false;
          editSubmitBtn.textContent = 'Save Changes';
        });
      });

      editModal.addEventListener('click', function(event) {
        if (event.target === editModal) {
          editForm.reset();
          showCoverPreview('');
          document.getElementById('edit_book_id').value = '';
          document.getElementById('edit_existing_cover_url').value = '';
          editSubmitBtn.disabled = false;
          editSubmitBtn.textContent = 'Save Changes';
        }
      });

      function openEditModal(bookId) {
        if (!bookId) return;

        editSubmitBtn.disabled = true;
        editSubmitBtn.textContent = 'Loading...';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'librarian-books.php?fetch_book_id=' + encodeURIComponent(bookId), true);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.onload = function() {
          editSubmitBtn.disabled = false;
          editSubmitBtn.textContent = 'Save Changes';

          if (xhr.status !== 200) {
            Swal.fire({
              icon: 'error',
              title: 'Failed to Load Book',
              text: 'Unable to fetch book details. Please try again.',
              confirmButtonColor: '#d24718'
            });
            return;
          }

          try {
            var response = JSON.parse(xhr.responseText);
          } catch (e) {
            Swal.fire({
              icon: 'error',
              title: 'Invalid Response',
              text: 'Server returned unexpected data.',
              confirmButtonColor: '#d24718'
            });
            return;
          }

          if (!response.ok || !response.book) {
            Swal.fire({
              icon: 'error',
              title: 'Book Not Found',
              text: response.message || 'The requested book could not be found.',
              confirmButtonColor: '#d24718'
            });
            return;
          }

          populateEditForm(response.book);
          openModal(editModal);

          // Autofocus title field
          setTimeout(function() {
            var titleInput = document.getElementById('edit_book_title');
            if (titleInput) titleInput.focus();
          }, 200);
        };

        xhr.onerror = function() {
          editSubmitBtn.disabled = false;
          editSubmitBtn.textContent = 'Save Changes';
          Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not reach the server. Please check your connection.',
            confirmButtonColor: '#d24718'
          });
        };

        xhr.send();
      }

      document.querySelectorAll('.librarian-book-edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var bookId = btn.getAttribute('data-book-id');
          openEditModal(bookId);
        });
      });
    })();
  </script>
</body>


</html>