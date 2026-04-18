<?php

/**
 * CirculationRepository
 * Read-only summary queries for borrower and admin dashboards.
 */
class CirculationRepository
{
  const BORROWER_MAX_ACTIVE_RESERVATIONS = 5;
<<<<<<< ours
<<<<<<< ours
=======
  const BORROWER_MAX_RENEWALS = 2;
  const BORROWER_RENEWAL_EXTENSION_DAYS = 7;
>>>>>>> theirs
=======
  const BORROWER_MAX_RENEWALS = 2;
  const BORROWER_RENEWAL_EXTENSION_DAYS = 7;
>>>>>>> theirs

  /** @var list<string> */
  const ACTIVE_RESERVATION_STATUSES = ['pending', 'ready_for_pickup', 'ready'];

<<<<<<< ours
<<<<<<< ours
=======
  /** @var list<string> */
  const ACTIVE_LOAN_STATUSES = ['active', 'overdue', 'borrowed'];

>>>>>>> theirs
=======
  /** @var list<string> */
  const ACTIVE_LOAN_STATUSES = ['active', 'overdue', 'borrowed'];

>>>>>>> theirs
  /** @var array<string, array<string, bool>> */
  private static $columnCache = [];

  public static function getBorrowerMaxActiveReservations(): int
  {
    return self::BORROWER_MAX_ACTIVE_RESERVATIONS;
  }

<<<<<<< ours
<<<<<<< ours
=======
=======
>>>>>>> theirs
  public static function getBorrowerMaxRenewals(): int
  {
    return self::BORROWER_MAX_RENEWALS;
  }

  public static function getBorrowerRenewalExtensionDays(): int
  {
    return self::BORROWER_RENEWAL_EXTENSION_DAYS;
  }

<<<<<<< ours
>>>>>>> theirs
=======
>>>>>>> theirs
  public static function isActiveReservationStatus(string $status): bool
  {
    return in_array(strtolower(trim($status)), self::ACTIVE_RESERVATION_STATUSES, true);
  }

  public static function canBorrowerCancelReservationStatus(string $status): bool
  {
    return self::isActiveReservationStatus($status);
  }

<<<<<<< ours
<<<<<<< ours
=======
=======
>>>>>>> theirs
  public static function isActiveLoanStatus(string $status): bool
  {
    return in_array(strtolower(trim($status)), self::ACTIVE_LOAN_STATUSES, true);
  }

  public static function isClosedLoanStatus(string $status): bool
  {
    $normalized = strtolower(trim($status));
    if ($normalized === '') {
      return false;
    }

    return !self::isActiveLoanStatus($normalized);
  }

<<<<<<< ours
>>>>>>> theirs
=======
>>>>>>> theirs
  /**
   * Evaluate creation business rules from resolved context values.
   *
   * @param array<string, mixed> $facts
   * @return array{ok: bool, message: string}
   */
  public static function evaluateBorrowerReservationCreationRules(array $facts): array
  {
    $isAuthenticated = !empty($facts['is_authenticated']);
    $bookId = (int)($facts['book_id'] ?? 0);
    $bookExists = !empty($facts['book_exists']);
    $bookIsActive = !empty($facts['book_is_active']);
    $hasDuplicateActiveReservation = !empty($facts['has_duplicate_active_reservation']);
    $activeReservationCount = (int)($facts['active_reservation_count'] ?? 0);
    $maxActiveReservations = (int)($facts['max_active_reservations'] ?? self::BORROWER_MAX_ACTIVE_RESERVATIONS);
    if ($maxActiveReservations <= 0) {
      $maxActiveReservations = self::BORROWER_MAX_ACTIVE_RESERVATIONS;
    }

    if (!$isAuthenticated) {
      return ['ok' => false, 'message' => 'Authentication is required to reserve books.'];
    }

    if ($bookId <= 0) {
      return ['ok' => false, 'message' => 'Please select a valid book to reserve.'];
    }

    if (!$bookExists) {
      return ['ok' => false, 'message' => 'Selected book was not found.'];
    }

    if (!$bookIsActive) {
      return ['ok' => false, 'message' => 'Selected book is not available for reservation.'];
    }

    if ($hasDuplicateActiveReservation) {
      return ['ok' => false, 'message' => 'You already have an active reservation for this title.'];
    }

    if ($activeReservationCount >= $maxActiveReservations) {
      return ['ok' => false, 'message' => 'You reached the maximum number of active reservations (' . $maxActiveReservations . ').'];
    }

    return ['ok' => true, 'message' => 'Reservation can be created.'];
  }

<<<<<<< ours
<<<<<<< ours
=======
=======
>>>>>>> theirs
  /**
   * Evaluate renewal business rules from resolved facts.
   *
   * @param array<string, mixed> $facts
   * @return array{ok: bool, message: string}
   */
  public static function evaluateBorrowerRenewalRules(array $facts): array
  {
    $isAuthenticated = !empty($facts['is_authenticated']);
    $loanId = (int)($facts['loan_id'] ?? 0);
    $loanExists = !empty($facts['loan_exists']);
    $isOwnLoan = !empty($facts['is_own_loan']);
    $isActiveLoan = !empty($facts['is_active_loan']);
    $hasQueueForTitle = !empty($facts['has_active_queue_for_title']);
    $renewalCount = max(0, (int)($facts['renewal_count'] ?? 0));
    $maxRenewals = (int)($facts['max_renewals'] ?? self::BORROWER_MAX_RENEWALS);

    if ($maxRenewals <= 0) {
      $maxRenewals = self::BORROWER_MAX_RENEWALS;
    }

    if (!$isAuthenticated) {
      return ['ok' => false, 'message' => 'Authentication is required to renew loans.'];
    }

    if ($loanId <= 0) {
      return ['ok' => false, 'message' => 'Please select a valid loan to renew.'];
    }

    if (!$loanExists) {
      return ['ok' => false, 'message' => 'Selected loan was not found.'];
    }

    if (!$isOwnLoan) {
      return ['ok' => false, 'message' => 'You can only renew your own active loans.'];
    }

    if (!$isActiveLoan) {
      return ['ok' => false, 'message' => 'Only active loans can be renewed.'];
    }

    if ($hasQueueForTitle) {
      return ['ok' => false, 'message' => 'This loan cannot be renewed because another reservation is waiting for this title.'];
    }

    if ($renewalCount >= $maxRenewals) {
      return ['ok' => false, 'message' => 'Maximum renewals reached for this loan.'];
    }

    return ['ok' => true, 'message' => 'Loan can be renewed.'];
  }

<<<<<<< ours
>>>>>>> theirs
=======
>>>>>>> theirs
  private static function getColumnMap(PDO $db, string $table): array
  {
    if (isset(self::$columnCache[$table])) {
      return self::$columnCache[$table];
    }

    $stmt = $db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $map = [];
    foreach ($columns as $column) {
      $map[strtolower((string)$column)] = true;
    }

    self::$columnCache[$table] = $map;
    return $map;
  }

