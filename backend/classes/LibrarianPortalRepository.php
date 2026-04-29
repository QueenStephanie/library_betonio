<?php

/**
 * LibrarianPortalRepository
 *
 * Lightweight, schema-tolerant read/write operations for librarian pages.
 */
class LibrarianPortalRepository
{
  const DEFAULT_LOAN_DAYS = 14;
  const FINE_RATE_PER_DAY = 10.0;

  /** @var array<string, bool> */
  private static $tableCache = [];

  /** @var array<string, array<string, bool>> */
  private static $columnCache = [];

  private static function loadReceiptRepository(): void
  {
    if (!class_exists('ReceiptRepository')) {
      require_once __DIR__ . '/ReceiptRepository.php';
    }
  }

  private static function buildReceiptPrintUrl(int $receiptId): string
  {
    return 'librarian-receipt.php?receipt_id=' . rawurlencode((string)$receiptId);
  }

  /**
   * @param array<string,mixed> $receiptPayload
   * @return array<string,mixed>
   */
  private static function createTransactionReceipt(PDO $db, string $transactionType, int $transactionRefId, int $borrowerUserId, int $actorUserId, array $receiptPayload): array
  {
    self::loadReceiptRepository();

    $receipt = ReceiptRepository::createForTransaction($db, [
      'transaction_type' => $transactionType,
      'transaction_ref_id' => $transactionRefId,
      'borrower_user_id' => $borrowerUserId > 0 ? $borrowerUserId : null,
      'actor_user_id' => $actorUserId > 0 ? $actorUserId : null,
      'payload' => $receiptPayload,
    ]);

    $receiptId = (int)($receipt['id'] ?? 0);
    if ($receiptId <= 0) {
      throw new RuntimeException('Receipt creation returned an invalid receipt ID.');
    }

    return [
      'receipt_id' => $receiptId,
      'receipt_code' => (string)($receipt['receipt_code'] ?? ''),
      'receipt_print_url' => self::buildReceiptPrintUrl($receiptId),
    ];
  }

  private static function tableExists(PDO $db, string $table): bool
  {
    if (isset(self::$tableCache[$table])) {
      return self::$tableCache[$table];
    }

    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);
    $exists = (int)$stmt->fetchColumn() > 0;
    self::$tableCache[$table] = $exists;

