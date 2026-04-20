<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../classes/LibrarianPortalRepository.php';
require_once __DIR__ . '/../classes/PermissionGate.php';

apiHandleCorsAndMethod('GET');
apiEnsureSessionStarted();

$role = PermissionGate::resolveAdminRole();
if (!PermissionGate::meetsMinimumRole($role, 'librarian')) {
  http_response_code(403);
  echo json_encode([
    'success' => false,
    'error' => 'Forbidden',
  ], JSON_UNESCAPED_SLASHES);
  exit();
}

try {
  $db = apiGetDatabaseConnection();

  $type = strtolower(trim((string)($_GET['type'] ?? '')));
  $query = trim((string)($_GET['q'] ?? ''));
  $limit = (int)($_GET['limit'] ?? 20);

  if (!in_array($type, ['borrowers', 'books'], true)) {
    http_response_code(400);
    echo json_encode([
      'success' => false,
      'error' => 'Invalid lookup type.',
    ], JSON_UNESCAPED_SLASHES);
    exit();
  }

  if ($query === '' || strlen($query) < 2) {
    echo json_encode([
      'success' => true,
      'rows' => [],
    ], JSON_UNESCAPED_SLASHES);
    exit();
  }

  if ($type === 'borrowers') {
    $result = LibrarianPortalRepository::searchCheckoutBorrowers($db, $query, $limit);
    if (empty($result['available'])) {
      http_response_code(503);
      echo json_encode([
        'success' => false,
        'error' => (string)($result['message'] ?? 'Borrower lookup unavailable.'),
      ], JSON_UNESCAPED_SLASHES);
      exit();
    }

    $rows = [];
    foreach (($result['rows'] ?? []) as $borrower) {
      $id = (int)($borrower['id'] ?? 0);
      if ($id <= 0) {
        continue;
      }

      $displayName = trim((string)($borrower['display_name'] ?? ''));
      $email = trim((string)($borrower['email'] ?? ''));
      $label = $displayName !== '' ? $displayName : ('User #' . $id);
      if ($email !== '') {
        $label .= ' (' . $email . ')';
      }

      $rows[] = [
        'id' => $id,
        'label' => $label,
      ];
    }

    echo json_encode([
      'success' => true,
      'rows' => $rows,
    ], JSON_UNESCAPED_SLASHES);
    exit();
  }

  $result = LibrarianPortalRepository::searchCheckoutBooks($db, $query, $limit);
  if (empty($result['available'])) {
    http_response_code(503);
    echo json_encode([
      'success' => false,
      'error' => (string)($result['message'] ?? 'Book lookup unavailable.'),
    ], JSON_UNESCAPED_SLASHES);
    exit();
  }

  $rows = [];
  foreach (($result['rows'] ?? []) as $book) {
    $id = (int)($book['id'] ?? 0);
    if ($id <= 0) {
      continue;
    }

    $title = trim((string)($book['title'] ?? 'Unknown title'));
    $author = trim((string)($book['author'] ?? ''));
    $isbn = trim((string)($book['isbn'] ?? ''));
    $availableCopies = max(0, (int)($book['available_copies'] ?? 0));

    $label = $title;
    if ($author !== '') {
      $label .= ' - ' . $author;
    }
    if ($isbn !== '') {
      $label .= ' (ISBN ' . $isbn . ')';
    }
    $label .= ' [' . $availableCopies . ' available]';

    $rows[] = [
      'id' => $id,
      'label' => $label,
    ];
  }

  echo json_encode([
    'success' => true,
    'rows' => $rows,
  ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  error_log('librarian-checkout-search error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Lookup failed.',
  ], JSON_UNESCAPED_SLASHES);
}