  private static function hasColumn(PDO $db, string $table, string $column): bool
  {
    $map = self::getColumnMap($db, $table);
    return isset($map[strtolower($column)]);
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

  private static function tableExists(PDO $db, string $table): bool
  {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    $stmt->execute([':table_name' => $table]);
    return (int)$stmt->fetchColumn() > 0;
  }

  /**
   * @param list<string> $statuses
   */
  private static function buildInClause(string $prefix, array $statuses): array
  {
    $placeholders = [];
    $params = [];

    foreach (array_values($statuses) as $index => $status) {
      $key = ':' . $prefix . '_' . $index;
      $placeholders[] = $key;
      $params[$key] = $status;
    }

    return [
      'clause' => implode(', ', $placeholders),
      'params' => $params,
    ];
  }

  public static function getBorrowerCatalog(PDO $db, string $query = '', string $category = '', int $limit = 200): array
  {
    $result = [
      'available' => false,
      'message' => 'Catalog is unavailable.',
      'rows' => [],
      'categories' => [],
    ];

    $bookIdColumn = self::resolveColumn($db, 'books', ['id']);
    $titleColumn = self::resolveColumn($db, 'books', ['title']);
    $authorColumn = self::resolveColumn($db, 'books', ['author']);
    $isbnColumn = self::resolveColumn($db, 'books', ['isbn']);
    $categoryColumn = self::resolveColumn($db, 'books', ['category']);
    $isActiveColumn = self::resolveColumn($db, 'books', ['is_active']);

    if ($bookIdColumn === null || $titleColumn === null || $authorColumn === null) {
      throw new RuntimeException('Incompatible books table schema.');
    }

    $safeLimit = max(1, min(300, $limit));
    $where = [];
    $params = [];

    if ($isActiveColumn !== null) {
      $where[] = '`' . $isActiveColumn . '` = 1';
    }

    $normalizedQuery = trim($query);
    if ($normalizedQuery !== '') {
      $params[':q'] = '%' . $normalizedQuery . '%';
      $searchParts = ['b.`' . $titleColumn . '` LIKE :q', 'b.`' . $authorColumn . '` LIKE :q'];
      if ($isbnColumn !== null) {
        $searchParts[] = 'b.`' . $isbnColumn . '` LIKE :q';
      }
      $where[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $normalizedCategory = trim($category);
    if ($normalizedCategory !== '' && $categoryColumn !== null) {
      $where[] = 'b.`' . $categoryColumn . '` = :category';
      $params[':category'] = $normalizedCategory;
    }

    $whereSql = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);

    $copyJoin = '';
    $availableExpr = '0';
    $totalExpr = '0';

    if (
      self::tableExists($db, 'book_copies')
      && self::hasColumn($db, 'book_copies', 'book_id')
      && self::hasColumn($db, 'book_copies', 'status')
    ) {
      $copyJoin = ' LEFT JOIN (
        SELECT
          book_id,
          SUM(CASE WHEN status = \'available\' THEN 1 ELSE 0 END) AS available_copies,
          COUNT(*) AS total_copies
        FROM book_copies
        GROUP BY book_id
      ) copy_stats ON copy_stats.book_id = b.`' . $bookIdColumn . '`';
      $availableExpr = 'COALESCE(copy_stats.available_copies, 0)';
      $totalExpr = 'COALESCE(copy_stats.total_copies, 0)';
    } else {
      $availableCopiesColumn = self::resolveColumn($db, 'books', ['available_copies', 'copies_available']);
      $totalCopiesColumn = self::resolveColumn($db, 'books', ['total_copies', 'copy_count', 'copies_total']);

      if ($availableCopiesColumn !== null) {
        $availableExpr = 'COALESCE(b.`' . $availableCopiesColumn . '`, 0)';
      }

      if ($totalCopiesColumn !== null) {
        $totalExpr = 'COALESCE(b.`' . $totalCopiesColumn . '`, 0)';
      } else {
        $totalExpr = $availableExpr;
      }
    }

    $categorySelect = $categoryColumn !== null
      ? 'COALESCE(NULLIF(TRIM(b.`' . $categoryColumn . '`), \'\'), \'Uncategorized\')'
      : '\'Uncategorized\'';

    $isbnSelect = $isbnColumn !== null ? 'b.`' . $isbnColumn . '`' : 'NULL';

    $sql = 'SELECT
      b.`' . $bookIdColumn . '` AS id,
      b.`' . $titleColumn . '` AS title,
      b.`' . $authorColumn . '` AS author,
      ' . $isbnSelect . ' AS isbn,
      ' . $categorySelect . ' AS category,
      ' . $availableExpr . ' AS available_copies,
      ' . $totalExpr . ' AS total_copies
      FROM books b' . $copyJoin . $whereSql . '
      ORDER BY b.`' . $titleColumn . '` ASC
      LIMIT ' . $safeLimit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $categoryWhere = [];
    if ($isActiveColumn !== null) {
      $categoryWhere[] = '`' . $isActiveColumn . '` = 1';
    }
    if ($categoryColumn !== null) {
      $categoryWhere[] = 'NULLIF(TRIM(`' . $categoryColumn . '`), \'\') IS NOT NULL';
      $categorySql = 'SELECT DISTINCT `' . $categoryColumn . '` AS category FROM books'
        . (empty($categoryWhere) ? '' : ' WHERE ' . implode(' AND ', $categoryWhere))
        . ' ORDER BY `' . $categoryColumn . '` ASC';
      $categoryStmt = $db->query($categorySql);
      $result['categories'] = $categoryStmt !== false ? ($categoryStmt->fetchAll(PDO::FETCH_COLUMN) ?: []) : [];
    }

    $result['rows'] = $rows;
    $result['available'] = true;
    $result['message'] = '';

    return $result;
  }

  public static function createBorrowerReservation(PDO $db, int $userId, int $bookId, int $maxActiveReservations = self::BORROWER_MAX_ACTIVE_RESERVATIONS): array
  {
    $maxActiveReservations = max(1, $maxActiveReservations);

    $preCheck = self::evaluateBorrowerReservationCreationRules([
      'is_authenticated' => $userId > 0,
      'book_id' => $bookId,
      'book_exists' => true,
      'book_is_active' => true,
      'has_duplicate_active_reservation' => false,
      'active_reservation_count' => 0,
      'max_active_reservations' => $maxActiveReservations,
    ]);

    if (!$preCheck['ok']) {
      return $preCheck;
    }

    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    $reservationStatusColumn = self::resolveColumn($db, 'reservations', ['status']);
    $reservationQueuedColumn = self::resolveColumn($db, 'reservations', ['queued_at', 'created_at']);
    if ($reservationUserColumn === null || $reservationBookColumn === null || $reservationStatusColumn === null) {
      throw new RuntimeException('Incompatible reservations table schema.');
    }

    $bookIdColumn = self::resolveColumn($db, 'books', ['id']);
    $bookActiveColumn = self::resolveColumn($db, 'books', ['is_active']);
    if ($bookIdColumn === null) {
      throw new RuntimeException('Incompatible books table schema.');
    }

    $statusIn = self::buildInClause('active_status', self::ACTIVE_RESERVATION_STATUSES);

    try {
      $db->beginTransaction();

      $bookCols = ['`' . $bookIdColumn . '` AS id'];
      if ($bookActiveColumn !== null) {
        $bookCols[] = '`' . $bookActiveColumn . '` AS is_active';
      }

      $bookStmt = $db->prepare(
        'SELECT ' . implode(', ', $bookCols) . ' FROM books WHERE `' . $bookIdColumn . '` = :book_id LIMIT 1 FOR UPDATE'
      );
      $bookStmt->execute([':book_id' => $bookId]);
      $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

      $bookExists = is_array($book);
      $bookIsActive = $bookExists;
      if ($bookExists && $bookActiveColumn !== null) {
        $bookIsActive = (int)($book['is_active'] ?? 0) === 1;
      }

      $duplicateSql = 'SELECT COUNT(*) FROM reservations
        WHERE `' . $reservationUserColumn . '` = :user_id
          AND `' . $reservationBookColumn . '` = :book_id
          AND `' . $reservationStatusColumn . '` IN (' . $statusIn['clause'] . ')
        FOR UPDATE';
      $duplicateStmt = $db->prepare($duplicateSql);
      $duplicateParams = array_merge([
        ':user_id' => $userId,
        ':book_id' => $bookId,
      ], $statusIn['params']);
      $duplicateStmt->execute($duplicateParams);
      $hasDuplicate = (int)$duplicateStmt->fetchColumn() > 0;

      $countSql = 'SELECT COUNT(*) FROM reservations
        WHERE `' . $reservationUserColumn . '` = :user_id
          AND `' . $reservationStatusColumn . '` IN (' . $statusIn['clause'] . ')
        FOR UPDATE';
      $countStmt = $db->prepare($countSql);
      $countParams = array_merge([
        ':user_id' => $userId,
      ], $statusIn['params']);
      $countStmt->execute($countParams);
      $activeCount = (int)$countStmt->fetchColumn();

      $ruleResult = self::evaluateBorrowerReservationCreationRules([
        'is_authenticated' => true,
        'book_id' => $bookId,
        'book_exists' => $bookExists,
        'book_is_active' => $bookIsActive,
        'has_duplicate_active_reservation' => $hasDuplicate,
        'active_reservation_count' => $activeCount,
        'max_active_reservations' => $maxActiveReservations,
      ]);

      if (!$ruleResult['ok']) {
        $db->rollBack();
        return $ruleResult;
      }

      $queueSql = 'SELECT COUNT(*) FROM reservations
        WHERE `' . $reservationBookColumn . '` = :book_id
          AND `' . $reservationStatusColumn . '` IN (' . $statusIn['clause'] . ')
        FOR UPDATE';
      $queueStmt = $db->prepare($queueSql);
      $queueParams = array_merge([
        ':book_id' => $bookId,
      ], $statusIn['params']);
      $queueStmt->execute($queueParams);
      $queuePosition = ((int)$queueStmt->fetchColumn()) + 1;

      $insertColumns = ['`' . $reservationUserColumn . '`', '`' . $reservationBookColumn . '`', '`' . $reservationStatusColumn . '`'];
      $insertValues = [':user_id', ':book_id', ':status'];
      $insertParams = [
        ':user_id' => $userId,
        ':book_id' => $bookId,
        ':status' => 'pending',
      ];

      if ($reservationQueuedColumn !== null) {
        $insertColumns[] = '`' . $reservationQueuedColumn . '`';
        $insertValues[] = ':queued_at';
        $insertParams[':queued_at'] = date('Y-m-d H:i:s');
      }

      $insertSql = 'INSERT INTO reservations (' . implode(', ', $insertColumns) . ')
        VALUES (' . implode(', ', $insertValues) . ')';
      $insertStmt = $db->prepare($insertSql);
      $insertStmt->execute($insertParams);

      $reservationId = (int)$db->lastInsertId();
      $db->commit();

      return [
        'ok' => true,
        'message' => 'Reservation created successfully.',
        'reservation_id' => $reservationId,
        'queue_position' => $queuePosition,
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      error_log('createBorrowerReservation error: ' . $e->getMessage());
      return [
        'ok' => false,
        'message' => 'Unable to create reservation right now. Please try again.',
      ];
    }
  }

  public static function getBorrowerActiveReservations(PDO $db, int $userId, int $limit = 120): array
  {
    $result = [
      'available' => false,
      'message' => 'Reservations are unavailable.',
      'rows' => [],
    ];

    if ($userId <= 0) {
      return $result;
    }

    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationBookColumn = self::resolveColumn($db, 'reservations', ['book_id']);
    $reservationStatusColumn = self::resolveColumn($db, 'reservations', ['status']);
    $reservationQueuedColumn = self::resolveColumn($db, 'reservations', ['queued_at', 'created_at']);
    $reservationReadyUntilColumn = self::resolveColumn($db, 'reservations', ['ready_until']);
    if ($reservationUserColumn === null || $reservationBookColumn === null || $reservationStatusColumn === null || $reservationQueuedColumn === null) {
      throw new RuntimeException('Incompatible reservations table schema.');
    }

    $bookIdColumn = self::resolveColumn($db, 'books', ['id']);
    $bookTitleColumn = self::resolveColumn($db, 'books', ['title']);
    $bookAuthorColumn = self::resolveColumn($db, 'books', ['author']);
    if ($bookIdColumn === null || $bookTitleColumn === null || $bookAuthorColumn === null) {
      throw new RuntimeException('Incompatible books table schema.');
    }

    $safeLimit = max(1, min(300, $limit));
    $statusIn = self::buildInClause('active_status', self::ACTIVE_RESERVATION_STATUSES);
    $readyUntilExpr = $reservationReadyUntilColumn !== null ? 'r.`' . $reservationReadyUntilColumn . '`' : 'NULL';

    $sql = 'SELECT
      r.id,
      r.`' . $reservationStatusColumn . '` AS status,
      r.`' . $reservationQueuedColumn . '` AS queued_at,
      ' . $readyUntilExpr . ' AS ready_until,
      b.`' . $bookIdColumn . '` AS book_id,
      b.`' . $bookTitleColumn . '` AS book_title,
      b.`' . $bookAuthorColumn . '` AS book_author
      FROM reservations r
      INNER JOIN books b ON b.`' . $bookIdColumn . '` = r.`' . $reservationBookColumn . '`
      WHERE r.`' . $reservationUserColumn . '` = :user_id
        AND r.`' . $reservationStatusColumn . '` IN (' . $statusIn['clause'] . ')
      ORDER BY r.`' . $reservationQueuedColumn . '` ASC
      LIMIT ' . $safeLimit;

    $stmt = $db->prepare($sql);
    $params = array_merge([':user_id' => $userId], $statusIn['params']);
    $stmt->execute($params);

    $result['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $result['available'] = true;
    $result['message'] = '';
    return $result;
  }

  public static function cancelBorrowerReservation(PDO $db, int $userId, int $reservationId): array
  {
    if ($userId <= 0) {
      return ['ok' => false, 'message' => 'Authentication is required to cancel reservations.'];
    }

    if ($reservationId <= 0) {
      return ['ok' => false, 'message' => 'Please select a valid reservation.'];
    }

    $reservationIdColumn = self::resolveColumn($db, 'reservations', ['id']);
    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    $reservationStatusColumn = self::resolveColumn($db, 'reservations', ['status']);
    if ($reservationIdColumn === null || $reservationUserColumn === null || $reservationStatusColumn === null) {
      throw new RuntimeException('Incompatible reservations table schema.');
    }

    try {
      $db->beginTransaction();

      $selectSql = 'SELECT
        `' . $reservationIdColumn . '` AS id,
        `' . $reservationUserColumn . '` AS user_id,
        `' . $reservationStatusColumn . '` AS status
        FROM reservations
        WHERE `' . $reservationIdColumn . '` = :reservation_id
        LIMIT 1
        FOR UPDATE';
      $selectStmt = $db->prepare($selectSql);
      $selectStmt->execute([':reservation_id' => $reservationId]);
      $reservation = $selectStmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($reservation)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Reservation was not found.'];
      }

      if ((int)($reservation['user_id'] ?? 0) !== $userId) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'You can only cancel your own reservations.'];
      }

      $status = (string)($reservation['status'] ?? '');
      if (!self::canBorrowerCancelReservationStatus($status)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Only active reservations can be cancelled.'];
      }

      $updateStmt = $db->prepare(
        'UPDATE reservations SET `' . $reservationStatusColumn . '` = :status WHERE `' . $reservationIdColumn . '` = :reservation_id'
      );
      $updateStmt->execute([
        ':status' => 'cancelled',
        ':reservation_id' => $reservationId,
      ]);

      $db->commit();
      return [
        'ok' => true,
        'message' => 'Reservation cancelled successfully.',
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      error_log('cancelBorrowerReservation error: ' . $e->getMessage());
      return [
        'ok' => false,
        'message' => 'Unable to cancel reservation right now. Please try again.',
      ];
    }
  }

<<<<<<< ours
<<<<<<< ours
=======
=======
>>>>>>> theirs
  public static function getBorrowerActiveLoans(PDO $db, int $userId, int $limit = 120): array
  {
    $result = [
      'available' => false,
      'message' => 'Loans are unavailable.',
      'rows' => [],
    ];

    if ($userId <= 0) {
      return $result;
    }

    $loanIdColumn = self::resolveColumn($db, 'loans', ['id']);
    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanCheckedOutColumn = self::resolveColumn($db, 'loans', ['checked_out_at', 'created_at']);
    $loanRenewalCountColumn = self::resolveColumn($db, 'loans', ['renewal_count']);
    $loanFineColumn = self::resolveColumn($db, 'loans', ['fine_amount']);
    $loanReservationColumn = self::resolveColumn($db, 'loans', ['reservation_id']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);

    if ($loanIdColumn === null || $loanUserColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
      throw new RuntimeException('Incompatible loans table schema.');
    }

    $bookTitleExpr = "''";
    $bookAuthorExpr = "''";
    $copyJoin = '';
    $bookJoin = '';

    if (
      $loanCopyColumn !== null
      && self::tableExists($db, 'book_copies')
      && self::hasColumn($db, 'book_copies', 'id')
      && self::hasColumn($db, 'book_copies', 'book_id')
      && self::tableExists($db, 'books')
      && self::hasColumn($db, 'books', 'id')
    ) {
      $copyJoin = ' LEFT JOIN book_copies bc ON bc.id = l.`' . $loanCopyColumn . '`';
      $bookJoin = ' LEFT JOIN books b ON b.id = bc.book_id';

      if (self::hasColumn($db, 'books', 'title')) {
        $bookTitleExpr = 'b.title';
      }
      if (self::hasColumn($db, 'books', 'author')) {
        $bookAuthorExpr = 'b.author';
      }
    }

    $renewalExpr = $loanRenewalCountColumn !== null ? 'l.`' . $loanRenewalCountColumn . '`' : '0';
    $fineExpr = $loanFineColumn !== null ? 'l.`' . $loanFineColumn . '`' : '0';
    $checkedOutExpr = $loanCheckedOutColumn !== null ? 'l.`' . $loanCheckedOutColumn . '`' : 'NULL';
    $reservationExpr = $loanReservationColumn !== null ? 'l.`' . $loanReservationColumn . '`' : 'NULL';

    $safeLimit = max(1, min(300, $limit));
    $statusIn = self::buildInClause('loan_status', self::ACTIVE_LOAN_STATUSES);

    $sql = 'SELECT
      l.`' . $loanIdColumn . '` AS id,
      l.`' . $loanStatusColumn . '` AS loan_status,
      l.`' . $loanDueColumn . '` AS due_at,
      ' . $checkedOutExpr . ' AS checked_out_at,
      ' . $renewalExpr . ' AS renewal_count,
      ' . $fineExpr . ' AS fine_amount,
      ' . $reservationExpr . ' AS reservation_id,
      ' . $bookTitleExpr . ' AS book_title,
      ' . $bookAuthorExpr . ' AS book_author
      FROM loans l' . $copyJoin . $bookJoin . '
      WHERE l.`' . $loanUserColumn . '` = :user_id
        AND l.`' . $loanStatusColumn . '` IN (' . $statusIn['clause'] . ')
      ORDER BY l.`' . $loanDueColumn . '` ASC, l.`' . $loanIdColumn . '` ASC
      LIMIT ' . $safeLimit;

    $stmt = $db->prepare($sql);
    $params = array_merge([':user_id' => $userId], $statusIn['params']);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
      $status = strtolower(trim((string)($row['loan_status'] ?? '')));
      $renewalCount = max(0, (int)($row['renewal_count'] ?? 0));
      $row['can_renew'] = self::isActiveLoanStatus($status) && $renewalCount < self::BORROWER_MAX_RENEWALS;
      $row['renewals_remaining'] = max(0, self::BORROWER_MAX_RENEWALS - $renewalCount);
    }
    unset($row);

    $result['rows'] = $rows;
    $result['available'] = true;
    $result['message'] = '';
    return $result;
  }

  public static function getBorrowerLoanHistory(PDO $db, int $userId, int $limit = 180): array
  {
    $result = [
      'available' => false,
      'message' => 'Loan history is unavailable.',
      'rows' => [],
    ];

    if ($userId <= 0) {
      return $result;
    }

    $loanIdColumn = self::resolveColumn($db, 'loans', ['id']);
    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanCheckedOutColumn = self::resolveColumn($db, 'loans', ['checked_out_at', 'created_at']);
    $loanReturnedColumn = self::resolveColumn($db, 'loans', ['returned_at']);
    $loanFineColumn = self::resolveColumn($db, 'loans', ['fine_amount']);
    $loanRenewalCountColumn = self::resolveColumn($db, 'loans', ['renewal_count']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
    $loanReservationColumn = self::resolveColumn($db, 'loans', ['reservation_id']);

    if ($loanIdColumn === null || $loanUserColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
      throw new RuntimeException('Incompatible loans table schema.');
    }

    $bookTitleExpr = "''";
    $bookAuthorExpr = "''";
    $copyJoin = '';
    $bookJoin = '';

    if (
      $loanCopyColumn !== null
      && self::tableExists($db, 'book_copies')
      && self::hasColumn($db, 'book_copies', 'id')
      && self::hasColumn($db, 'book_copies', 'book_id')
      && self::tableExists($db, 'books')
      && self::hasColumn($db, 'books', 'id')
    ) {
      $copyJoin = ' LEFT JOIN book_copies bc ON bc.id = l.`' . $loanCopyColumn . '`';
      $bookJoin = ' LEFT JOIN books b ON b.id = bc.book_id';

      if (self::hasColumn($db, 'books', 'title')) {
        $bookTitleExpr = 'b.title';
      }
      if (self::hasColumn($db, 'books', 'author')) {
        $bookAuthorExpr = 'b.author';
      }
    }

    $checkedOutExpr = $loanCheckedOutColumn !== null ? 'l.`' . $loanCheckedOutColumn . '`' : 'NULL';
    $returnedExpr = $loanReturnedColumn !== null ? 'l.`' . $loanReturnedColumn . '`' : 'NULL';
    $fineExpr = $loanFineColumn !== null ? 'l.`' . $loanFineColumn . '`' : '0';
    $renewalExpr = $loanRenewalCountColumn !== null ? 'l.`' . $loanRenewalCountColumn . '`' : '0';
    $reservationExpr = $loanReservationColumn !== null ? 'l.`' . $loanReservationColumn . '`' : 'NULL';

    $safeLimit = max(1, min(400, $limit));
    $closedStatuses = ['returned', 'lost', 'void', 'closed', 'cancelled'];
    $statusIn = self::buildInClause('closed_status', $closedStatuses);

    $where = 'l.`' . $loanUserColumn . '` = :user_id AND l.`' . $loanStatusColumn . '` IN (' . $statusIn['clause'] . ')';
    if ($loanReturnedColumn !== null) {
      $where = 'l.`' . $loanUserColumn . '` = :user_id AND (l.`' . $loanStatusColumn . '` IN (' . $statusIn['clause'] . ') OR l.`' . $loanReturnedColumn . '` IS NOT NULL)';
    }

    $orderExpr = $loanReturnedColumn !== null
      ? 'COALESCE(l.`' . $loanReturnedColumn . '`, l.`' . $loanDueColumn . '`) DESC'
      : 'l.`' . $loanDueColumn . '` DESC';

    $sql = 'SELECT
      l.`' . $loanIdColumn . '` AS id,
      l.`' . $loanStatusColumn . '` AS loan_status,
      l.`' . $loanDueColumn . '` AS due_at,
      ' . $checkedOutExpr . ' AS checked_out_at,
      ' . $returnedExpr . ' AS returned_at,
      ' . $fineExpr . ' AS fine_amount,
      ' . $renewalExpr . ' AS renewal_count,
      ' . $reservationExpr . ' AS reservation_id,
      ' . $bookTitleExpr . ' AS book_title,
      ' . $bookAuthorExpr . ' AS book_author
      FROM loans l' . $copyJoin . $bookJoin . '
      WHERE ' . $where . '
      ORDER BY ' . $orderExpr . ', l.`' . $loanIdColumn . '` DESC
      LIMIT ' . $safeLimit;

    $stmt = $db->prepare($sql);
    $params = array_merge([':user_id' => $userId], $statusIn['params']);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) {
      $status = strtolower(trim((string)($row['loan_status'] ?? '')));
      $row['is_closed'] = self::isClosedLoanStatus($status) || !empty($row['returned_at']);
    }
    unset($row);

    $result['rows'] = $rows;
    $result['available'] = true;
    $result['message'] = '';
    return $result;
  }

  public static function renewBorrowerLoan(
    PDO $db,
    int $userId,
    int $loanId,
    int $maxRenewals = self::BORROWER_MAX_RENEWALS,
    int $extensionDays = self::BORROWER_RENEWAL_EXTENSION_DAYS
  ): array {
    $maxRenewals = max(1, $maxRenewals);
    $extensionDays = max(1, $extensionDays);

    $preCheck = self::evaluateBorrowerRenewalRules([
      'is_authenticated' => $userId > 0,
      'loan_id' => $loanId,
      'loan_exists' => true,
      'is_own_loan' => true,
      'is_active_loan' => true,
      'has_active_queue_for_title' => false,
      'renewal_count' => 0,
      'max_renewals' => $maxRenewals,
    ]);
    if (!$preCheck['ok']) {
      return $preCheck;
    }

    $loanIdColumn = self::resolveColumn($db, 'loans', ['id']);
    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanRenewalCountColumn = self::resolveColumn($db, 'loans', ['renewal_count']);
    $loanCopyColumn = self::resolveColumn($db, 'loans', ['book_copy_id']);
    $loanReservationColumn = self::resolveColumn($db, 'loans', ['reservation_id']);
    $loanBookColumn = self::resolveColumn($db, 'loans', ['book_id']);

    if ($loanIdColumn === null || $loanUserColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
      throw new RuntimeException('Incompatible loans table schema.');
    }

    if ($loanRenewalCountColumn === null) {
      return ['ok' => false, 'message' => 'Renewal is unavailable because renewal tracking is not configured.'];
    }

    try {
      $db->beginTransaction();

      $selectColumns = [
        'l.`' . $loanIdColumn . '` AS id',
        'l.`' . $loanUserColumn . '` AS user_id',
        'l.`' . $loanStatusColumn . '` AS loan_status',
        'l.`' . $loanDueColumn . '` AS due_at',
        'l.`' . $loanRenewalCountColumn . '` AS renewal_count',
      ];

      if ($loanCopyColumn !== null) {
        $selectColumns[] = 'l.`' . $loanCopyColumn . '` AS book_copy_id';
      }
      if ($loanReservationColumn !== null) {
        $selectColumns[] = 'l.`' . $loanReservationColumn . '` AS reservation_id';
      }
      if ($loanBookColumn !== null) {
        $selectColumns[] = 'l.`' . $loanBookColumn . '` AS book_id';
      }

      $loanStmt = $db->prepare(
        'SELECT ' . implode(', ', $selectColumns) . ' FROM loans l WHERE l.`' . $loanIdColumn . '` = :loan_id LIMIT 1 FOR UPDATE'
      );
      $loanStmt->execute([':loan_id' => $loanId]);
      $loan = $loanStmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($loan)) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Selected loan was not found.'];
      }

      $loanOwnerId = (int)($loan['user_id'] ?? 0);
      $loanStatus = strtolower(trim((string)($loan['loan_status'] ?? '')));
      $renewalCount = max(0, (int)($loan['renewal_count'] ?? 0));

      $bookId = 0;
      if (isset($loan['book_id'])) {
        $bookId = (int)$loan['book_id'];
      }
      if ($bookId <= 0 && isset($loan['book_copy_id']) && self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id')) {
        $copyStmt = $db->prepare('SELECT book_id FROM book_copies WHERE id = :copy_id LIMIT 1 FOR UPDATE');
        $copyStmt->execute([':copy_id' => (int)$loan['book_copy_id']]);
        $bookId = (int)$copyStmt->fetchColumn();
      }

      $hasActiveQueue = false;
      if (
        $bookId > 0
        && self::tableExists($db, 'reservations')
        && self::hasColumn($db, 'reservations', 'book_id')
        && self::hasColumn($db, 'reservations', 'status')
      ) {
        $reservationStatusIn = self::buildInClause('reservation_status', self::ACTIVE_RESERVATION_STATUSES);
        $queueSql = 'SELECT COUNT(*) FROM reservations
          WHERE book_id = :book_id
            AND status IN (' . $reservationStatusIn['clause'] . ')';
        $queueParams = array_merge([':book_id' => $bookId], $reservationStatusIn['params']);

        if ($loanReservationColumn !== null && isset($loan['reservation_id']) && (int)$loan['reservation_id'] > 0) {
          $queueSql .= ' AND id <> :own_reservation_id';
          $queueParams[':own_reservation_id'] = (int)$loan['reservation_id'];
        }

        $queueSql .= ' FOR UPDATE';
        $queueStmt = $db->prepare($queueSql);
        $queueStmt->execute($queueParams);
        $hasActiveQueue = (int)$queueStmt->fetchColumn() > 0;
      }

      $ruleResult = self::evaluateBorrowerRenewalRules([
        'is_authenticated' => true,
        'loan_id' => $loanId,
        'loan_exists' => true,
        'is_own_loan' => $loanOwnerId === $userId,
        'is_active_loan' => self::isActiveLoanStatus($loanStatus),
        'has_active_queue_for_title' => $hasActiveQueue,
        'renewal_count' => $renewalCount,
        'max_renewals' => $maxRenewals,
      ]);

      if (!$ruleResult['ok']) {
        $db->rollBack();
        return $ruleResult;
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
          $eventParams[':actor_user_id'] = $userId;
        }

        if (self::hasColumn($db, 'loan_events', 'notes')) {
          $eventColumns[] = 'notes';
          $eventValues[] = ':notes';
          $eventParams[':notes'] = 'Borrower renewal via self-service history page';
        }

        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      $db->commit();

      return [
        'ok' => true,
        'message' => 'Loan renewed successfully.',
        'loan_id' => $loanId,
        'renewal_count' => $renewalCount + 1,
        'due_at' => $newDueAt,
      ];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      error_log('renewBorrowerLoan error: ' . $e->getMessage());
      return [
        'ok' => false,
        'message' => 'Unable to renew loan right now. Please try again.',
      ];
    }
  }