    return $exists;
  }

  /** @return array<string, bool> */
  private static function getColumnMap(PDO $db, string $table): array
  {
    if (isset(self::$columnCache[$table])) {
      return self::$columnCache[$table];
    }

    if (!self::tableExists($db, $table)) {
      self::$columnCache[$table] = [];
      return self::$columnCache[$table];
    }

    $stmt = $db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);

    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
      $columns[strtolower((string)$column)] = true;
    }

    self::$columnCache[$table] = $columns;
    return $columns;
  }

  private static function hasColumn(PDO $db, string $table, string $column): bool
  {
    $map = self::getColumnMap($db, $table);
    return isset($map[strtolower($column)]);
  }

  /**
   * Safely quote SQL identifier for safe interpolation
   */
  private static function quoteIdentifier(string $identifier): string
  {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
      throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
    }
    return '`' . str_replace('`', '``', $identifier) . '`';
  }

  private static function resolveColumn(PDO $db, string $table, array $candidates): ?string
  {
    foreach ($candidates as $candidate) {
      if (self::hasColumn($db, $table, $candidate)) {
        return $candidate;
      }
    }

    return null;
  }

  private static function normalizeIsbn(string $isbn): string
  {
    $normalized = preg_replace('/[^0-9Xx]/', '', $isbn);
    if (!is_string($normalized)) {
      return '';
    }

    return strtoupper($normalized);
  }

  private static function buildCopyBarcode(int $bookId): string
  {
    try {
      $token = bin2hex(random_bytes(4));
    } catch (Exception $e) {
      $token = substr(sha1($bookId . '|' . microtime(true)), 0, 8);
    }

    return 'BK' . $bookId . '-' . strtoupper($token);
  }

  private static function isValidIsbn(string $isbn): bool
  {
    $normalized = self::normalizeIsbn($isbn);

    if ($normalized === '') {
      return false;
    }

    if (strlen($normalized) === 10 && preg_match('/^[0-9]{9}[0-9X]$/', $normalized) === 1) {
      return true;
    }

    if (strlen($normalized) === 13 && preg_match('/^[0-9]{13}$/', $normalized) === 1) {
      return true;
    }

    return false;
  }

  /**
   * @param array<string,mixed> $facts
   * @return array{ok: bool, message: string, normalized?: array<string,mixed>}
   */
  public static function evaluateBookCreationRules(array $facts): array
  {
    $title = trim((string)($facts['title'] ?? ''));
    $author = trim((string)($facts['author'] ?? ''));
    $isbn = trim((string)($facts['isbn'] ?? ''));
    $publicationDate = trim((string)($facts['publication_date'] ?? ''));
    $genre = trim((string)($facts['genre'] ?? ''));

    if ($title === '') {
      return ['ok' => false, 'message' => 'Title is required.'];
    }

    if ($author === '') {
      return ['ok' => false, 'message' => 'Author is required.'];
    }

    if ($isbn === '') {
      return ['ok' => false, 'message' => 'ISBN is required.'];
    }

    if (!self::isValidIsbn($isbn)) {
      return ['ok' => false, 'message' => 'ISBN must use 10 or 13 digits (ISBN-10 may end with X).'];
    }

    if ($publicationDate === '') {
      return ['ok' => false, 'message' => 'Publication date is required.'];
    }

    $publishedAt = DateTimeImmutable::createFromFormat('Y-m-d', $publicationDate);
    $publishedAtErrors = DateTimeImmutable::getLastErrors();
    $hasDateErrors = is_array($publishedAtErrors)
      && (($publishedAtErrors['warning_count'] ?? 0) > 0 || ($publishedAtErrors['error_count'] ?? 0) > 0);

    if (!$publishedAt instanceof DateTimeImmutable || $hasDateErrors || $publishedAt->format('Y-m-d') !== $publicationDate) {
      return ['ok' => false, 'message' => 'Publication date must use YYYY-MM-DD format.'];
    }

    $today = new DateTimeImmutable('today');
    if ($publishedAt > $today) {
      return ['ok' => false, 'message' => 'Publication date cannot be in the future.'];
    }

    if ($genre === '') {
      return ['ok' => false, 'message' => 'Genre is required.'];
    }

    $titleLength = function_exists('mb_strlen') ? (int)mb_strlen($title) : strlen($title);
    $authorLength = function_exists('mb_strlen') ? (int)mb_strlen($author) : strlen($author);
    $genreLength = function_exists('mb_strlen') ? (int)mb_strlen($genre) : strlen($genre);

    if ($titleLength > 255) {
      return ['ok' => false, 'message' => 'Title must be 255 characters or fewer.'];
    }

    if ($authorLength > 255) {
      return ['ok' => false, 'message' => 'Author must be 255 characters or fewer.'];
    }

    if ($genreLength > 100) {
      return ['ok' => false, 'message' => 'Genre must be 100 characters or fewer.'];
    }

    return [
      'ok' => true,
      'message' => 'Book details are valid.',
      'normalized' => [
        'title' => $title,
        'author' => $author,
        'isbn' => $isbn,
        'isbn_normalized' => self::normalizeIsbn($isbn),
        'publication_date' => $publicationDate,
        'published_year' => (int)$publishedAt->format('Y'),
        'genre' => $genre,
      ],
    ];
  }


  /**
   * @param array<string,mixed> $input
   * @return array{ok: bool, message: string, book_id?: int}
   */
  public static function addBook(PDO $db, array $input): array
  {
    if (!self::tableExists($db, 'books')) {
      return ['ok' => false, 'message' => 'Books table is missing. Run circulation migration first.'];
    }

    $validation = self::evaluateBookCreationRules($input);
    if (!$validation['ok']) {
      return $validation;
    }

    $normalized = (array)($validation['normalized'] ?? []);
    $title = (string)($normalized['title'] ?? '');
    $author = (string)($normalized['author'] ?? '');
    $isbnNormalized = (string)($normalized['isbn_normalized'] ?? '');
    $publishedYear = (int)($normalized['published_year'] ?? 0);
    $genre = (string)($normalized['genre'] ?? '');

    if (!self::hasColumn($db, 'books', 'title') || !self::hasColumn($db, 'books', 'author')) {
      return ['ok' => false, 'message' => 'Books schema is incompatible with add-book requirements.'];
    }

    $hasIsbn = self::hasColumn($db, 'books', 'isbn');
    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    $hasPublicationDate = self::hasColumn($db, 'books', 'publication_date');
    $publishedYearColumn = self::resolveColumn($db, 'books', ['published_year', 'publication_year', 'publish_year']);
    $coverColumn = self::resolveColumn($db, 'books', ['cover_image_url', 'cover_url', 'image_url', 'thumbnail_url', 'cover_image', 'book_cover', 'book_image']);
    $hasIsActive = self::hasColumn($db, 'books', 'is_active');

    $initialCopies = (int)($input['initial_copies'] ?? 1);
    if ($initialCopies < 0) {
      $initialCopies = 0;
    } elseif ($initialCopies > 200) {
      $initialCopies = 200;
    }

    $canCreateCopies = $initialCopies > 0
      && self::tableExists($db, 'book_copies')
      && self::hasColumn($db, 'book_copies', 'book_id')
      && self::hasColumn($db, 'book_copies', 'barcode')
      && self::hasColumn($db, 'book_copies', 'status');

    $transactionStarted = false;
    try {
      if ($hasIsbn && $isbnNormalized !== '') {
        $existingIsbnStmt = $db->query("SELECT id, isbn FROM books WHERE COALESCE(isbn, '') <> ''");
        $existingIsbnRows = $existingIsbnStmt ? $existingIsbnStmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($existingIsbnRows as $existingIsbnRow) {
          $existingIsbn = self::normalizeIsbn((string)($existingIsbnRow['isbn'] ?? ''));
          if ($existingIsbn !== '' && hash_equals($existingIsbn, $isbnNormalized)) {
            return ['ok' => false, 'message' => 'Duplicate entry: ISBN already exists in the catalog.'];
          }
        }
      }

      $duplicateSql = 'SELECT id FROM books WHERE LOWER(TRIM(title)) = :title AND LOWER(TRIM(author)) = :author';
      $duplicateParams = [
        ':title' => strtolower($title),
        ':author' => strtolower($author),
      ];

      if ($publishedYearColumn !== null) {
        $duplicateSql .= ' AND COALESCE(`' . $publishedYearColumn . '`, 0) = :published_year';
        $duplicateParams[':published_year'] = $publishedYear;
      }

      $duplicateSql .= ' LIMIT 1';

      $duplicateStmt = $db->prepare($duplicateSql);
      $duplicateStmt->execute($duplicateParams);
      $duplicateByIdentityId = (int)$duplicateStmt->fetchColumn();
      if ($duplicateByIdentityId > 0) {
        return ['ok' => false, 'message' => 'Duplicate entry: the same title/author/publication year already exists.'];
      }

      if ($canCreateCopies) {
        $db->beginTransaction();
        $transactionStarted = true;
      }

      $columns = ['title', 'author'];
      $values = [':title', ':author'];
      $params = [
        ':title' => $title,
        ':author' => $author,
      ];

      if ($hasIsbn) {
        $columns[] = 'isbn';
        $values[] = ':isbn';
        $params[':isbn'] = $isbnNormalized;
      }

      if ($categoryColumn !== null) {
        $columns[] = $categoryColumn;
        $values[] = ':category';
        $params[':category'] = $genre;
      }

      if ($publishedYearColumn !== null) {
        $columns[] = $publishedYearColumn;
        $values[] = ':published_year';
        $params[':published_year'] = $publishedYear;
      }

      if ($hasPublicationDate) {
        $columns[] = 'publication_date';
        $values[] = ':publication_date';
        $params[':publication_date'] = (string)($normalized['publication_date'] ?? '');
      }

      if ($coverColumn !== null) {
        $columns[] = $coverColumn;
        $values[] = ':cover_image_url';
        $params[':cover_image_url'] = trim((string)($input['cover_image_url'] ?? ''));
      }

      if ($hasIsActive) {
        $columns[] = 'is_active';
        $values[] = ':is_active';
        $params[':is_active'] = 1;
      }

      $insertSql = 'INSERT INTO books (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
      $insertStmt = $db->prepare($insertSql);
      $insertStmt->execute($params);

      $bookId = (int)$db->lastInsertId();

      if ($canCreateCopies && $bookId > 0) {
        $copyInsertStmt = $db->prepare('INSERT INTO book_copies (book_id, barcode, status) VALUES (:book_id, :barcode, :status)');
        for ($i = 0; $i < $initialCopies; $i++) {
          $copyInsertStmt->execute([
            ':book_id' => $bookId,
            ':barcode' => self::buildCopyBarcode($bookId),
            ':status' => 'available',
          ]);
        }
      }

      if ($transactionStarted) {
        $db->commit();
        $transactionStarted = false;
      }
      return [
        'ok' => true,
        'message' => 'Book added successfully.',
        'book_id' => $bookId,
      ];
    } catch (PDOException $e) {
      if ($transactionStarted) {
        $db->rollBack();
      }
      $sqlState = (string)$e->getCode();
      if ($sqlState === '23000') {
        return ['ok' => false, 'message' => 'Duplicate entry detected while saving this book.'];
      }

      error_log('LibrarianPortalRepository::addBook error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to add book right now.'];
    } catch (Exception $e) {
      if ($transactionStarted) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::addBook error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to add book right now.'];
    }
  }

  /**
   * @param array<string,mixed> $facts
   * @return array{ok: bool, message: string}
   */
  public static function evaluateCheckoutRules(array $facts): array
  {
    $borrowerUserId = (int)($facts['borrower_user_id'] ?? 0);
    $bookId = (int)($facts['book_id'] ?? 0);
    $hasAvailableCopy = !empty($facts['has_available_copy']);

    if ($borrowerUserId <= 0) {
      return ['ok' => false, 'message' => 'Invalid borrower identifier.'];
    }

    if ($bookId <= 0) {
      return ['ok' => false, 'message' => 'Invalid book identifier.'];
    }

    if (!$hasAvailableCopy) {
      return ['ok' => false, 'message' => 'No available copy found for selected title.'];
    }

    return ['ok' => true, 'message' => 'Checkout can proceed.'];
  }

  /**
   * @param array<string,mixed> $facts
   * @return array{ok: bool, message: string}
   */
  public static function evaluateReadyReservationCheckoutRules(array $facts): array
  {
    $reservationId = (int)($facts['reservation_id'] ?? 0);
    $reservationExists = !empty($facts['reservation_exists']);
    $status = strtolower(trim((string)($facts['reservation_status'] ?? '')));
    $borrowerUserId = (int)($facts['borrower_user_id'] ?? 0);
    $bookId = (int)($facts['book_id'] ?? 0);
    $hasAvailableCopy = !empty($facts['has_available_copy']);

    if ($reservationId <= 0) {
      return ['ok' => false, 'message' => 'Invalid reservation identifier.'];
    }

    if (!$reservationExists) {
      return ['ok' => false, 'message' => 'Reservation record not found.'];
    }

    if (!in_array($status, ['ready'], true)) {
      return ['ok' => false, 'message' => 'Only ready reservations can be checked out.'];
    }

    if ($borrowerUserId <= 0 || $bookId <= 0) {
      return ['ok' => false, 'message' => 'Reservation is missing borrower or book details.'];
    }

    if (!$hasAvailableCopy) {
      return ['ok' => false, 'message' => 'No available copy found for this ready reservation.'];
    }

    return ['ok' => true, 'message' => 'Ready reservation checkout can proceed.'];
  }

  /**
   * @return array{stats: array<string,int|float>, missing_tables: list<string>, data_available: bool}
   */
  public static function getDashboardSummary(PDO $db): array
  {
    $summary = [
      'stats' => [
        'catalog_titles' => 0,
        'available_copies' => 0,
        'active_loans' => 0,
        'overdue_loans' => 0,
        'pending_reservations' => 0,
        'ready_reservations' => 0,
      ],
      'missing_tables' => [],
      'data_available' => true,
    ];

    $requiredTables = ['books', 'book_copies', 'loans', 'reservations'];
    foreach ($requiredTables as $table) {
      if (!self::tableExists($db, $table)) {
        $summary['missing_tables'][] = $table;
      }
    }

    if (!empty($summary['missing_tables'])) {
      $summary['data_available'] = false;
    }

    if (self::tableExists($db, 'books')) {
      $booksWhere = self::hasColumn($db, 'books', 'is_active') ? ' WHERE is_active = 1' : '';
      $summary['stats']['catalog_titles'] = (int)$db->query('SELECT COUNT(*) FROM books' . $booksWhere)->fetchColumn();
    }

    if (self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'status')) {
      $summary['stats']['available_copies'] = (int)$db->query("SELECT COUNT(*) FROM book_copies WHERE status = 'available'")->fetchColumn();
    }

    if (self::tableExists($db, 'loans')) {
      $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
      $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
      if ($loanStatusColumn !== null) {
        $col = "`$loanStatusColumn`";
        $summary['stats']['active_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$col} IN ('active', 'overdue', 'borrowed', 'checked_out')")->fetchColumn();
      }
      if ($loanStatusColumn !== null && $loanDueColumn !== null) {
        $statusCol = "`$loanStatusColumn`";
        $dueCol = "`$loanDueColumn`";
        $summary['stats']['overdue_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$statusCol} IN ('active', 'overdue', 'borrowed') AND {$dueCol} < NOW()")->fetchColumn();
      }
    }

    if (self::tableExists($db, 'reservations') && self::hasColumn($db, 'reservations', 'status')) {
      $summary['stats']['pending_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
      $summary['stats']['ready_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('ready')")->fetchColumn();
    }

    return $summary;
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function getCirculationRows(PDO $db, int $limit = 200): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'loans')) {
      $response['available'] = false;
      $response['message'] = 'Loans table is missing. Run circulation migration to enable this view.';
      return $response;
    }

    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
    $loanBookColumn = self::resolveColumn($db, 'loans', ['book_id']);
    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);

    if ($loanStatusColumn === null || $loanDueColumn === null) {
      $response['available'] = false;
      $response['message'] = 'Loans table schema is incompatible with circulation features.';
      return $response;
    }

    $hasBookCopies = self::tableExists($db, 'book_copies') && $loanCopyColumn !== null;
    $hasBooks = self::tableExists($db, 'books');
    $hasUsers = self::tableExists($db, 'users') && $loanUserColumn !== null;

    $limit = max(1, min(500, $limit));

    // Quote column identifiers with backticks for safety
    $statusCol = "`$loanStatusColumn`";
    $dueCol = "`$loanDueColumn`";
    $copyCol = $loanCopyColumn !== null ? "`$loanCopyColumn`" : '';
    $userCol = $loanUserColumn !== null ? "`$loanUserColumn`" : '';

    $copyJoin = $hasBookCopies && $loanCopyColumn !== null ? ' LEFT JOIN book_copies bc ON bc.id = l.' . $copyCol : '';
    $bookJoin = '';
    if ($hasBooks) {
      if ($loanBookColumn !== null) {
        $bookJoin = ' LEFT JOIN books b ON b.id = l.`' . $loanBookColumn . '`';
      } elseif ($hasBookCopies && self::hasColumn($db, 'book_copies', 'book_id')) {
        $bookJoin = ' LEFT JOIN books b ON b.id = bc.book_id';
      }
    }
    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = l.' . $userCol : '';

    $loanCheckedOutColumn = self::resolveColumn($db, 'loans', ['checked_out_at', 'checkout_date', 'borrowed_at']);
    $loanReturnedAtColumn = self::resolveColumn($db, 'loans', ['returned_at', 'return_date', 'returned_date']);
    $loanFineColumn = self::resolveColumn($db, 'loans', ['fine_amount', 'fine']);
    $loanRenewalCountColumn = self::resolveColumn($db, 'loans', ['renewal_count', 'renewed']);
    $checkedOutExpr = $loanCheckedOutColumn !== null ? 'l.`' . $loanCheckedOutColumn . '`' : 'NULL';
    $returnedAtExpr = $loanReturnedAtColumn !== null ? 'l.`' . $loanReturnedAtColumn . '`' : 'NULL';
    $fineAmountExpr = $loanFineColumn !== null ? 'l.`' . $loanFineColumn . '`' : '0';
    $renewalCountExpr = $loanRenewalCountColumn !== null ? 'l.`' . $loanRenewalCountColumn . '`' : '0';

    $sql = "SELECT
      l.id,
      l.{$statusCol} AS loan_status,
      {$checkedOutExpr} AS checked_out_at,
      l.{$dueCol} AS due_at,
      {$returnedAtExpr} AS returned_at,
      {$fineAmountExpr} AS fine_amount,
      {$renewalCountExpr} AS renewal_count,
      " . ($hasBookCopies && self::hasColumn($db, 'book_copies', 'barcode') ? 'bc.barcode' : "''") . " AS barcode,
      " . ($hasBooks ? 'b.title' : "''") . " AS title,
      " . ($hasBooks ? 'b.author' : "''") . " AS author,
      " . ($hasUsers ? 'u.first_name' : "''") . " AS borrower_first_name,
      " . ($hasUsers ? 'u.last_name' : "''") . " AS borrower_last_name,
      " . ($hasUsers ? 'u.email' : "''") . " AS borrower_email
    FROM loans l
    {$copyJoin}
    {$bookJoin}
    {$userJoin}
    WHERE l.{$statusCol} IN ('active', 'overdue', 'borrowed')
    ORDER BY l.{$dueCol} ASC, l.id ASC
    LIMIT {$limit}";

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
      $dueAt = (string)($row['due_at'] ?? '');
      $status = strtolower(trim((string)($row['loan_status'] ?? '')));
      $isOverdue = $dueAt !== '' && strtotime($dueAt) !== false && strtotime($dueAt) < time() && $status !== 'returned';
      $renewalCount = max(0, (int)($row['renewal_count'] ?? 0));
      $row['is_overdue'] = $isOverdue;
      $row['can_checkin'] = in_array($status, ['active', 'overdue', 'borrowed'], true);
      $row['can_renew'] = in_array($status, ['active', 'borrowed'], true) && $renewalCount < 2;
      $row['renewals_remaining'] = max(0, 2 - $renewalCount);
    }
    unset($row);

    $response['rows'] = $rows;
    return $response;
  }

  /**
   * @return array{ok: bool, message: string, receipt_id?: int, receipt_code?: string, receipt_print_url?: string}
   */
  public static function checkInLoan(PDO $db, int $loanId, int $actorUserId): array
  {
    if ($loanId <= 0) {
      return ['ok' => false, 'message' => 'Invalid loan identifier.'];
    }

    if (!self::tableExists($db, 'loans')) {
      return ['ok' => false, 'message' => 'Check-in is unavailable because loans table is missing.'];
    }

    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
    if ($loanStatusColumn === null) {
      return ['ok' => false, 'message' => 'Check-in is unavailable due to incompatible loans schema.'];
    }

    try {
      $db->beginTransaction();

      $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
      $loanFineColumn = self::resolveColumn($db, 'loans', ['fine_amount', 'fine']);

      $loanQuery = 'SELECT id, `' . $loanStatusColumn . '` AS loan_status';
      if ($loanCopyColumn !== null) {
        $loanQuery .= ', `' . $loanCopyColumn . '` AS book_copy_id';
      }
      if ($loanDueColumn !== null) {
        $loanQuery .= ', `' . $loanDueColumn . '` AS due_at';
      }
      $loanReturnedColumn = self::resolveColumn($db, 'loans', ['returned_at', 'return_date']);
      if ($loanReturnedColumn !== null) {
        $loanQuery .= ', `' . $loanReturnedColumn . '` AS returned_at';
      }
      $loanQuery .= ' FROM loans WHERE id = :id FOR UPDATE';

      $loanStmt = $db->prepare($loanQuery);
      $loanStmt->execute([':id' => $loanId]);
      $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($loan)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Loan record not found.'];
      }

      $currentStatus = strtolower(trim((string)($loan['loan_status'] ?? '')));
      if (!in_array($currentStatus, ['active', 'overdue', 'borrowed'], true)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Loan is not in a check-in state.'];
      }

      // Auto-fine calculation: if due_at is past, calculate fine
      $calculatedFine = 0.0;
      if ($loanDueColumn !== null && isset($loan['due_at'])) {
        $dueTimestamp = strtotime((string)$loan['due_at']);
        if ($dueTimestamp !== false && $dueTimestamp < time()) {
          $overdueDays = (int)ceil((time() - $dueTimestamp) / 86400);
          $calculatedFine = max(0, $overdueDays) * self::FINE_RATE_PER_DAY;
        }
      }

      $updateParts = ["`$loanStatusColumn` = :next_status"];
      $params = [
        ':next_status' => 'returned',
        ':id' => $loanId,
      ];
      if ($loanFineColumn !== null && $calculatedFine > 0) {
        $updateParts[] = '`' . $loanFineColumn . '` = COALESCE(`' . $loanFineColumn . '`, 0) + :fine_amount';
        $params[':fine_amount'] = $calculatedFine;
      }

      $loanReturnedColumn = self::resolveColumn($db, 'loans', ['returned_at', 'return_date']);
      if ($loanReturnedColumn !== null) {
        $updateParts[] = '`' . $loanReturnedColumn . '` = NOW()';
      }

      $updateSql = 'UPDATE loans SET ' . implode(', ', $updateParts) . ' WHERE id = :id';
      $updateStmt = $db->prepare($updateSql);
      $updateStmt->execute($params);

      $copyId = isset($loan['book_copy_id']) ? (int)$loan['book_copy_id'] : 0;
      if (
        $copyId > 0 &&
        self::tableExists($db, 'book_copies') &&
        self::hasColumn($db, 'book_copies', 'status')
      ) {
        $copyStmt = $db->prepare('UPDATE book_copies SET status = :status WHERE id = :id');
        $copyStmt->execute([
          ':status' => 'available',
          ':id' => $copyId,
        ]);
      }

      if (
        self::tableExists($db, 'loan_events') &&
        self::hasColumn($db, 'loan_events', 'loan_id') &&
        self::hasColumn($db, 'loan_events', 'event_type')
      ) {
        $eventColumns = ['loan_id', 'event_type'];
        $eventValues = [':loan_id', ':event_type'];
        $eventParams = [
          ':loan_id' => $loanId,
          ':event_type' => 'return',
        ];

        if (self::hasColumn($db, 'loan_events', 'actor_user_id')) {
          $eventColumns[] = 'actor_user_id';
          $eventValues[] = ':actor_user_id';
          $eventParams[':actor_user_id'] = $actorUserId > 0 ? $actorUserId : null;
        }

        if (self::hasColumn($db, 'loan_events', 'notes')) {
          $eventColumns[] = 'notes';
          $eventValues[] = ':notes';
          $eventParams[':notes'] = 'Checked in via librarian circulation page';
        }

        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      $receiptMeta = self::createTransactionReceipt(
        $db,
        'checkin',
        $loanId,
        0,
        $actorUserId,
        [
          'loan_id' => $loanId,
          'book_copy_id' => $copyId > 0 ? $copyId : null,
          'action' => 'checkin',
          'status_before' => $currentStatus,
          'status_after' => 'returned',
          'generated_by' => 'LibrarianPortalRepository::checkInLoan',
        ]
      );

      $db->commit();
      return [
        'ok' => true,
        'message' => 'Loan checked in successfully.',
        'receipt_id' => $receiptMeta['receipt_id'],
        'receipt_code' => $receiptMeta['receipt_code'],
        'receipt_print_url' => $receiptMeta['receipt_print_url'],
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::checkInLoan error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to check in loan right now.'];
    }
  }

  /**
   * @return array{ok: bool, message: string, loan_id?: int, renewal_count?: int, due_at?: string, receipt_id?: int, receipt_code?: string, receipt_print_url?: string}
   */
  public static function renewLoan(PDO $db, int $loanId, int $actorUserId, int $extensionDays = 7, int $maxRenewals = 2): array
  {
    if ($loanId <= 0) {
      return ['ok' => false, 'message' => 'Invalid loan identifier.'];
    }

    if (!self::tableExists($db, 'loans')) {
      return ['ok' => false, 'message' => 'Renewal is unavailable because loans table is missing.'];
    }

    $loanIdColumn = self::resolveColumn($db, 'loans', ['id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanRenewalCountColumn = self::resolveColumn($db, 'loans', ['renewal_count', 'renewed']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);

    if ($loanIdColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
      return ['ok' => false, 'message' => 'Renewal is unavailable due to incompatible loans schema.'];
    }

    if ($loanRenewalCountColumn === null) {
      return ['ok' => false, 'message' => 'Renewal is unavailable because renewal tracking is not configured.'];
    }

    $extensionDays = max(1, min(30, $extensionDays));
    $maxRenewals = max(1, min(10, $maxRenewals));

    try {
      $db->beginTransaction();

      $selectColumns = [
        'l.`' . $loanIdColumn . '` AS id',
        'l.`' . $loanStatusColumn . '` AS loan_status',
        'l.`' . $loanDueColumn . '` AS due_at',
        'l.`' . $loanRenewalCountColumn . '` AS renewal_count',
      ];
      if ($loanCopyColumn !== null) {
        $selectColumns[] = 'l.`' . $loanCopyColumn . '` AS book_copy_id';
      }

      $loanStmt = $db->prepare(
        'SELECT ' . implode(', ', $selectColumns) . ' FROM loans l WHERE l.`' . $loanIdColumn . '` = :loan_id LIMIT 1 FOR UPDATE'
      );
      $loanStmt->execute([':loan_id' => $loanId]);
      $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($loan)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Loan record not found.'];
      }

      $loanStatus = strtolower(trim((string)($loan['loan_status'] ?? '')));
      $renewalCount = max(0, (int)($loan['renewal_count'] ?? 0));

      if (!in_array($loanStatus, ['active', 'borrowed'], true)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Only active loans can be renewed.'];
      }

      if ($renewalCount >= $maxRenewals) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Maximum renewals (' . $maxRenewals . ') reached for this loan.'];
      }

      // Check if another reservation is waiting for this title
      $bookId = 0;
      if (
        isset($loan['book_copy_id']) && (int)$loan['book_copy_id'] > 0
        && self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id')
      ) {
        $copyStmt = $db->prepare('SELECT book_id FROM book_copies WHERE id = :copy_id LIMIT 1 FOR UPDATE');
        $copyStmt->execute([':copy_id' => (int)$loan['book_copy_id']]);
        $bookId = (int)$copyStmt->fetchColumn();
      }

      if (
        $bookId > 0 && self::tableExists($db, 'reservations') && self::hasColumn($db, 'reservations', 'book_id')
        && self::hasColumn($db, 'reservations', 'status')
      ) {
        $queueStmt = $db->prepare("SELECT COUNT(*) FROM reservations WHERE book_id = :book_id AND status IN ('pending', 'ready') FOR UPDATE");
        $queueStmt->execute([':book_id' => $bookId]);
        $hasActiveQueue = (int)$queueStmt->fetchColumn() > 0;
        if ($hasActiveQueue) {
          $db->rollBack();
          return ['ok' => false, 'message' => 'Cannot renew: another reservation is waiting for this title.'];
        }
      }

      $dueAt = trim((string)($loan['due_at'] ?? ''));
      $dueTimestamp = strtotime($dueAt);
      if ($dueTimestamp === false) {
        $dueTimestamp = time();
      }
      $baseTimestamp = max(time(), $dueTimestamp);
      $newDueAt = date('Y-m-d H:i:s', strtotime('+' . $extensionDays . ' days', $baseTimestamp));

      $updateSql = 'UPDATE loans
        SET `' . $loanDueColumn . '` = :new_due_at,
            `' . $loanRenewalCountColumn . '` = :new_renewal_count,
            `' . $loanStatusColumn . '` = :new_status
        WHERE `' . $loanIdColumn . '` = :loan_id';
      $updateStmt = $db->prepare($updateSql);
      $updateStmt->execute([
        ':new_due_at' => $newDueAt,
        ':new_renewal_count' => $renewalCount + 1,
        ':new_status' => 'active',
        ':loan_id' => $loanId,
      ]);

      // Log loan event
      if (
        self::tableExists($db, 'loan_events')
        && self::hasColumn($db, 'loan_events', 'loan_id')
        && self::hasColumn($db, 'loan_events', 'event_type')
      ) {
        $eventColumns = ['loan_id', 'event_type'];
        $eventValues = [':loan_id', ':event_type'];
        $eventParams = [
          ':loan_id' => $loanId,
          ':event_type' => 'renewal',
        ];
        if (self::hasColumn($db, 'loan_events', 'actor_user_id')) {
          $eventColumns[] = 'actor_user_id';
          $eventValues[] = ':actor_user_id';
          $eventParams[':actor_user_id'] = $actorUserId > 0 ? $actorUserId : null;
        }
        if (self::hasColumn($db, 'loan_events', 'notes')) {
          $eventColumns[] = 'notes';
          $eventValues[] = ':notes';
          $eventParams[':notes'] = 'Renewed by librarian via circulation page';
        }
        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      // Create receipt
      $receiptMeta = self::createTransactionReceipt(
        $db,
        'renewal',
        $loanId,
        0,
        $actorUserId,
        [
          'loan_id' => $loanId,
          'renewal_count' => $renewalCount + 1,
          'due_at' => $newDueAt,
          'extension_days' => $extensionDays,
          'generated_by' => 'LibrarianPortalRepository::renewLoan',
        ]
      );

      $db->commit();
      return [
        'ok' => true,
        'message' => 'Loan renewed successfully.',
        'loan_id' => $loanId,
        'renewal_count' => $renewalCount + 1,
        'due_at' => $newDueAt,
        'receipt_id' => $receiptMeta['receipt_id'],
        'receipt_code' => $receiptMeta['receipt_code'],
        'receipt_print_url' => $receiptMeta['receipt_print_url'],
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::renewLoan error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to renew loan right now.'];
    }
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function getCheckoutCandidates(PDO $db, int $borrowerLimit = 200, int $bookLimit = 200): array
  {
    $response = [
      'rows' => [
        'borrowers' => [],
        'books' => [],
      ],
      'available' => true,
      'message' => '',
    ];

    $borrowerLimit = max(1, min(500, $borrowerLimit));
    $bookLimit = max(1, min(500, $bookLimit));

    if (self::tableExists($db, 'users') && self::hasColumn($db, 'users', 'id')) {
      $nameParts = [];
      if (self::hasColumn($db, 'users', 'first_name')) {
        $nameParts[] = 'u.first_name';
      }
      if (self::hasColumn($db, 'users', 'last_name')) {
        $nameParts[] = 'u.last_name';
      }

      $nameExpr = empty($nameParts)
        ? "''"
        : 'TRIM(CONCAT(' . implode(", ' ', ", $nameParts) . '))';
      $emailExpr = self::hasColumn($db, 'users', 'email') ? 'u.email' : "''";

      $where = ['1=1'];
      if (self::hasColumn($db, 'users', 'is_active')) {
        $where[] = 'u.is_active = 1';
      }

      if (self::hasColumn($db, 'users', 'role')) {
        $where[] = "LOWER(COALESCE(u.role, 'borrower')) = 'borrower'";
      }

      $borrowerSql = 'SELECT
        u.id,
        ' . $nameExpr . ' AS display_name,
        ' . $emailExpr . ' AS email
        FROM users u
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY u.id DESC
        LIMIT ' . $borrowerLimit;

      $response['rows']['borrowers'] = $db->query($borrowerSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if (self::tableExists($db, 'books') && self::hasColumn($db, 'books', 'id') && self::hasColumn($db, 'books', 'title')) {
      $hasCopies = self::tableExists($db, 'book_copies')
        && self::hasColumn($db, 'book_copies', 'book_id')
        && self::hasColumn($db, 'book_copies', 'status');

      if ($hasCopies) {
        $authorExpr = self::hasColumn($db, 'books', 'author') ? 'b.author' : "''";
        $bookSql = 'SELECT
          b.id,
          b.title,
          ' . $authorExpr . ' AS author,
          SUM(CASE WHEN bc.status = \'available\' THEN 1 ELSE 0 END) AS available_copies
          FROM books b
          LEFT JOIN book_copies bc ON bc.book_id = b.id';

        if (self::hasColumn($db, 'books', 'is_active')) {
          $bookSql .= ' WHERE b.is_active = 1';
        }

        $bookSql .= ' GROUP BY b.id, b.title, ' . $authorExpr . '
          HAVING available_copies > 0
          ORDER BY b.title ASC
          LIMIT ' . $bookLimit;

        $response['rows']['books'] = $db->query($bookSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
      }
    }

    return $response;
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function searchCheckoutBorrowers(PDO $db, string $term = '', int $limit = 20): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'users') || !self::hasColumn($db, 'users', 'id')) {
      $response['available'] = false;
      $response['message'] = 'Users table is missing. Borrower lookup is unavailable.';
      return $response;
    }

    $limit = max(1, min(50, $limit));
    $term = trim($term);

    $nameParts = [];
    if (self::hasColumn($db, 'users', 'first_name')) {
      $nameParts[] = 'u.first_name';
    }
    if (self::hasColumn($db, 'users', 'last_name')) {
      $nameParts[] = 'u.last_name';
    }

    $nameExpr = empty($nameParts)
      ? "''"
      : 'TRIM(CONCAT(' . implode(", ' ', ", $nameParts) . '))';
    $emailExpr = self::hasColumn($db, 'users', 'email') ? 'u.email' : "''";

    $where = ['1=1'];
    $params = [];

    if (self::hasColumn($db, 'users', 'is_active')) {
      $where[] = 'u.is_active = 1';
    }

    if (self::hasColumn($db, 'users', 'role')) {
      $where[] = "LOWER(COALESCE(u.role, 'borrower')) = 'borrower'";
    }

    if ($term !== '') {
      $where[] = '(
        ' . $nameExpr . ' LIKE :term0
        OR ' . $emailExpr . ' LIKE :term1
        OR CAST(u.id AS CHAR) LIKE :term2
      )';
      $params[':term0'] = '%' . $term . '%';
      $params[':term1'] = '%' . $term . '%';
      $params[':term2'] = '%' . $term . '%';
    }

    $sql = 'SELECT
      u.id,
      ' . $nameExpr . ' AS display_name,
      ' . $emailExpr . ' AS email
      FROM users u
      WHERE ' . implode(' AND ', $where) . '
      ORDER BY u.id DESC
      LIMIT ' . $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $response['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return $response;
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function searchCheckoutBooks(PDO $db, string $term = '', int $limit = 20): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'books') || !self::hasColumn($db, 'books', 'id') || !self::hasColumn($db, 'books', 'title')) {
      $response['available'] = false;
      $response['message'] = 'Books table is missing. Book lookup is unavailable.';
      return $response;
    }

    if (
      !self::tableExists($db, 'book_copies')
      || !self::hasColumn($db, 'book_copies', 'book_id')
      || !self::hasColumn($db, 'book_copies', 'status')
    ) {
      $response['available'] = false;
      $response['message'] = 'Book copies table is missing. Availability lookup is unavailable.';
      return $response;
    }

    $term = trim($term);
    $limit = max(1, min(50, $limit));

    $authorExpr = self::hasColumn($db, 'books', 'author') ? 'b.author' : "''";
    $isbnExpr = self::hasColumn($db, 'books', 'isbn') ? 'b.isbn' : "''";
    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    $categoryExpr = $categoryColumn !== null ? 'b.`' . $categoryColumn . '`' : "''";

    $where = [];
    $params = [];

    if (self::hasColumn($db, 'books', 'is_active')) {
      $where[] = 'b.is_active = 1';
    }

    if ($term !== '') {
      $where[] = '(
        b.title LIKE :term0
        OR ' . $authorExpr . ' LIKE :term1
        OR ' . $isbnExpr . ' LIKE :term2
        OR ' . $categoryExpr . ' LIKE :term3
        OR CAST(b.id AS CHAR) LIKE :term4
      )';
      $params[':term0'] = '%' . $term . '%';
      $params[':term1'] = '%' . $term . '%';
      $params[':term2'] = '%' . $term . '%';
      $params[':term3'] = '%' . $term . '%';
      $params[':term4'] = '%' . $term . '%';
    }

    $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

    $sql = 'SELECT
      b.id,
      b.title,
      ' . $authorExpr . ' AS author,
      ' . $isbnExpr . ' AS isbn,
      SUM(CASE WHEN bc.status = \'available\' THEN 1 ELSE 0 END) AS available_copies
      FROM books b
      LEFT JOIN book_copies bc ON bc.book_id = b.id
      ' . $whereSql . '
      GROUP BY b.id, b.title, ' . $authorExpr . ', ' . $isbnExpr . '
      HAVING available_copies > 0
      ORDER BY
        CASE WHEN b.title LIKE :prefix THEN 0 ELSE 1 END,
        b.title ASC,
        b.id ASC
      LIMIT ' . $limit;

    $params[':prefix'] = $term === '' ? '%' : $term . '%';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $response['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return $response;
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function getReadyReservationCheckoutRows(PDO $db, int $limit = 150): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'reservations')) {
      $response['available'] = false;
      $response['message'] = 'Reservations table is missing.';
      return $response;
    }

    $reservationIdColumn = self::resolveColumn($db, 'reservations', ['id']);
    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    $reservationStatusColumn = self::resolveColumn($db, 'reservations', ['status']);
    $reservationQueuedColumn = self::resolveColumn($db, 'reservations', ['queued_at', 'reserved_at', 'created_at']);
    $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);

    if ($reservationIdColumn === null || $reservationUserColumn === null || $reservationBookColumn === null || $reservationStatusColumn === null) {
      $response['available'] = false;
      $response['message'] = 'Reservations schema is incompatible with checkout bridge.';
      return $response;
    }

    $limit = max(1, min(300, $limit));
    $hasUsers = self::tableExists($db, 'users');
    $hasBooks = self::tableExists($db, 'books');
    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id') && self::hasColumn($db, 'book_copies', 'status');

    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = r.`' . $reservationUserColumn . '`' : '';
    $bookJoin = $hasBooks ? ' LEFT JOIN books b ON b.id = r.`' . $reservationBookColumn . '`' : '';

    $queuedExpr = $reservationQueuedColumn !== null ? 'r.`' . $reservationQueuedColumn . '`' : 'NULL';
    $readyUntilExpr = $reservationReadyUntilColumn !== null ? 'r.`' . $reservationReadyUntilColumn . '`' : 'NULL';
    $orderByExpr = $reservationQueuedColumn !== null ? $queuedExpr : 'r.`' . $reservationIdColumn . '`';

    $availableCopiesExpr = '0';
    if ($hasCopies) {
      $availableCopiesExpr = '(SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = r.`' . $reservationBookColumn . '` AND bc.status = \'available\')';
    }

    $sql = "SELECT
      r.`{$reservationIdColumn}` AS id,
      r.`{$reservationUserColumn}` AS user_id,
      r.`{$reservationBookColumn}` AS book_id,
      r.`{$reservationStatusColumn}` AS status,
      {$queuedExpr} AS queued_at,
      {$readyUntilExpr} AS ready_until,
      {$availableCopiesExpr} AS available_copies,
      " . ($hasUsers ? 'u.first_name' : "''") . " AS borrower_first_name,
      " . ($hasUsers ? 'u.last_name' : "''") . " AS borrower_last_name,
      " . ($hasUsers ? 'u.email' : "''") . " AS borrower_email,
      " . ($hasBooks ? 'b.title' : "''") . " AS book_title,
      " . ($hasBooks ? 'b.author' : "''") . " AS book_author
      FROM reservations r
      {$userJoin}
      {$bookJoin}
      WHERE r.`{$reservationStatusColumn}` IN ('ready')
      ORDER BY {$orderByExpr} ASC, r.`{$reservationIdColumn}` ASC
      LIMIT {$limit}";

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) {
      $row['can_checkout'] = ((int)($row['available_copies'] ?? 0)) > 0;
    }
    unset($row);

    $response['rows'] = $rows;
    return $response;
  }

  /**
   * @return array{ok: bool, message: string, loan_id?: int, copy_id?: int, receipt_id?: int, receipt_code?: string, receipt_print_url?: string}
   */
  public static function checkoutLoan(PDO $db, int $borrowerUserId, int $bookId, int $actorUserId, ?int $reservationId = null, int $loanDays = self::DEFAULT_LOAN_DAYS): array
  {
    $preCheck = self::evaluateCheckoutRules([
      'borrower_user_id' => $borrowerUserId,
      'book_id' => $bookId,
      'has_available_copy' => true,
    ]);
    if (!$preCheck['ok']) {
      return $preCheck;
    }

    if (!self::tableExists($db, 'loans') || !self::tableExists($db, 'book_copies')) {
      return ['ok' => false, 'message' => 'Checkout is unavailable because circulation tables are missing.'];
    }

    if (!self::hasColumn($db, 'book_copies', 'book_id') || !self::hasColumn($db, 'book_copies', 'status')) {
      return ['ok' => false, 'message' => 'Checkout is unavailable due to incompatible copies schema.'];
    }

    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
    $loanBookColumn = self::resolveColumn($db, 'loans', ['book_id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    if ($loanUserColumn === null || ($loanCopyColumn === null && $loanBookColumn === null) || $loanStatusColumn === null || $loanDueColumn === null) {
      return ['ok' => false, 'message' => 'Checkout is unavailable due to incompatible loans schema.'];
    }

    $loanDays = max(1, min(60, $loanDays));

    try {
      $db->beginTransaction();

      $copyStmt = $db->prepare('SELECT id FROM book_copies WHERE book_id = :book_id AND status = :status ORDER BY id ASC LIMIT 1 FOR UPDATE');
      $copyStmt->execute([
        ':book_id' => $bookId,
        ':status' => 'available',
      ]);
      $copyId = (int)$copyStmt->fetchColumn();

      $runtimeRule = self::evaluateCheckoutRules([
        'borrower_user_id' => $borrowerUserId,
        'book_id' => $bookId,
        'has_available_copy' => $copyId > 0,
      ]);
      if (!$runtimeRule['ok']) {
        $db->rollBack();
        return $runtimeRule;
      }

      $copyUpdateStmt = $db->prepare('UPDATE book_copies SET status = :status WHERE id = :id');
      $copyUpdateStmt->execute([
        ':status' => 'loaned',
        ':id' => $copyId,
      ]);

      $insertColumns = ['`' . $loanUserColumn . '`', '`' . $loanStatusColumn . '`', '`' . $loanDueColumn . '`'];
      $insertValues = [':user_id', ':loan_status', ':due_at'];
      $insertParams = [
        ':user_id' => $borrowerUserId,
        ':loan_status' => 'active',
        ':due_at' => date('Y-m-d H:i:s', strtotime('+' . $loanDays . ' days')),
      ];

      if ($loanCopyColumn !== null) {
        $insertColumns[] = '`' . $loanCopyColumn . '`';
        $insertValues[] = ':book_copy_id';
        $insertParams[':book_copy_id'] = $copyId;
      }
      if ($loanBookColumn !== null) {
        $insertColumns[] = '`' . $loanBookColumn . '`';
        $insertValues[] = ':book_id';
        $insertParams[':book_id'] = $bookId;
      }

      $loanCheckedOutColumn = self::resolveColumn($db, 'loans', ['checked_out_at', 'checkout_date']);
      if ($loanCheckedOutColumn !== null) {
        $insertColumns[] = '`' . $loanCheckedOutColumn . '`';
        $insertValues[] = ':checked_out_at';
        $insertParams[':checked_out_at'] = date('Y-m-d H:i:s');
      }

      $loanReservationColumn = self::resolveColumn($db, 'loans', ['reservation_id']);
      if ($reservationId !== null && $reservationId > 0 && $loanReservationColumn !== null) {
        $insertColumns[] = '`' . $loanReservationColumn . '`';
        $insertValues[] = ':reservation_id';
        $insertParams[':reservation_id'] = $reservationId;
      }

      $loanInsertSql = 'INSERT INTO loans (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
      $loanInsertStmt = $db->prepare($loanInsertSql);
      $loanInsertStmt->execute($insertParams);
      $loanId = (int)$db->lastInsertId();

      if (
        self::tableExists($db, 'loan_events')
        && self::hasColumn($db, 'loan_events', 'loan_id')
        && self::hasColumn($db, 'loan_events', 'event_type')
      ) {
        $eventColumns = ['loan_id', 'event_type'];
        $eventValues = [':loan_id', ':event_type'];
        $eventParams = [
          ':loan_id' => $loanId,
          ':event_type' => 'checkout',
        ];

        if (self::hasColumn($db, 'loan_events', 'actor_user_id')) {
          $eventColumns[] = 'actor_user_id';
          $eventValues[] = ':actor_user_id';
          $eventParams[':actor_user_id'] = $actorUserId > 0 ? $actorUserId : null;
        }

        if (self::hasColumn($db, 'loan_events', 'notes')) {
          $eventColumns[] = 'notes';
          $eventValues[] = ':notes';
          $note = $reservationId !== null && $reservationId > 0
            ? 'Checked out from ready reservation #' . $reservationId
            : 'Checked out via librarian circulation page';
          $eventParams[':notes'] = $note;
        }

        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      $receiptMeta = self::createTransactionReceipt(
        $db,
        $reservationId !== null && $reservationId > 0 ? 'reservation_checkout' : 'checkout',
        $loanId,
        $borrowerUserId,
        $actorUserId,
        [
          'loan_id' => $loanId,
          'borrower_user_id' => $borrowerUserId,
          'book_id' => $bookId,
          'book_copy_id' => $copyId,
          'reservation_id' => $reservationId,
          'loan_days' => $loanDays,
          'due_at' => $insertParams[':due_at'],
          'generated_by' => 'LibrarianPortalRepository::checkoutLoan',
        ]
      );

      $db->commit();
      return [
        'ok' => true,
        'message' => 'Checkout completed successfully.',
        'loan_id' => $loanId,
        'copy_id' => $copyId,
        'receipt_id' => $receiptMeta['receipt_id'],
        'receipt_code' => $receiptMeta['receipt_code'],
        'receipt_print_url' => $receiptMeta['receipt_print_url'],
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      error_log('LibrarianPortalRepository::checkoutLoan error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to check out loan right now.'];
    }
  }

  /**
   * @return array{ok: bool, message: string, loan_id?: int, reservation_id?: int, receipt_id?: int, receipt_code?: string, receipt_print_url?: string}
   */
  public static function checkoutReadyReservation(PDO $db, int $reservationId, int $actorUserId, int $loanDays = self::DEFAULT_LOAN_DAYS): array
  {
    $preCheck = self::evaluateReadyReservationCheckoutRules([
      'reservation_id' => $reservationId,
      'reservation_exists' => true,
      'reservation_status' => 'ready',
      'borrower_user_id' => 1,
      'book_id' => 1,
      'has_available_copy' => true,
    ]);
    if (!$preCheck['ok']) {
      return $preCheck;
    }

    if (!self::tableExists($db, 'reservations') || !self::hasColumn($db, 'reservations', 'status')) {
      return ['ok' => false, 'message' => 'Reservation checkout bridge is unavailable due to schema mismatch.'];
    }

    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    if ($reservationUserColumn === null || $reservationBookColumn === null) {
      return ['ok' => false, 'message' => 'Reservation checkout bridge requires user_id and book_id columns.'];
    }

    if (!self::tableExists($db, 'book_copies')) {
      return ['ok' => false, 'message' => 'Reservation checkout bridge is unavailable because book copies table is missing.'];
    }

    if (!self::hasColumn($db, 'book_copies', 'book_id') || !self::hasColumn($db, 'book_copies', 'status')) {
      return ['ok' => false, 'message' => 'Reservation checkout bridge is unavailable due to incompatible copies schema.'];
    }

    try {
      $db->beginTransaction();

      $reservationStmt = $db->prepare('SELECT id, `' . $reservationUserColumn . '` AS user_id, `' . $reservationBookColumn . '` AS book_id, status FROM reservations WHERE id = :id LIMIT 1 FOR UPDATE');
      $reservationStmt->execute([':id' => $reservationId]);
      $reservation = $reservationStmt->fetch(PDO::FETCH_ASSOC);

      $currentStatus = is_array($reservation)
        ? strtolower(trim((string)($reservation['status'] ?? '')))
        : '';

      $borrowerUserId = is_array($reservation) ? (int)($reservation['user_id'] ?? 0) : 0;
      $bookId = is_array($reservation) ? (int)($reservation['book_id'] ?? 0) : 0;

      $copyStmt = $db->prepare('SELECT id FROM book_copies WHERE book_id = :book_id AND status = :status ORDER BY id ASC LIMIT 1 FOR UPDATE');
      $copyStmt->execute([
        ':book_id' => $bookId,
        ':status' => 'available',
      ]);
      $copyId = (int)$copyStmt->fetchColumn();

      $runtimeRule = self::evaluateReadyReservationCheckoutRules([
        'reservation_id' => $reservationId,
        'reservation_exists' => is_array($reservation),
        'reservation_status' => $currentStatus,
        'borrower_user_id' => $borrowerUserId,
        'book_id' => $bookId,
        'has_available_copy' => $copyId > 0,
      ]);
      if (!$runtimeRule['ok']) {
        $db->rollBack();
        return $runtimeRule;
      }

      $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
      $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
      $loanBookColumn = self::resolveColumn($db, 'loans', ['book_id']);
      $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
      $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
      if ($loanUserColumn === null || ($loanCopyColumn === null && $loanBookColumn === null) || $loanStatusColumn === null || $loanDueColumn === null) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Loans schema is incompatible with checkout bridge.'];
      }

      $copyUpdateStmt = $db->prepare('UPDATE book_copies SET status = :status WHERE id = :id');
      $copyUpdateStmt->execute([
        ':status' => 'loaned',
        ':id' => $copyId,
      ]);

      $loanDays = max(1, min(60, $loanDays));
      $insertColumns = ['`' . $loanUserColumn . '`', '`' . $loanStatusColumn . '`', '`' . $loanDueColumn . '`'];
      $insertValues = [':user_id', ':loan_status', ':due_at'];
      $insertParams = [
        ':user_id' => $borrowerUserId,
        ':loan_status' => 'active',
        ':due_at' => date('Y-m-d H:i:s', strtotime('+' . $loanDays . ' days')),
      ];

      if ($loanCopyColumn !== null) {
        $insertColumns[] = '`' . $loanCopyColumn . '`';
        $insertValues[] = ':book_copy_id';
        $insertParams[':book_copy_id'] = $copyId;
      }
      if ($loanBookColumn !== null) {
        $insertColumns[] = '`' . $loanBookColumn . '`';
        $insertValues[] = ':book_id';
        $insertParams[':book_id'] = $bookId;
      }

      $loanCheckedOutColumn = self::resolveColumn($db, 'loans', ['checked_out_at', 'checkout_date']);
      if ($loanCheckedOutColumn !== null) {
        $insertColumns[] = '`' . $loanCheckedOutColumn . '`';
        $insertValues[] = ':checked_out_at';
        $insertParams[':checked_out_at'] = date('Y-m-d H:i:s');
      }

      $loanReservationColumn = self::resolveColumn($db, 'loans', ['reservation_id']);
      if ($loanReservationColumn !== null) {
        $insertColumns[] = '`' . $loanReservationColumn . '`';
        $insertValues[] = ':reservation_id';
        $insertParams[':reservation_id'] = $reservationId;
      }

      $loanInsertSql = 'INSERT INTO loans (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $insertValues) . ')';
      $loanInsertStmt = $db->prepare($loanInsertSql);
      $loanInsertStmt->execute($insertParams);
      $loanId = (int)$db->lastInsertId();

      $reservationUpdateParts = ['status = :status'];
      $reservationUpdateParams = [
        ':status' => 'fulfilled',
        ':id' => $reservationId,
      ];

      $reservationPickedUpColumn = self::resolveColumn($db, 'reservations', ['picked_up_at', 'fulfilled_at']);
      if ($reservationPickedUpColumn !== null) {
        $reservationUpdateParts[] = '`' . $reservationPickedUpColumn . '` = NOW()';
      }

      $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);
      if ($reservationReadyUntilColumn !== null) {
        $reservationUpdateParts[] = '`' . $reservationReadyUntilColumn . '` = NULL';
      }

      $reservationUpdateSql = 'UPDATE reservations SET ' . implode(', ', $reservationUpdateParts) . ' WHERE id = :id';
      $reservationUpdateStmt = $db->prepare($reservationUpdateSql);
      $reservationUpdateStmt->execute($reservationUpdateParams);

      if (
        self::tableExists($db, 'loan_events')
        && self::hasColumn($db, 'loan_events', 'loan_id')
        && self::hasColumn($db, 'loan_events', 'event_type')
      ) {
        $eventColumns = ['loan_id', 'event_type'];
        $eventValues = [':loan_id', ':event_type'];
        $eventParams = [
          ':loan_id' => $loanId,
          ':event_type' => 'checkout',
        ];

        if (self::hasColumn($db, 'loan_events', 'actor_user_id')) {
          $eventColumns[] = 'actor_user_id';
          $eventValues[] = ':actor_user_id';
          $eventParams[':actor_user_id'] = $actorUserId > 0 ? $actorUserId : null;
        }

        if (self::hasColumn($db, 'loan_events', 'notes')) {
          $eventColumns[] = 'notes';
          $eventValues[] = ':notes';
          $eventParams[':notes'] = 'Checkout completed from ready reservation #' . $reservationId;
        }

        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      $receiptMeta = self::createTransactionReceipt(
        $db,
        'reservation_checkout',
        $loanId,
        $borrowerUserId,
        $actorUserId,
        [
          'loan_id' => $loanId,
          'reservation_id' => $reservationId,
          'borrower_user_id' => $borrowerUserId,
          'book_id' => $bookId,
          'book_copy_id' => $copyId,
          'loan_days' => $loanDays,
          'due_at' => $insertParams[':due_at'],
          'generated_by' => 'LibrarianPortalRepository::checkoutReadyReservation',
        ]
      );

      $db->commit();

      return [
        'ok' => true,
        'message' => 'Ready reservation checked out successfully.',
        'loan_id' => $loanId,
        'reservation_id' => $reservationId,
        'receipt_id' => $receiptMeta['receipt_id'],
        'receipt_code' => $receiptMeta['receipt_code'],
        'receipt_print_url' => $receiptMeta['receipt_print_url'],
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      error_log('LibrarianPortalRepository::checkoutReadyReservation error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to complete ready reservation checkout right now.'];
    }
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string, total?: int, page?: int, per_page?: int}
   */
  public static function getBooks(PDO $db, string $search = '', int $limit = 250, string $type = '', int $page = 1): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
      'has_more' => false,
    ];

    if (!self::tableExists($db, 'books')) {
      $response['available'] = false;
      $response['message'] = 'Books table is missing. Run circulation migration to enable catalog listing.';
      return $response;
    }

    $isbnColumn = self::resolveColumn($db, 'books', ['isbn']);
    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    $publishedYearColumn = self::resolveColumn($db, 'books', ['published_year', 'publication_year']);
    $isActiveColumn = self::resolveColumn($db, 'books', ['is_active']);

    $search = trim($search);
    $type = trim($type);
    $limit = max(1, min(500, $limit));
    $fetchLimit = $limit + 1;
    $where = '';
    $params = [];

    if ($search !== '') {
      $searchParts = ['b.title LIKE :term0', 'b.author LIKE :term1'];
      $params[':term0'] = '%' . $search . '%';
      $params[':term1'] = '%' . $search . '%';
      $paramIdx = 2;
      if ($isbnColumn !== null) {
        $searchParts[] = 'COALESCE(b.`' . $isbnColumn . '`, \'\') LIKE :term' . $paramIdx;
        $params[':term' . $paramIdx] = '%' . $search . '%';
        $paramIdx++;
      }
      if ($categoryColumn !== null) {
        $searchParts[] = 'COALESCE(b.`' . $categoryColumn . '`, \'\') LIKE :term' . $paramIdx;
        $params[':term' . $paramIdx] = '%' . $search . '%';
        $paramIdx++;
      }
      $where = ' WHERE (' . implode(' OR ', $searchParts) . ')';
    }

    if ($isActiveColumn !== null) {
      $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'b.`' . $isActiveColumn . '` = 1';
    }

    if ($type !== '' && $categoryColumn !== null) {
      $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'LOWER(TRIM(COALESCE(b.`' . $categoryColumn . '`, \'\'))) = :type';
      $params[':type'] = strtolower($type);
    }

    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id');
    $isbnExpr = $isbnColumn !== null ? 'b.`' . $isbnColumn . '`' : "''";
    $categoryExpr = $categoryColumn !== null ? 'b.`' . $categoryColumn . '`' : "''";
    $publishedYearExpr = $publishedYearColumn !== null ? 'b.`' . $publishedYearColumn . '`' : 'NULL';
    $isActiveExpr = $isActiveColumn !== null ? 'b.`' . $isActiveColumn . '`' : '1';

    if ($hasCopies && self::hasColumn($db, 'book_copies', 'status')) {
      $descriptionExpr = self::hasColumn($db, 'books', 'description')
        ? 'b.description'
        : (self::hasColumn($db, 'books', 'summary')
          ? 'b.summary'
          : (self::hasColumn($db, 'books', 'synopsis')
            ? 'b.synopsis'
            : (self::hasColumn($db, 'books', 'short_description')
              ? 'b.short_description'
              : (self::hasColumn($db, 'books', 'blurb') ? 'b.blurb' : 'NULL'))));

      $coverExpr = self::hasColumn($db, 'books', 'cover_image_url')
        ? 'b.cover_image_url'
        : (self::hasColumn($db, 'books', 'cover_url')
          ? 'b.cover_url'
          : (self::hasColumn($db, 'books', 'image_url')
            ? 'b.image_url'
            : (self::hasColumn($db, 'books', 'thumbnail_url')
              ? 'b.thumbnail_url'
              : (self::hasColumn($db, 'books', 'cover_image')
                ? 'b.cover_image'
                : (self::hasColumn($db, 'books', 'book_cover')
                  ? 'b.book_cover'
                  : (self::hasColumn($db, 'books', 'book_image') ? 'b.book_image' : 'NULL'))))));

      $sql = "SELECT
        b.id,
        {$isbnExpr} AS isbn,
        b.title,
        b.author,
        {$categoryExpr} AS category,
        {$publishedYearExpr} AS published_year,
        {$descriptionExpr} AS description,
        {$coverExpr} AS cover_image_url,
        {$isActiveExpr} AS is_active,
        COUNT(bc.id) AS total_copies,
        SUM(CASE WHEN bc.status = 'available' THEN 1 ELSE 0 END) AS available_copies
      FROM books b
      LEFT JOIN book_copies bc ON bc.book_id = b.id
      {$where}
      GROUP BY b.id, {$isbnExpr}, b.title, b.author, {$categoryExpr}, {$publishedYearExpr}, {$descriptionExpr}, {$coverExpr}, {$isActiveExpr}
      ORDER BY b.title ASC
      LIMIT {$fetchLimit}";
    } else {
      $descriptionExpr = self::hasColumn($db, 'books', 'description')
        ? 'b.description'
        : (self::hasColumn($db, 'books', 'summary')
          ? 'b.summary'
          : (self::hasColumn($db, 'books', 'synopsis')
            ? 'b.synopsis'
            : (self::hasColumn($db, 'books', 'short_description')
              ? 'b.short_description'
              : (self::hasColumn($db, 'books', 'blurb') ? 'b.blurb' : 'NULL'))));

      $coverExpr = self::hasColumn($db, 'books', 'cover_image_url')
        ? 'b.cover_image_url'
        : (self::hasColumn($db, 'books', 'cover_url')
          ? 'b.cover_url'
          : (self::hasColumn($db, 'books', 'image_url')
            ? 'b.image_url'
            : (self::hasColumn($db, 'books', 'thumbnail_url')
              ? 'b.thumbnail_url'
              : (self::hasColumn($db, 'books', 'cover_image')
                ? 'b.cover_image'
                : (self::hasColumn($db, 'books', 'book_cover')
                  ? 'b.book_cover'
                  : (self::hasColumn($db, 'books', 'book_image') ? 'b.book_image' : 'NULL'))))));

      $sql = "SELECT
        b.id,
        {$isbnExpr} AS isbn,
        b.title,
        b.author,
        {$categoryExpr} AS category,
        {$publishedYearExpr} AS published_year,
        {$descriptionExpr} AS description,
        {$coverExpr} AS cover_image_url,
        {$isActiveExpr} AS is_active,
        0 AS total_copies,
        0 AS available_copies
      FROM books b
      {$where}
      ORDER BY b.title ASC
      LIMIT {$fetchLimit}";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows) > $limit) {
      $response['has_more'] = true;
      array_pop($rows);
    }
    $response['rows'] = $rows;
    return $response;
  }

  /**
   * @return list<string>
   */
  /**
   * @return array<string,mixed>|null
   */
  public static function getBookById(PDO $db, int $bookId): ?array
  {
    if ($bookId <= 0 || !self::tableExists($db, 'books')) {
      return null;
    }

    $isbnColumn = self::resolveColumn($db, 'books', ['isbn']);
    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    $publishedYearColumn = self::resolveColumn($db, 'books', ['published_year', 'publication_year', 'publish_year']);
    $publicationDateColumn = self::hasColumn($db, 'books', 'publication_date') ? 'publication_date' : null;
    $descriptionColumn = self::resolveColumn($db, 'books', ['description', 'summary', 'synopsis']);
    $publisherColumn = self::hasColumn($db, 'books', 'publisher') ? 'publisher' : null;
    $editionColumn = self::hasColumn($db, 'books', 'edition') ? 'edition' : null;
    $locationColumn = self::hasColumn($db, 'books', 'location') ? 'location' : null;
    $isActiveColumn = self::hasColumn($db, 'books', 'is_active') ? 'is_active' : null;
    $coverColumn = self::resolveColumn($db, 'books', ['cover_image', 'cover_image_url', 'cover_url', 'image_url']);

    $sql = 'SELECT b.id, b.title, b.author';
    $sql .= $isbnColumn !== null ? ', b.`' . $isbnColumn . '` AS isbn' : ", '' AS isbn";
    $sql .= $categoryColumn !== null ? ', b.`' . $categoryColumn . '` AS category' : ", '' AS category";
    $sql .= $publishedYearColumn !== null ? ', b.`' . $publishedYearColumn . '` AS publish_year' : ', NULL AS publish_year';
    $sql .= $publicationDateColumn !== null ? ', b.publication_date' : ', NULL AS publication_date';
    $sql .= $descriptionColumn !== null ? ', b.`' . $descriptionColumn . '` AS description' : ', NULL AS description';
    $sql .= $publisherColumn !== null ? ', b.publisher' : ', NULL AS publisher';
    $sql .= $editionColumn !== null ? ', b.edition' : ', NULL AS edition';
    $sql .= $locationColumn !== null ? ', b.location' : ', NULL AS location';
    $sql .= $isActiveColumn !== null ? ', b.is_active' : ', 1 AS is_active';
    $sql .= $coverColumn !== null ? ', b.`' . $coverColumn . '` AS cover_image' : ", '' AS cover_image";

    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id');
    $hasStatus = self::hasColumn($db, 'book_copies', 'status');

    if ($hasCopies) {
      $sql .= ', (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) AS total_copies';
      if ($hasStatus) {
        $sql .= ", (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'available') AS available_copies";
      } else {
        $sql .= ', 0 AS available_copies';
      }
    } else {
      $hasTotalCopiesCol = self::hasColumn($db, 'books', 'total_copies');
      $hasAvailCopiesCol = self::hasColumn($db, 'books', 'available_copies');
      $sql .= $hasTotalCopiesCol ? ', b.total_copies' : ', 0 AS total_copies';
      $sql .= $hasAvailCopiesCol ? ', b.available_copies' : ', 0 AS available_copies';
    }

    $sql .= ' FROM books b WHERE b.id = :id LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $bookId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) && !empty($row) ? $row : null;
  }

  /**
   * @param array<string,mixed> $input
   * @return array{ok: bool, message: string}
   */
  public static function updateBook(PDO $db, array $input): array
  {
    $bookId = (int)($input['book_id'] ?? 0);
    if ($bookId <= 0) {
      return ['ok' => false, 'message' => 'Invalid book identifier.'];
    }

    if (!self::tableExists($db, 'books')) {
      return ['ok' => false, 'message' => 'Books table is missing.'];
    }

    $validation = self::evaluateBookCreationRules($input);
    if (!$validation['ok']) {
      return $validation;
    }

    $normalized = (array)($validation['normalized'] ?? []);
    $title = (string)($normalized['title'] ?? '');
    $author = (string)($normalized['author'] ?? '');
    $isbnNormalized = (string)($normalized['isbn_normalized'] ?? '');
    $publishedYear = (int)($normalized['published_year'] ?? 0);
    $genre = (string)($normalized['genre'] ?? '');
    $publicationDate = (string)($normalized['publication_date'] ?? '');

    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    $publishedYearColumn = self::resolveColumn($db, 'books', ['published_year', 'publication_year', 'publish_year']);
    $hasPublicationDate = self::hasColumn($db, 'books', 'publication_date');
    $coverColumn = self::resolveColumn($db, 'books', ['cover_image', 'cover_image_url', 'cover_url', 'image_url', 'thumbnail_url']);
    $hasTotalCopiesCol = self::hasColumn($db, 'books', 'total_copies');
    $hasAvailableCopiesCol = self::hasColumn($db, 'books', 'available_copies');
    $hasIsActive = self::hasColumn($db, 'books', 'is_active');
    $hasIsbn = self::hasColumn($db, 'books', 'isbn');
    $descriptionColumn = self::resolveColumn($db, 'books', ['description', 'summary', 'synopsis']);
    $publisherColumn = self::hasColumn($db, 'books', 'publisher') ? 'publisher' : null;
    $editionColumn = self::hasColumn($db, 'books', 'edition') ? 'edition' : null;
    $locationColumn = self::hasColumn($db, 'books', 'location') ? 'location' : null;

    $newTotalCopies = (int)($input['total_copies'] ?? -1);
    $newCoverImage = trim((string)($input['cover_image_url'] ?? ''));

    try {
      $db->beginTransaction();

      // Check ISBN uniqueness, excluding current book
      if ($hasIsbn && $isbnNormalized !== '') {
        $existingIsbnStmt = $db->query("SELECT id, isbn FROM books WHERE COALESCE(isbn, '') <> '' AND id <> " . (int)$bookId);
        $existingIsbnRows = $existingIsbnStmt ? $existingIsbnStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($existingIsbnRows as $existingIsbnRow) {
          $existingIsbn = self::normalizeIsbn((string)($existingIsbnRow['isbn'] ?? ''));
          if ($existingIsbn !== '' && hash_equals($existingIsbn, $isbnNormalized)) {
            $db->rollBack();
            return ['ok' => false, 'message' => 'Duplicate entry: ISBN already exists in the catalog.'];
          }
        }
      }

      // Build UPDATE
      $updateParts = [];
      $params = [':id' => $bookId];

      $updateParts[] = 'title = :title';
      $params[':title'] = $title;

      $updateParts[] = 'author = :author';
      $params[':author'] = $author;

      if ($hasIsbn) {
        $updateParts[] = 'isbn = :isbn';
        $params[':isbn'] = $isbnNormalized;
      }

      if ($categoryColumn !== null) {
        $updateParts[] = '`' . $categoryColumn . '` = :category';
        $params[':category'] = $genre;
      }

      if ($publishedYearColumn !== null) {
        $updateParts[] = '`' . $publishedYearColumn . '` = :published_year';
        $params[':published_year'] = $publishedYear;
      }

      if ($hasPublicationDate) {
        $updateParts[] = 'publication_date = :publication_date';
        $params[':publication_date'] = $publicationDate;
      }

      if ($coverColumn !== null) {
        $updateParts[] = '`' . $coverColumn . '` = :cover_image_url';
        $params[':cover_image_url'] = $newCoverImage;
      }

      if ($descriptionColumn !== null) {
        $desc = trim((string)($input['description'] ?? ''));
        $updateParts[] = '`' . $descriptionColumn . '` = :description';
        $params[':description'] = $desc;
      }

      if ($publisherColumn !== null) {
        $publisher = trim((string)($input['publisher'] ?? ''));
        $updateParts[] = '`' . $publisherColumn . '` = :publisher';
        $params[':publisher'] = $publisher;
      }

      if ($editionColumn !== null) {
        $edition = trim((string)($input['edition'] ?? ''));
        $updateParts[] = '`' . $editionColumn . '` = :edition';
        $params[':edition'] = $edition;
      }

      if ($locationColumn !== null) {
        $location = trim((string)($input['location'] ?? ''));
        $updateParts[] = '`' . $locationColumn . '` = :location';
        $params[':location'] = $location;
      }

      if ($hasIsActive) {
        $isActive = isset($input['is_active']) ? ((int)$input['is_active'] === 1 ? 1 : 0) : 1;
        $updateParts[] = 'is_active = :is_active';
        $params[':is_active'] = $isActive;
      }

      if (!empty($updateParts)) {
        $updateSql = 'UPDATE books SET ' . implode(', ', $updateParts) . ' WHERE id = :id';
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->execute($params);
      }

      // Handle copy count adjustment via book_copies table
      $hasCopiesTable = self::tableExists($db, 'book_copies')
        && self::hasColumn($db, 'book_copies', 'book_id')
        && self::hasColumn($db, 'book_copies', 'status');

      if ($hasCopiesTable && $newTotalCopies >= 0) {
        $newTotalCopies = min(200, max(0, $newTotalCopies));

        $countStmt = $db->prepare('SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id');
        $countStmt->execute([':book_id' => $bookId]);
        $currentCopies = (int)$countStmt->fetchColumn();

        if ($newTotalCopies > $currentCopies) {
          // Add more available copies
          $copyInsertStmt = $db->prepare('INSERT INTO book_copies (book_id, barcode, status) VALUES (:book_id, :barcode, :status)');
          for ($i = $currentCopies; $i < $newTotalCopies; $i++) {
            $copyInsertStmt->execute([
              ':book_id' => $bookId,
              ':barcode' => self::buildCopyBarcode($bookId),
              ':status' => 'available',
            ]);
          }
        } elseif ($newTotalCopies < $currentCopies) {
          // Remove excess available copies (only available ones)
          $excess = $currentCopies - $newTotalCopies;
          if ($excess > 0) {
            $availableStmt = $db->prepare("SELECT id FROM book_copies WHERE book_id = :book_id AND status = 'available' ORDER BY id ASC");
            $availableStmt->execute([':book_id' => $bookId]);
            $availableIds = $availableStmt->fetchAll(PDO::FETCH_COLUMN);

            $toRemove = min($excess, count($availableIds));
            if ($toRemove > 0) {
              $removeIds = array_slice($availableIds, 0, $toRemove);
              $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
              $db->prepare('DELETE FROM book_copies WHERE id IN (' . $placeholders . ')')->execute($removeIds);
            }

            if ($toRemove < $excess) {
              $db->rollBack();
              return [
                'ok' => false,
                'message' => 'Cannot reduce copies: only ' . count($availableIds) . ' available copy(ies) can be removed. '
                  . 'Loan/reserved copies must be returned first.',
              ];
            }
          }
        }

        // Get updated counts for legacy columns
        $countStmt = $db->prepare('SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id');
        $countStmt->execute([':book_id' => $bookId]);
        $updatedTotal = (int)$countStmt->fetchColumn();

        $availStmt = $db->prepare("SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = 'available'");
        $availStmt->execute([':book_id' => $bookId]);
        $updatedAvailable = (int)$availStmt->fetchColumn();

        if ($hasTotalCopiesCol) {
          $db->prepare('UPDATE books SET total_copies = :total WHERE id = :id')->execute([
            ':total' => $updatedTotal,
            ':id' => $bookId,
          ]);
        }

        if ($hasAvailableCopiesCol) {
          $db->prepare('UPDATE books SET available_copies = :available WHERE id = :id')->execute([
            ':available' => $updatedAvailable,
            ':id' => $bookId,
          ]);
        }
      }

      $db->commit();
      return ['ok' => true, 'message' => 'Book updated successfully.'];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::updateBook error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to update book right now.'];
    }
  }

  /**
   * @return list<string>
   */
  public static function getBookTypes(PDO $db, int $limit = 250): array

  {
    if (!self::tableExists($db, 'books')) {
      return [];
    }

    $categoryColumn = self::resolveColumn($db, 'books', ['category', 'genre']);
    if ($categoryColumn === null) {
      return [];
    }

    $limit = max(1, min(500, $limit));
    $isActiveColumn = self::resolveColumn($db, 'books', ['is_active']);
    $where = 'WHERE COALESCE(TRIM(b.`' . $categoryColumn . '`), \'\') <> \'\'';
    if ($isActiveColumn !== null) {
      $where .= ' AND b.`' . $isActiveColumn . '` = 1';
    }

    $sql = 'SELECT DISTINCT TRIM(b.`' . $categoryColumn . '`) AS type_name
      FROM books b
      ' . $where . '
      ORDER BY type_name ASC
      LIMIT ' . $limit;

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $types = [];
    foreach ($rows as $value) {
      $label = trim((string)$value);
      if ($label === '') {
        continue;
      }
      $types[] = $label;
    }

    return $types;
  }

  /**
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function getReservationQueue(PDO $db, int $limit = 250): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'reservations')) {
      $response['available'] = false;
      $response['message'] = 'Reservations table is missing. Run circulation migration to enable queue management.';
      return $response;
    }

    $reservationIdColumn = self::resolveColumn($db, 'reservations', ['id']);
    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    $reservationStatusColumn = self::resolveColumn($db, 'reservations', ['status']);
    $reservationQueuedColumn = self::resolveColumn($db, 'reservations', ['queued_at', 'reserved_at', 'created_at']);
    $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);
    $reservationPickedUpColumn = self::resolveColumn($db, 'reservations', ['picked_up_at', 'fulfilled_at']);

    if ($reservationIdColumn === null || $reservationStatusColumn === null) {
      $response['available'] = false;
      $response['message'] = 'Reservations schema is incompatible with queue management.';
      return $response;
    }

    $limit = max(1, min(500, $limit));
    $hasUsers = self::tableExists($db, 'users') && $reservationUserColumn !== null;
    $hasBooks = self::tableExists($db, 'books') && $reservationBookColumn !== null;
    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id') && self::hasColumn($db, 'book_copies', 'status');

    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = r.`' . $reservationUserColumn . '`' : '';
    $bookJoin = $hasBooks ? ' LEFT JOIN books b ON b.id = r.`' . $reservationBookColumn . '`' : '';

    $userExpr = $reservationUserColumn !== null ? 'r.`' . $reservationUserColumn . '`' : 'NULL';
    $bookExpr = $reservationBookColumn !== null ? 'r.`' . $reservationBookColumn . '`' : 'NULL';
    $queuedExpr = $reservationQueuedColumn !== null ? 'r.`' . $reservationQueuedColumn . '`' : 'NULL';
    $readyUntilExpr = $reservationReadyUntilColumn !== null ? 'r.`' . $reservationReadyUntilColumn . '`' : 'NULL';
    $pickedUpExpr = $reservationPickedUpColumn !== null ? 'r.`' . $reservationPickedUpColumn . '`' : 'NULL';
    $orderByExpr = $reservationQueuedColumn !== null ? $queuedExpr : 'r.`' . $reservationIdColumn . '`';
    $availableCopiesExpr = '0';
    if ($hasCopies) {
      $availableCopiesExpr = '(SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = r.`' . $reservationBookColumn . '` AND bc.status = \'available\')';
    }

    $sql = "SELECT
      r.`{$reservationIdColumn}` AS id,
      {$userExpr} AS user_id,
      {$bookExpr} AS book_id,
      r.`{$reservationStatusColumn}` AS status,
      {$queuedExpr} AS queued_at,
      {$readyUntilExpr} AS ready_until,
      {$pickedUpExpr} AS picked_up_at,
      {$availableCopiesExpr} AS available_copies,
      " . ($hasUsers ? 'u.first_name' : "''") . " AS borrower_first_name,
      " . ($hasUsers ? 'u.last_name' : "''") . " AS borrower_last_name,
      " . ($hasUsers ? 'u.email' : "''") . " AS borrower_email,
      " . ($hasBooks ? 'b.title' : "''") . " AS book_title,
      " . ($hasBooks ? 'b.author' : "''") . " AS book_author
    FROM reservations r
    {$userJoin}
    {$bookJoin}
    WHERE r.`{$reservationStatusColumn}` IN ('pending', 'ready')
    ORDER BY {$orderByExpr} ASC, r.`{$reservationIdColumn}` ASC
    LIMIT {$limit}";

    $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $bookPositions = [];
    foreach ($rows as &$row) {
      $bookId = (int)($row['book_id'] ?? 0);
      if (!isset($bookPositions[$bookId])) {
        $bookPositions[$bookId] = 0;
      }
      $bookPositions[$bookId]++;
      $row['queue_position'] = $bookPositions[$bookId];
      $row['can_checkout'] = ((int)($row['available_copies'] ?? 0)) > 0;
    }
    unset($row);

    $response['rows'] = $rows;
    return $response;
  }

  /**
   * @return array{ok: bool, message: string}
   */
  public static function updateReservationStatus(PDO $db, int $reservationId, string $action): array
  {
    if ($reservationId <= 0) {
      return ['ok' => false, 'message' => 'Invalid reservation identifier.'];
    }

    if (!self::tableExists($db, 'reservations')) {
      return ['ok' => false, 'message' => 'Reservation action is unavailable because reservations table is missing.'];
    }

    if (!self::hasColumn($db, 'reservations', 'status')) {
      return ['ok' => false, 'message' => 'Reservation action is unavailable due to incompatible schema.'];
    }

    $action = strtolower(trim($action));
    if (!in_array($action, ['approve', 'reject', 'cancel'], true)) {
      return ['ok' => false, 'message' => 'Unsupported reservation action.'];
    }

    try {
      $db->beginTransaction();

      $selectSql = 'SELECT id, status FROM reservations WHERE id = :id FOR UPDATE';
      $selectStmt = $db->prepare($selectSql);
      $selectStmt->execute([':id' => $reservationId]);
      $reservation = $selectStmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($reservation)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Reservation record not found.'];
      }

      $currentStatus = strtolower(trim((string)($reservation['status'] ?? '')));
      $updateParts = [];
      $params = [':id' => $reservationId];
      $successMessage = 'Reservation updated.';

      if ($action === 'approve') {
        if ($currentStatus !== 'pending') {
          $db->rollBack();
          return ['ok' => false, 'message' => 'Only pending reservations can be approved.'];
        }

        $updateParts[] = 'status = :status';
        $params[':status'] = 'ready';

        if (self::hasColumn($db, 'reservations', 'expires_at')) {
          $updateParts[] = 'expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY)';
        } elseif (self::hasColumn($db, 'reservations', 'ready_until')) {
          $updateParts[] = 'ready_until = DATE_ADD(NOW(), INTERVAL 3 DAY)';
        }

        $successMessage = 'Reservation approved and marked ready for pickup.';
      }

      if ($action === 'reject') {
        if ($currentStatus !== 'pending') {
          $db->rollBack();
          return ['ok' => false, 'message' => 'Only pending reservations can be rejected.'];
        }

        $updateParts[] = 'status = :status';
        $params[':status'] = 'cancelled';

        $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);
        if ($reservationReadyUntilColumn !== null) {
          $updateParts[] = '`' . $reservationReadyUntilColumn . '` = NULL';
        }

        $successMessage = 'Reservation rejected.';
      }

      if ($action === 'cancel') {
        if (!in_array($currentStatus, ['pending', 'ready'], true)) {
          $db->rollBack();
          return ['ok' => false, 'message' => 'Only active queue reservations can be cancelled.'];
        }

        $updateParts[] = 'status = :status';
        $params[':status'] = 'cancelled';

        $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);
        if ($reservationReadyUntilColumn !== null) {
          $updateParts[] = '`' . $reservationReadyUntilColumn . '` = NULL';
        }

        $successMessage = 'Reservation cancelled.';
      }

      if (empty($updateParts)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'No reservation changes were applied.'];
      }

      $updateSql = 'UPDATE reservations SET ' . implode(', ', $updateParts) . ' WHERE id = :id';
      $updateStmt = $db->prepare($updateSql);
      $updateStmt->execute($params);

      $db->commit();

      // Send email notification when reservation is approved (ready for pickup)
      if ($action === 'approve' && $reservationId > 0) {
        try {
          self::sendReservationReadyNotification($db, $reservationId);
        } catch (Exception $e) {
          error_log('Failed to send reservation ready notification: ' . $e->getMessage());
        }
      }

      return ['ok' => true, 'message' => $successMessage];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::updateReservationStatus error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to update reservation right now.'];
    }
  }

  /**
   * Send email notification to borrower when reservation is ready for pickup.
   */
  private static function sendReservationReadyNotification(PDO $db, int $reservationId): void
  {
    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);

    if ($reservationUserColumn === null || $reservationBookColumn === null) {
      return;
    }

    $readyUntilExpr = $reservationReadyUntilColumn !== null ? 'r.`' . $reservationReadyUntilColumn . '`' : 'NULL';

    $sql = "SELECT r.id, r.`{$reservationUserColumn}` AS user_id, r.`{$reservationBookColumn}` AS book_id,
              {$readyUntilExpr} AS ready_until,
              u.email, u.first_name, u.last_name,
              b.title AS book_title, b.author AS book_author
              FROM reservations r
              LEFT JOIN users u ON u.id = r.`{$reservationUserColumn}`
              LEFT JOIN books b ON b.id = r.`{$reservationBookColumn}`
              WHERE r.id = :id LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $reservationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!is_array($row) || empty($row['email'])) {
      return;
    }

    $email = trim((string)$row['email']);
    $name = trim((string)($row['first_name'] ?? '') . ' ' . (string)($row['last_name'] ?? ''));
    if ($name === '') {
      $name = $email;
    }
    $bookTitle = trim((string)($row['book_title'] ?? 'Unknown Title'));
    $bookAuthor = trim((string)($row['book_author'] ?? ''));
    $readyUntil = trim((string)($row['ready_until'] ?? '3 days'));

    try {
      $mailHandler = null;
      if (function_exists('getMailHandler')) {
        $mailHandler = getMailHandler();
      }
      if ($mailHandler === null) {
        require_once __DIR__ . '/../mail/MailHandler.php';
        $mailHandler = new MailHandler($db);
      }

      $mailHandler->sendReservationReadyEmail($email, $name, $bookTitle, $bookAuthor, $readyUntil);
    } catch (Throwable $e) {
      error_log('sendReservationReadyNotification error: ' . $e->getMessage());
    }
  }



  /**
   * @return array{all_time_collections:int, all_time_amount:float}
   */
  public static function getFineCollectionTotals(PDO $db): array
  {
    $totals = [
      'all_time_collections' => 0,
      'all_time_amount' => 0.0,
    ];

    if (!self::tableExists($db, 'fine_collections')) {
      return $totals;
    }

    $sql = "SELECT COUNT(*) AS total_collections, COALESCE(SUM(amount), 0) AS total_amount FROM fine_collections WHERE status = 'collected'";
    $row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (is_array($row)) {
      $totals['all_time_collections'] = (int)($row['total_collections'] ?? 0);
      $totals['all_time_amount'] = (float)($row['total_amount'] ?? 0);
    }

    return $totals;
  }
}
