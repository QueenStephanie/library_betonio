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

$resolveBookValue = static function (array $row, array $candidates, string $fallback = ''): string {
  foreach ($candidates as $candidate) {
    if (array_key_exists($candidate, $row)) {
      $value = trim((string)($row[$candidate] ?? ''));
      if ($value !== '') {
        return $value;
      }
    }
  }

  return $fallback;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('borrower_catalog_reserve_post');
  $submittedToken = getPost('csrf_token');
  $action = trim((string)getPost('action', 'reserve'));
  $bookId = (int)getPost('book_id', '0');
  $reservationId = (int)getPost('reservation_id', '0');
  $currentUserId = (int)($_SESSION['user_id'] ?? 0);

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
    if ($action === 'cancel_receipt_reservation') {
      $cancelResult = CirculationRepository::cancelBorrowerReservation($db, $currentUserId, $reservationId);
      if (!empty($cancelResult['ok'])) {
        setFlash('success', 'Reservation cancelled successfully.');
      } else {
        setFlash('error', (string)($cancelResult['message'] ?? 'Unable to cancel reservation.'));
      }
      unset($_SESSION['borrower_catalog_reservation_receipt']);
    } else {
      $result = CirculationRepository::createBorrowerReservation(
        $db,
        $currentUserId,
        $bookId,
        CirculationRepository::getBorrowerMaxActiveReservations()
      );

      if (!empty($result['ok'])) {
        $queuePosition = isset($result['queue_position']) ? (int)$result['queue_position'] : 0;

        $bookTitle = 'Unknown Title';
        $bookAuthor = 'Unknown Author';
        $bookIsbn = 'N/A';
        $bookCategory = 'General';
        $bookCallNumber = 'N/A';
        $bookCoverUrl = '';
        $bookAvailabilityCount = 0;

        try {
          $bookStmt = $db->prepare('SELECT * FROM books WHERE id = :book_id LIMIT 1');
          $bookStmt->execute([':book_id' => $bookId]);
          $bookRow = $bookStmt->fetch(PDO::FETCH_ASSOC);

          if (is_array($bookRow)) {
            $bookTitle = $resolveBookValue($bookRow, ['title'], $bookTitle);
            $bookAuthor = $resolveBookValue($bookRow, ['author'], $bookAuthor);
            $bookIsbn = $resolveBookValue($bookRow, ['isbn'], $bookIsbn);
            $bookCategory = $resolveBookValue($bookRow, ['category', 'genre'], $bookCategory);
            $bookCallNumber = $resolveBookValue(
              $bookRow,
              ['call_number', 'shelf_location', 'shelf_code', 'location_code', 'classification_code'],
              $bookCallNumber
            );
            $bookCoverUrl = $resolveBookValue(
              $bookRow,
              ['cover_image_url', 'cover_url', 'image_url', 'thumbnail_url', 'book_cover', 'book_image'],
              ''
            );

            $bookAvailabilityCount = max(
              0,
              (int)$resolveBookValue($bookRow, ['available_copies', 'copies_available'], '0')
            );
          }

          try {
            $copiesStmt = $db->prepare('SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = :status');
            $copiesStmt->execute([
              ':book_id' => $bookId,
              ':status' => 'available',
            ]);
            $bookAvailabilityCount = max(0, (int)$copiesStmt->fetchColumn());
          } catch (Exception $e) {
            error_log('borrower catalog available copy lookup error: ' . $e->getMessage());
          }
        } catch (Exception $e) {
          error_log('borrower catalog receipt lookup error: ' . $e->getMessage());
        }

        $generatedAtIso = date('c');
        $pickupDeadlineIso = date('c', strtotime('+48 hours'));
        $reservationExpiryIso = date('c', strtotime('+48 hours'));

        $availabilityStatusKey = 'pending';
        $availabilityStatusLabel = 'Pending';
        $statusHeadline = 'Reservation Placed - Waiting for Availability';
        if ($bookAvailabilityCount > 0 && $queuePosition <= 1) {
          $availabilityStatusKey = 'ready';
          $availabilityStatusLabel = 'Ready for Pickup';
          $statusHeadline = 'Reservation Confirmed - Ready for Pickup';
        } elseif ($queuePosition > 1) {
          $availabilityStatusKey = 'on_hold';
          $availabilityStatusLabel = 'On Hold';
        }

        $message = $statusHeadline . '.';
        if ($queuePosition > 0) {
          $message .= ' Queue position: ' . $queuePosition . '.';
        }

        $customerName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
        if ($customerName === '') {
          $customerName = trim((string)($user['email'] ?? 'Borrower User'));
        }

        $resolvedReservationId = (int)($result['reservation_id'] ?? 0);
        if ($resolvedReservationId > 0) {
          $receiptNumber = 'RCP-' . date('Y') . '-' . str_pad((string)$resolvedReservationId, 5, '0', STR_PAD_LEFT);
          $verificationCode = strtoupper(substr(hash('sha256', $resolvedReservationId . '|' . $currentUserId . '|' . $generatedAtIso . '|' . session_id()), 0, 12));

          $_SESSION['borrower_catalog_reservation_receipt'] = [
            'confirmed' => true,
            'reservation_id' => $resolvedReservationId,
            'receipt_number' => $receiptNumber,
            'receipt_code' => trim((string)($result['receipt_code'] ?? '')),
            'verification_code' => $verificationCode,
            'queue_position' => $queuePosition,
            'customer_name' => $customerName,
            'customer_email' => trim((string)($user['email'] ?? '')),
            'generated_at' => $generatedAtIso,
            'transaction_datetime' => $generatedAtIso,
            'pickup_location' => 'Circulation Desk - Main Library',
            'pickup_deadline' => $pickupDeadlineIso,
            'reservation_expires_at' => $reservationExpiryIso,
            'availability_status_key' => $availabilityStatusKey,
            'availability_status_label' => $availabilityStatusLabel,
            'status_headline' => $statusHeadline,
            'items' => [
              [
                'label' => $bookTitle,
                'details' => 'Author: ' . $bookAuthor,
                'amount' => 0,
                'isbn' => $bookIsbn,
                'call_number' => $bookCallNumber,
                'category' => $bookCategory,
                'cover_image_url' => $bookCoverUrl,
              ],
            ],
            'fees_label' => 'Fees: None (Free Reservation)',
            'subtotal' => 0,
            'taxes_fees' => 0,
            'total_amount' => 0,
            'currency' => 'PHP',
            'payment_method' => 'No payment required (reservation hold)',
            'policies' => [
              'Reservation is valid for 48 hours only.',
              'Unclaimed items are released to the next borrower in queue.',
              'Cancel from your account to free the queue slot immediately.',
            ],
          ];
        } else {
          unset($_SESSION['borrower_catalog_reservation_receipt']);
          $message .= ' Receipt details are temporarily unavailable.';
        }

        setFlash('success', $message);
      } else {
        setFlash('error', (string)($result['message'] ?? 'Unable to create reservation.'));
      }
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
$reservationReceiptOverlay = null;
if (isset($_SESSION['borrower_catalog_reservation_receipt']) && is_array($_SESSION['borrower_catalog_reservation_receipt'])) {
  $reservationReceiptOverlay = $_SESSION['borrower_catalog_reservation_receipt'];
}
unset($_SESSION['borrower_catalog_reservation_receipt']);

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
$catalogHeading = 'Find your next book';
if ($query !== '' || $category !== '') {
  $catalogHeading = 'Filtered catalog results';
}

$truncateCatalogText = static function (string $value, int $limit = 180): string {
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

    return appPath(ltrim($value, '/'));
  }

  $placeholderPath = appPath('images/book-covers-big-2019101610.jpg');
  return $placeholderPath;
};

$formatReceiptMoney = static function ($value, string $currency = 'PHP'): string {
  $amount = is_numeric($value) ? (float)$value : 0.0;
  return $currency . ' ' . number_format($amount, 2, '.', ',');
};

$receiptItems = [];
if (is_array($reservationReceiptOverlay['items'] ?? null)) {
  foreach ($reservationReceiptOverlay['items'] as $receiptItem) {
    if (!is_array($receiptItem)) {
      continue;
    }

    $receiptItems[] = [
      'label' => trim((string)($receiptItem['label'] ?? 'Reservation item')),
      'details' => trim((string)($receiptItem['details'] ?? '')),
      'amount' => is_numeric($receiptItem['amount'] ?? null) ? (float)$receiptItem['amount'] : 0.0,
    ];
  }
}

if (empty($receiptItems)) {
  $receiptItems[] = [
    'label' => 'Reservation hold request',
    'details' => 'Library reservation service',
    'amount' => 0.0,
  ];
}

$receiptCurrency = trim((string)($reservationReceiptOverlay['currency'] ?? 'PHP'));
if ($receiptCurrency === '') {
  $receiptCurrency = 'PHP';
}

$receiptDateRaw = trim((string)($reservationReceiptOverlay['transaction_datetime'] ?? ''));
$receiptTimestamp = $receiptDateRaw !== '' ? strtotime($receiptDateRaw) : false;
$receiptDateDisplay = $receiptTimestamp !== false
  ? date('M j, Y g:i A', $receiptTimestamp)
  : date('M j, Y g:i A');

$receiptGeneratedRaw = trim((string)($reservationReceiptOverlay['generated_at'] ?? ''));
$receiptGeneratedTimestamp = $receiptGeneratedRaw !== '' ? strtotime($receiptGeneratedRaw) : false;
$receiptGeneratedDisplay = $receiptGeneratedTimestamp !== false
  ? date('M j, Y g:i A', $receiptGeneratedTimestamp)
  : $receiptDateDisplay;

$pickupDeadlineRaw = trim((string)($reservationReceiptOverlay['pickup_deadline'] ?? ''));
$pickupDeadlineTimestamp = $pickupDeadlineRaw !== '' ? strtotime($pickupDeadlineRaw) : false;
$pickupDeadlineDisplay = $pickupDeadlineTimestamp !== false
  ? date('M j, Y g:i A', $pickupDeadlineTimestamp)
  : 'Pending schedule';

$reservationExpiryRaw = trim((string)($reservationReceiptOverlay['reservation_expires_at'] ?? ''));
$reservationExpiryTimestamp = $reservationExpiryRaw !== '' ? strtotime($reservationExpiryRaw) : false;
$reservationExpiryDisplay = $reservationExpiryTimestamp !== false
  ? date('M j, Y g:i A', $reservationExpiryTimestamp)
  : 'Pending schedule';

$receiptDueDateRaw = trim((string)(
  $reservationReceiptOverlay['due_date']
  ?? $reservationReceiptOverlay['due_datetime']
  ?? $reservationReceiptOverlay['due_at']
  ?? ''
));
$receiptDueDateTimestamp = $receiptDueDateRaw !== '' ? strtotime($receiptDueDateRaw) : false;
$receiptDueDateDisplay = $receiptDueDateTimestamp !== false
  ? date('M j, Y g:i A', $receiptDueDateTimestamp)
  : '';

$receiptHasBorrowField = $receiptDueDateTimestamp !== false
  || !empty($reservationReceiptOverlay['borrow_id'])
  || !empty($reservationReceiptOverlay['loan_id'])
  || !empty($reservationReceiptOverlay['borrowed_at']);

$receiptTypeLabel = $receiptHasBorrowField ? 'Borrow Receipt' : 'Reservation Receipt';
$receiptDeadlineLabel = $receiptHasBorrowField ? 'Due Date' : 'Pickup Deadline';
$receiptDeadlineDisplay = $receiptHasBorrowField && $receiptDueDateDisplay !== ''
  ? $receiptDueDateDisplay
  : $pickupDeadlineDisplay;

$receiptReservationId = (int)($reservationReceiptOverlay['reservation_id'] ?? 0);
$receiptNumber = trim((string)($reservationReceiptOverlay['receipt_number'] ?? ''));
$receiptCode = trim((string)($reservationReceiptOverlay['receipt_code'] ?? ''));
$receiptVerificationCode = trim((string)($reservationReceiptOverlay['verification_code'] ?? ''));
$receiptQueuePosition = max(0, (int)($reservationReceiptOverlay['queue_position'] ?? 0));
$receiptCustomerName = trim((string)($reservationReceiptOverlay['customer_name'] ?? 'Borrower User'));
$receiptCustomerEmail = trim((string)($reservationReceiptOverlay['customer_email'] ?? ''));
$receiptPickupLocation = trim((string)($reservationReceiptOverlay['pickup_location'] ?? 'Main Library Desk'));
$receiptStatusKey = trim((string)($reservationReceiptOverlay['availability_status_key'] ?? 'pending'));
$receiptStatusLabel = trim((string)($reservationReceiptOverlay['availability_status_label'] ?? 'Pending'));
$receiptStatusHeadline = trim((string)($reservationReceiptOverlay['status_headline'] ?? 'Reservation Placed - Waiting for Availability'));
$receiptFeesLabel = trim((string)($reservationReceiptOverlay['fees_label'] ?? 'Fees: None (Free Reservation)'));
$receiptSubtotal = is_numeric($reservationReceiptOverlay['subtotal'] ?? null) ? (float)$reservationReceiptOverlay['subtotal'] : 0.0;
$receiptTaxesFees = is_numeric($reservationReceiptOverlay['taxes_fees'] ?? null) ? (float)$reservationReceiptOverlay['taxes_fees'] : 0.0;
$receiptTotalAmount = is_numeric($reservationReceiptOverlay['total_amount'] ?? null) ? (float)$reservationReceiptOverlay['total_amount'] : 0.0;
$receiptPaymentMethod = trim((string)($reservationReceiptOverlay['payment_method'] ?? 'No payment required'));
$receiptIsConfirmed = !empty($reservationReceiptOverlay['confirmed']);
$receiptFileBase = $receiptCode !== ''
  ? strtolower($receiptCode)
  : ($receiptReservationId > 0 ? ('reservation-' . $receiptReservationId) : 'reservation-receipt');
$receiptPolicies = [];
if (is_array($reservationReceiptOverlay['policies'] ?? null)) {
  foreach ($reservationReceiptOverlay['policies'] as $policy) {
    $policyText = trim((string)$policy);
    if ($policyText !== '') {
      $receiptPolicies[] = $policyText;
    }
  }
}

if (empty($receiptPolicies)) {
  $receiptPolicies = [
    'Reservation is valid for 48 hours only.',
    'Unclaimed items are released to the next borrower in queue.',
  ];
}

$firstReceiptItem = $receiptItems[0] ?? [
  'label' => 'Reservation item',
  'details' => '',
  'amount' => 0,
];

$receiptBookAuthor = trim((string)($firstReceiptItem['details'] ?? ''));
if (stripos($receiptBookAuthor, 'author:') === 0) {
  $receiptBookAuthor = trim((string)substr($receiptBookAuthor, 7));
}

$receiptBookIsbn = trim((string)($firstReceiptItem['isbn'] ?? 'N/A'));
$receiptBookCallNumber = trim((string)($firstReceiptItem['call_number'] ?? 'N/A'));
$receiptBookCategory = trim((string)($firstReceiptItem['category'] ?? 'General'));
$receiptBookCoverRaw = trim((string)($firstReceiptItem['cover_image_url'] ?? ''));
$receiptBookCoverUrl = $receiptBookCoverRaw !== ''
  ? $resolveCatalogCoverUrl($receiptBookCoverRaw, $receiptBookIsbn)
  : $resolveCatalogCoverUrl('', $receiptBookIsbn);

$receiptBarcodeDisplay = preg_replace('/[^A-Z0-9]/', '', strtoupper($receiptVerificationCode !== '' ? $receiptVerificationCode : ('R' . $receiptReservationId)));
$isReceiptExpiringSoon = false;
if ($pickupDeadlineTimestamp !== false) {
  $hoursRemaining = ($pickupDeadlineTimestamp - time()) / 3600;
  $isReceiptExpiringSoon = $hoursRemaining > 0 && $hoursRemaining <= 24;
}

$showFeeBreakdown = abs($receiptSubtotal) > 0.0001 || abs($receiptTaxesFees) > 0.0001 || abs($receiptTotalAmount) > 0.0001;

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
  <title>QueenLib | Catalog</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $borrowerCssHref; ?>">
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" defer></script>
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
          <section class="borrower-hero borrower-page-hero">
            <div class="borrower-hero-copy">
              <span class="borrower-eyebrow">Catalog</span>
              <h1><?php echo htmlspecialchars($catalogHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="borrower-page-subtitle">Search and reserve books.</p>
            </div>
            <aside class="borrower-hero-card">
              <span class="borrower-hero-card-label">Search coverage</span>
              <strong><?php echo (int)$catalogResultCount; ?> titles</strong>
              <p><?php echo (int)$catalogAvailableCopies; ?> total copies currently available for checkout.</p>
            </aside>
          </section>

          <section class="borrower-dashboard-stats catalog-summary-stats borrower-stat-grid" aria-label="Catalog summary">
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

          <section class="borrower-card borrower-surface-card catalog-filter-card" aria-label="Catalog search and filters">
            <div class="borrower-panel-heading borrower-panel-heading-tight">
              <div>
                <span class="borrower-section-kicker">Filters</span>
                <h2>Search the collection</h2>
              </div>
            </div>
            <div class="borrower-panel-content">
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
              <div class="catalog-active-filters">
                <span class="borrower-chip<?php echo $query !== '' ? ' is-active' : ''; ?>">Query: <?php echo htmlspecialchars($query !== '' ? $query : 'None', ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="borrower-chip<?php echo $category !== '' ? ' is-active' : ''; ?>">Category: <?php echo htmlspecialchars($category !== '' ? $category : 'All', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
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

                $titleLabel = trim((string)($row['title'] ?? ''));
                if ($titleLabel === '') {
                  $titleLabel = 'Unknown Title';
                }
                $authorLabel = trim((string)($row['author'] ?? ''));
                if ($authorLabel === '') {
                  $authorLabel = 'Unknown Author';
                }

                $categoryLabel = trim((string)($row['category'] ?? ''));
                $yearLabel = trim((string)($row['published_year'] ?? ''));
                $isbnLabel = trim((string)($row['isbn'] ?? ''));

                $coverUrl = $resolveCatalogCoverUrl((string)($row['cover_image_url'] ?? ''), $isbnLabel);
                $bookDescription = trim((string)($row['description'] ?? ''));
                if ($bookDescription === '') {
                  $bookDescription = 'A ' . ($categoryLabel !== '' ? strtolower($categoryLabel) : 'library') . ' title by ' . $authorLabel
                    . ($yearLabel !== '' ? ' (' . $yearLabel . ')' : '') . '.';
                }
                $bookDescription = $truncateCatalogText($bookDescription, 170);

                $availabilityLabel = $availableCopies > 0 ? 'Available' : ($totalCopies === 0 ? 'Inventory not set' : 'Unavailable');
                $availabilityDetail = $totalCopies === 0
                  ? 'Ask staff to add copies'
                  : $availableCopies . ' of ' . $totalCopies . ' copies ready';
                $canReserve = $totalCopies > 0;

                $placeholderSeed = strtoupper(substr($titleLabel, 0, 1));
                if (!preg_match('/[A-Z0-9]/', $placeholderSeed)) {
                  $placeholderSeed = '#';
                }
                ?>
                <article class="catalog-item borrower-card">
                  <div class="catalog-item-cover">
                    <?php if ($coverUrl !== ''): ?>
                      <img src="<?php echo htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Cover of <?php echo htmlspecialchars($titleLabel, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                      <div class="catalog-cover-placeholder" aria-hidden="true"><?php echo htmlspecialchars($placeholderSeed, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="catalog-item-main">
                    <div class="catalog-item-tags">
                      <span class="borrower-chip"><?php echo htmlspecialchars($categoryLabel !== '' ? $categoryLabel : 'General', ENT_QUOTES, 'UTF-8'); ?></span>
                      <span class="borrower-chip"><?php echo htmlspecialchars($yearLabel !== '' ? $yearLabel : 'Year N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($titleLabel, ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="catalog-meta catalog-meta-primary"><?php echo htmlspecialchars($authorLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="catalog-description"><?php echo htmlspecialchars($bookDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="catalog-meta catalog-meta-secondary">
                      <?php if ($categoryLabel !== ''): ?>
                        <span>Category: <?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endif; ?>
                      <?php if ($yearLabel !== ''): ?>
                        <span>Year: <?php echo htmlspecialchars($yearLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                      <?php endif; ?>
                      <span>ISBN: <?php echo htmlspecialchars($isbnLabel !== '' ? $isbnLabel : 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                  </div>
                  <div class="catalog-actions">
                    <div class="availability<?php echo $availableCopies > 0 ? ' is-available' : ' is-unavailable'; ?>">
                      <strong><?php echo htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                      <span><?php echo htmlspecialchars($availabilityDetail, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <form method="POST" action="<?php echo htmlspecialchars(appPath('catalog.php', array_filter(['q' => $query, 'category' => $category], static function ($value) {
                                                  return $value !== '';
                                                })), ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reserveCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="book_id" value="<?php echo (int)($row['id'] ?? 0); ?>">
                      <button type="submit" class="borrower-btn borrower-btn-secondary" <?php echo $canReserve ? '' : 'disabled'; ?>>Reserve</button>
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

  <?php if ($receiptIsConfirmed): ?>
    <div
      id="borrower-reservation-receipt-modal"
      class="borrower-receipt-modal"
      role="presentation"
      aria-hidden="true">
      <div
        class="borrower-receipt-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="borrower-receipt-title"
        aria-describedby="borrower-receipt-summary"
        tabindex="-1">
        <button type="button" class="borrower-receipt-close" id="borrower-receipt-close" aria-label="Close receipt overlay">&times;</button>

        <section id="receipt" class="receipt" role="document">
          <header class="section center">
            <p class="brand">QueenLib</p>
            <p class="small muted">Main Library</p>
            <h2 id="borrower-receipt-title" class="title"><?php echo htmlspecialchars($receiptTypeLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
          </header>

          <div class="divider" aria-hidden="true"></div>

          <section class="section">
            <div class="row">
              <span class="small muted">Receipt No</span>
              <strong><?php echo htmlspecialchars($receiptNumber !== '' ? $receiptNumber : 'Pending', ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="row">
              <span class="small muted">Date</span>
              <strong><?php echo htmlspecialchars($receiptDateDisplay, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </section>

          <section class="section">
            <div class="row">
              <span class="small muted">Borrower Name</span>
              <strong><?php echo htmlspecialchars($receiptCustomerName, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </section>

          <section class="section">
            <div class="row">
              <span class="small muted">Book Title</span>
              <strong><?php echo htmlspecialchars((string)($firstReceiptItem['label'] ?? 'Reservation item'), ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <?php if ($receiptBookAuthor !== ''): ?>
              <div class="row">
                <span class="small muted">Author</span>
                <strong><?php echo htmlspecialchars($receiptBookAuthor, ENT_QUOTES, 'UTF-8'); ?></strong>
              </div>
            <?php endif; ?>
          </section>

          <section class="section" role="status" aria-live="polite">
            <div class="row">
              <span class="small muted"><?php echo htmlspecialchars($receiptHasBorrowField ? 'Due Date' : 'Pickup Until', ENT_QUOTES, 'UTF-8'); ?></span>
              <strong><?php echo htmlspecialchars($receiptDeadlineDisplay, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div class="row">
              <span class="small muted">Status</span>
              <strong class="status status-<?php echo htmlspecialchars($receiptStatusKey, ENT_QUOTES, 'UTF-8'); ?><?php echo $isReceiptExpiringSoon ? ' status-expiring' : ''; ?>"><?php echo htmlspecialchars($receiptStatusLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          </section>

          <div class="divider" aria-hidden="true"></div>
          <p id="borrower-receipt-summary" class="small muted">Present this receipt at the library circulation desk.</p>
          <p class="small muted">Thank you for using QueenLib.</p>
        </section>

        <footer class="borrower-receipt-actions">
          <button type="button" class="borrower-btn borrower-btn-secondary" id="borrower-receipt-close-action">Close</button>
          <button type="button" class="borrower-btn borrower-btn-primary" id="borrower-receipt-print">Print</button>
          <button type="button" class="borrower-btn borrower-btn-secondary" id="borrower-receipt-download">Download PDF</button>
          <form method="POST" action="<?php echo htmlspecialchars(appPath('catalog.php', array_filter(['q' => $query, 'category' => $category], static function ($value) {
                                        return $value !== '';
                                      })), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-receipt-cancel-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reserveCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="cancel_receipt_reservation">
            <input type="hidden" name="reservation_id" value="<?php echo (int)$receiptReservationId; ?>">
            <button type="submit" class="borrower-btn borrower-btn-danger" id="borrower-receipt-cancel">Cancel Reservation</button>
          </form>
        </footer>
      </div>
    </div>

    <script>
      window.borrowerReservationReceipt = <?php echo json_encode([
                                            'confirmed' => $receiptIsConfirmed,
                                            'reservationId' => $receiptReservationId,
                                            'receiptCode' => $receiptCode,
                                            'receiptNumber' => $receiptNumber,
                                            'receiptTypeLabel' => $receiptTypeLabel,
                                            'verificationCode' => $receiptVerificationCode,
                                            'queuePosition' => $receiptQueuePosition,
                                            'customerName' => $receiptCustomerName,
                                            'customerEmail' => $receiptCustomerEmail,
                                            'transactionDate' => $receiptDateDisplay,
                                            'generatedOn' => $receiptGeneratedDisplay,
                                            'pickupDeadline' => $pickupDeadlineDisplay,
                                            'deadlineLabel' => $receiptDeadlineLabel,
                                            'deadlineDisplay' => $receiptDeadlineDisplay,
                                            'pickupDeadlineIso' => $pickupDeadlineRaw,
                                            'reservationExpiry' => $reservationExpiryDisplay,
                                            'pickupLocation' => $receiptPickupLocation,
                                            'statusKey' => $receiptStatusKey,
                                            'statusLabel' => $receiptStatusLabel,
                                            'statusHeadline' => $receiptStatusHeadline,
                                            'feesLabel' => $receiptFeesLabel,
                                            'showFeeBreakdown' => $showFeeBreakdown,
                                            'bookIsbn' => $receiptBookIsbn,
                                            'bookCallNumber' => $receiptBookCallNumber,
                                            'bookCategory' => $receiptBookCategory,
                                            'bookCoverUrl' => $receiptBookCoverUrl,
                                            'policies' => $receiptPolicies,
                                            'items' => $receiptItems,
                                            'subtotal' => $receiptSubtotal,
                                            'taxesFees' => $receiptTaxesFees,
                                            'totalAmount' => $receiptTotalAmount,
                                            'currency' => $receiptCurrency,
                                            'paymentMethod' => $receiptPaymentMethod,
                                            'fileBase' => $receiptFileBase,
                                            'barcodeText' => $receiptBarcodeDisplay,
                                            'cancelAction' => 'cancel_receipt_reservation',
                                          ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script>
      (function() {
        'use strict';

        var receiptData = window.borrowerReservationReceipt || null;
        if (!receiptData || !receiptData.confirmed) {
          return;
        }

        var modal = document.getElementById('borrower-reservation-receipt-modal');
        var dialog = modal ? modal.querySelector('.borrower-receipt-dialog') : null;
        var closeButton = document.getElementById('borrower-receipt-close');
        var closeActionButton = document.getElementById('borrower-receipt-close-action');
        var printButton = document.getElementById('borrower-receipt-print');
        var downloadButton = document.getElementById('borrower-receipt-download');
        var lastFocusedElement = null;

        if (!modal || !dialog) {
          return;
        }

        function isMobileDevice() {
          if (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches) {
            return true;
          }

          var ua = window.navigator && window.navigator.userAgent ? window.navigator.userAgent : '';
          return /Android|iPhone|iPad|iPod|Mobile|Opera Mini|IEMobile/i.test(ua);
        }

        function getFocusableElements() {
          return Array.prototype.slice.call(
            dialog.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
          ).filter(function(element) {
            return !element.hasAttribute('disabled') && element.getAttribute('aria-hidden') !== 'true';
          });
        }

        function openModal() {
          lastFocusedElement = document.activeElement;
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('borrower-receipt-open');
          dialog.focus();
        }

        function closeModal() {
          modal.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('borrower-receipt-open');
          if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
          }
        }

        function formatMoney(value) {
          var amount = Number(value);
          if (!Number.isFinite(amount)) {
            amount = 0;
          }

          var currency = typeof receiptData.currency === 'string' && receiptData.currency ? receiptData.currency : 'PHP';
          return currency + ' ' + amount.toFixed(2);
        }

        function drawReceiptToCanvas() {
          var canvas = document.createElement('canvas');
          var context = canvas.getContext('2d');
          var width = 1240;

          var lines = [
            'QueenLib - Main Library',
            String(receiptData.receiptTypeLabel || 'Reservation Receipt'),
            '',
            'Receipt Number: ' + (receiptData.receiptNumber || 'Pending'),
            'Date & Time: ' + (receiptData.transactionDate || ''),
            '',
            'Name: ' + (receiptData.customerName || 'Borrower User'),
            ''
          ];

          var items = Array.isArray(receiptData.items) ? receiptData.items : [];
          var firstItem = items.length > 0 ? items[0] : null;
          var firstLabel = firstItem && firstItem.label ? String(firstItem.label) : 'Reservation item';
          var firstDetails = firstItem && firstItem.details ? String(firstItem.details) : '';
          if (firstDetails.toLowerCase().indexOf('author:') === 0) {
            firstDetails = firstDetails.slice(7).trim();
          }

          lines.push('Title: ' + firstLabel);
          if (firstDetails) {
            lines.push('Author: ' + firstDetails);
          }

          lines.push('');
          lines.push((receiptData.deadlineLabel || 'Pickup Deadline') + ': ' + (receiptData.deadlineDisplay || receiptData.pickupDeadline || 'Pending schedule'));
          lines.push('Status: ' + (receiptData.statusLabel || 'Pending'));
          lines.push('');
          lines.push('Present this receipt at the library circulation desk.');
          lines.push('Thank you for using QueenLib.');

          var paddingX = 48;
          var lineHeight = 42;
          var height = Math.max(820, (lines.length * lineHeight) + 130);

          canvas.width = width;
          canvas.height = height;

          context.fillStyle = '#ffffff';
          context.fillRect(0, 0, width, height);

          context.fillStyle = '#2b1c11';
          context.font = '700 48px "Outfit", Arial, sans-serif';
          context.fillText(String(receiptData.receiptTypeLabel || 'Reservation Receipt'), paddingX, 78);

          context.strokeStyle = '#d8c7b2';
          context.lineWidth = 2;
          context.beginPath();
          context.moveTo(paddingX, 96);
          context.lineTo(width - paddingX, 96);
          context.stroke();

          context.fillStyle = '#3e3025';
          context.font = '500 27px "Outfit", Arial, sans-serif';

          var y = 146;
          lines.forEach(function(line) {
            context.fillText(line, paddingX, y);
            y += lineHeight;
          });

          return canvas;
        }

        function downloadReceiptPdf(canvas, fileBase) {
          if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
            window.alert('PDF generator is unavailable right now. Please try again after the page fully loads.');
            return;
          }

          try {
            var pdf = new window.jspdf.jsPDF({
              orientation: 'portrait',
              unit: 'pt',
              format: 'a4'
            });

            var pageWidth = pdf.internal.pageSize.getWidth();
            var pageHeight = pdf.internal.pageSize.getHeight();
            var margin = 24;
            var imgData = canvas.toDataURL('image/png');
            var maxWidth = pageWidth - (margin * 2);
            var maxHeight = pageHeight - (margin * 2);
            var imageWidth = maxWidth;
            var imageHeight = canvas.height * (imageWidth / canvas.width);

            if (imageHeight > maxHeight) {
              imageHeight = maxHeight;
              imageWidth = canvas.width * (imageHeight / canvas.height);
            }

            pdf.addImage(imgData, 'PNG', margin, margin, imageWidth, imageHeight);
            pdf.save(fileBase + '.pdf');
          } catch (error) {
            window.alert('Unable to generate PDF right now. Please try again.');
          }
        }

        function handleDownloadPdf() {
          var canvas = drawReceiptToCanvas();
          var fileBase = typeof receiptData.fileBase === 'string' && receiptData.fileBase ? receiptData.fileBase : 'reservation-receipt';
          downloadReceiptPdf(canvas, fileBase.replace(/[^a-z0-9._-]/gi, '_'));
        }

        function queuePrintDialog() {
          window.requestAnimationFrame(function() {
            window.requestAnimationFrame(function() {
              window.print();
            });
          });
        }

        function printReceipt() {
          modal.setAttribute('aria-hidden', 'false');
          document.body.classList.add('borrower-print-active');
          queuePrintDialog();
        }

        downloadButton.textContent = 'Download PDF';

        closeButton.addEventListener('click', closeModal);
        closeActionButton.addEventListener('click', closeModal);
        printButton.addEventListener('click', printReceipt);
        downloadButton.addEventListener('click', handleDownloadPdf);

        window.addEventListener('beforeprint', function() {
          if (modal.getAttribute('aria-hidden') === 'false') {
            document.body.classList.add('borrower-print-active');
          }
        });

        window.addEventListener('afterprint', function() {
          document.body.classList.remove('borrower-print-active');
        });

        modal.addEventListener('click', function(event) {
          if (event.target === modal) {
            closeModal();
          }
        });

        document.addEventListener('keydown', function(event) {
          if (modal.getAttribute('aria-hidden') === 'true') {
            return;
          }

          if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
          }

          if ((event.ctrlKey || event.metaKey) && (event.key === 'p' || event.key === 'P')) {
            event.preventDefault();
            printReceipt();
            return;
          }

          if (event.key === 'Tab') {
            var focusable = getFocusableElements();
            if (focusable.length === 0) {
              event.preventDefault();
              return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
              event.preventDefault();
              last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
              event.preventDefault();
              first.focus();
            }
          }
        });

        openModal();
      })();
    </script>
  <?php endif; ?>
</body>

</html>