<<<<<<< ours
>>>>>>> theirs
=======
>>>>>>> theirs
  public static function getBorrowerOverview(PDO $db, int $userId): array
  {
    $overview = [
      'current_loans' => 0,
      'due_soon' => 0,
      'active_reservations' => 0,
      'outstanding_fines' => 0.0,
      'loan_history_count' => 0,
    ];

    if ($userId <= 0) {
      return $overview;
    }

    $loanUserColumn = self::resolveColumn($db, 'loans', ['user_id', 'borrower_user_id']);
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    $loanFineColumn = self::resolveColumn($db, 'loans', ['fine_amount']);

    if ($loanUserColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
      throw new RuntimeException('Incompatible loans table schema.');
    }

    $activeStatuses = "'active', 'overdue', 'borrowed'";
    $returnedStatuses = "'returned'";

    $userCol = "`$loanUserColumn`";
    $statusCol = "`$loanStatusColumn`";
    $dueCol = "`$loanDueColumn`";

    $loanSql = "SELECT
      COUNT(CASE WHEN {$statusCol} IN ({$activeStatuses}) THEN 1 END) AS current_loans,
      COUNT(CASE WHEN {$statusCol} IN ({$activeStatuses}) AND {$dueCol} >= CURDATE() AND {$dueCol} < DATE_ADD(CURDATE(), INTERVAL 4 DAY) THEN 1 END) AS due_soon,
      COUNT(CASE WHEN {$statusCol} IN ({$returnedStatuses}) THEN 1 END) AS loan_history_count
      FROM loans
      WHERE {$userCol} = :user_id";

    $loanStmt = $db->prepare($loanSql);
    $loanStmt->execute([':user_id' => $userId]);
    $loanRow = $loanStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $overview['current_loans'] = (int)($loanRow['current_loans'] ?? 0);
    $overview['due_soon'] = (int)($loanRow['due_soon'] ?? 0);
    $overview['loan_history_count'] = (int)($loanRow['loan_history_count'] ?? 0);

    $reservationUserColumn = self::resolveColumn($db, 'reservations', ['user_id', 'borrower_user_id']);
    if ($reservationUserColumn === null) {
      throw new RuntimeException('Incompatible reservations table schema.');
    }

    $reservationUserCol = "`$reservationUserColumn`";
    $reservationSql = "SELECT COUNT(*) FROM reservations WHERE {$reservationUserCol} = :user_id AND status IN ('pending', 'ready_for_pickup', 'ready')";
    $reservationStmt = $db->prepare($reservationSql);
    $reservationStmt->execute([':user_id' => $userId]);
    $overview['active_reservations'] = (int)$reservationStmt->fetchColumn();

    if ($loanFineColumn !== null) {
      $fineCol = "`$loanFineColumn`";
      $loanFineSql = "SELECT COALESCE(SUM(CASE WHEN {$statusCol} IN ({$activeStatuses}) THEN {$fineCol} ELSE 0 END), 0) FROM loans WHERE {$userCol} = :user_id";
      $loanFineStmt = $db->prepare($loanFineSql);
      $loanFineStmt->execute([':user_id' => $userId]);
      $overview['outstanding_fines'] = (float)$loanFineStmt->fetchColumn();
    } elseif (self::tableExists($db, 'fine_assessments') && self::hasColumn($db, 'fine_assessments', 'borrower_user_id')) {
      $fineSql = "SELECT COALESCE(SUM(amount), 0) FROM fine_assessments WHERE borrower_user_id = :user_id AND status NOT IN ('paid', 'voided')";
      $fineStmt = $db->prepare($fineSql);
      $fineStmt->execute([':user_id' => $userId]);
      $overview['outstanding_fines'] = (float)$fineStmt->fetchColumn();
    }

    return $overview;
  }

  public static function getAdminOverview(PDO $db): array
  {
    $overview = [
      'catalog_titles' => 0,
      'available_copies' => 0,
      'active_loans' => 0,
      'active_reservations' => 0,
    ];

    $booksWhere = self::hasColumn($db, 'books', 'is_active') ? ' WHERE is_active = 1' : '';
    $overview['catalog_titles'] = (int)$db->query('SELECT COUNT(*) FROM books' . $booksWhere)->fetchColumn();

    if (self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'status')) {
      $overview['available_copies'] = (int)$db->query("SELECT COUNT(*) FROM book_copies WHERE status = 'available'")->fetchColumn();
    } elseif (self::hasColumn($db, 'books', 'available_copies')) {
      $overview['available_copies'] = (int)$db->query('SELECT COALESCE(SUM(available_copies), 0) FROM books')->fetchColumn();
    }

    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    if ($loanStatusColumn === null) {
      throw new RuntimeException('Incompatible loans table schema.');
    }
    $statusCol = "`$loanStatusColumn`";
    $overview['active_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$statusCol} IN ('active', 'overdue', 'borrowed')")->fetchColumn();

    $overview['active_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('pending', 'ready_for_pickup', 'ready')")->fetchColumn();

    if ($overview['active_reservations'] < 0) {
      $overview['active_reservations'] = 0;
    }

    return $overview;
  }
}
