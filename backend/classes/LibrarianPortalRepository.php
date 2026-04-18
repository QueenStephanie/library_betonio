<?php

/**
 * LibrarianPortalRepository
 *
 * Lightweight, schema-tolerant read/write operations for librarian pages.
 */
class LibrarianPortalRepository
{
  const DEFAULT_LOAN_DAYS = 14;

  /** @var array<string, bool> */
  private static $tableCache = [];

  /** @var array<string, array<string, bool>> */
  private static $columnCache = [];

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

  private static function resolveColumn(PDO $db, string $table, array $candidates): ?string
  {
    foreach ($candidates as $candidate) {
      if (self::hasColumn($db, $table, $candidate)) {
        return $candidate;
      }
    }

    return null;
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

    if (!in_array($status, ['ready_for_pickup', 'ready'], true)) {
      return ['ok' => false, 'message' => 'Only ready-for-pickup reservations can be checked out.'];
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
        $summary['stats']['active_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$col} IN ('active', 'overdue', 'borrowed')")->fetchColumn();
      }
      if ($loanStatusColumn !== null && $loanDueColumn !== null) {
        $statusCol = "`$loanStatusColumn`";
        $dueCol = "`$loanDueColumn`";
        $summary['stats']['overdue_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$statusCol} IN ('active', 'overdue', 'borrowed') AND {$dueCol} < NOW()")->fetchColumn();
      }
    }

    if (self::tableExists($db, 'reservations') && self::hasColumn($db, 'reservations', 'status')) {
      $summary['stats']['pending_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
      $summary['stats']['ready_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('ready_for_pickup', 'ready')")->fetchColumn();
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

    $copyJoin = $hasBookCopies ? ' LEFT JOIN book_copies bc ON bc.id = l.' . $copyCol : '';
    $bookJoin = ($hasBookCopies && $hasBooks && self::hasColumn($db, 'book_copies', 'book_id')) ? ' LEFT JOIN books b ON b.id = bc.book_id' : '';
    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = l.' . $userCol : '';

    $sql = "SELECT
      l.id,
      l.{$statusCol} AS loan_status,
      l.checked_out_at,
      l.{$dueCol} AS due_at,
      l.returned_at,
      l.fine_amount,
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
      $row['is_overdue'] = $isOverdue;
      $row['can_checkin'] = in_array($status, ['active', 'overdue', 'borrowed'], true);
    }
    unset($row);

    $response['rows'] = $rows;
    return $response;
  }

  /**
   * @return array{ok: bool, message: string}
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

      $loanQuery = 'SELECT id, `' . $loanStatusColumn . '` AS loan_status';
      if ($loanCopyColumn !== null) {
        $loanQuery .= ', `' . $loanCopyColumn . '` AS book_copy_id';
      }
      if (self::hasColumn($db, 'loans', 'returned_at')) {
        $loanQuery .= ', returned_at';
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

      $updateParts = ["`$loanStatusColumn` = :next_status"];
      $params = [
        ':next_status' => 'returned',
        ':id' => $loanId,
      ];

      if (self::hasColumn($db, 'loans', 'returned_at')) {
        $updateParts[] = 'returned_at = NOW()';
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

      $db->commit();
      return ['ok' => true, 'message' => 'Loan checked in successfully.'];
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log('LibrarianPortalRepository::checkInLoan error: ' . $e->getMessage());
      return ['ok' => false, 'message' => 'Unable to check in loan right now.'];
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

    if (!self::hasColumn($db, 'reservations', 'status')) {
      $response['available'] = false;
      $response['message'] = 'Reservations schema is incompatible with checkout bridge.';
      return $response;
    }

    $limit = max(1, min(300, $limit));
    $hasUsers = self::tableExists($db, 'users') && self::hasColumn($db, 'reservations', 'user_id');
    $hasBooks = self::tableExists($db, 'books') && self::hasColumn($db, 'reservations', 'book_id');
    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id') && self::hasColumn($db, 'book_copies', 'status');

    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = r.user_id' : '';
    $bookJoin = $hasBooks ? ' LEFT JOIN books b ON b.id = r.book_id' : '';

    $availableCopiesExpr = '0';
    if ($hasCopies) {
      $availableCopiesExpr = '(SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = r.book_id AND bc.status = \'available\')';
    }

    $sql = "SELECT
      r.id,
      r.user_id,
      r.book_id,
      r.status,
      r.queued_at,
      r.ready_until,
      {$availableCopiesExpr} AS available_copies,
      " . ($hasUsers ? 'u.first_name' : "''") . " AS borrower_first_name,
      " . ($hasUsers ? 'u.last_name' : "''") . " AS borrower_last_name,
      " . ($hasUsers ? 'u.email' : "''") . " AS borrower_email,
      " . ($hasBooks ? 'b.title' : "''") . " AS book_title,
      " . ($hasBooks ? 'b.author' : "''") . " AS book_author
      FROM reservations r
      {$userJoin}
      {$bookJoin}
      WHERE r.status IN ('ready_for_pickup', 'ready')
      ORDER BY r.queued_at ASC, r.id ASC
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
   * @return array{ok: bool, message: string, loan_id?: int, copy_id?: int}
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
    $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
    $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
    if ($loanUserColumn === null || $loanCopyColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
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

      $insertColumns = ['`' . $loanUserColumn . '`', '`' . $loanCopyColumn . '`', '`' . $loanStatusColumn . '`', '`' . $loanDueColumn . '`'];
      $insertValues = [':user_id', ':book_copy_id', ':loan_status', ':due_at'];
      $insertParams = [
        ':user_id' => $borrowerUserId,
        ':book_copy_id' => $copyId,
        ':loan_status' => 'active',
        ':due_at' => date('Y-m-d H:i:s', strtotime('+' . $loanDays . ' days')),
      ];

      if (self::hasColumn($db, 'loans', 'checked_out_at')) {
        $insertColumns[] = 'checked_out_at';
        $insertValues[] = ':checked_out_at';
        $insertParams[':checked_out_at'] = date('Y-m-d H:i:s');
      }

      if ($reservationId !== null && $reservationId > 0 && self::hasColumn($db, 'loans', 'reservation_id')) {
        $insertColumns[] = 'reservation_id';
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

      $db->commit();
      return [
        'ok' => true,
        'message' => 'Checkout completed successfully.',
        'loan_id' => $loanId,
        'copy_id' => $copyId,
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
   * @return array{ok: bool, message: string, loan_id?: int, reservation_id?: int}
   */
  public static function checkoutReadyReservation(PDO $db, int $reservationId, int $actorUserId, int $loanDays = self::DEFAULT_LOAN_DAYS): array
  {
    $preCheck = self::evaluateReadyReservationCheckoutRules([
      'reservation_id' => $reservationId,
      'reservation_exists' => true,
      'reservation_status' => 'ready_for_pickup',
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

    if (!self::hasColumn($db, 'reservations', 'user_id') || !self::hasColumn($db, 'reservations', 'book_id')) {
      return ['ok' => false, 'message' => 'Reservation checkout bridge requires user_id and book_id columns.'];
    }

    try {
      $db->beginTransaction();

      $reservationStmt = $db->prepare('SELECT id, user_id, book_id, status FROM reservations WHERE id = :id LIMIT 1 FOR UPDATE');
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
      $loanStatusColumn = self::resolveColumn($db, 'loans', ['loan_status', 'status']);
      $loanDueColumn = self::resolveColumn($db, 'loans', ['due_at', 'due_date']);
      if ($loanUserColumn === null || $loanCopyColumn === null || $loanStatusColumn === null || $loanDueColumn === null) {
        $db->rollBack();
        return ['ok' => false, 'message' => 'Loans schema is incompatible with checkout bridge.'];
      }

      $copyUpdateStmt = $db->prepare('UPDATE book_copies SET status = :status WHERE id = :id');
      $copyUpdateStmt->execute([
        ':status' => 'loaned',
        ':id' => $copyId,
      ]);

      $loanDays = max(1, min(60, $loanDays));
      $insertColumns = ['`' . $loanUserColumn . '`', '`' . $loanCopyColumn . '`', '`' . $loanStatusColumn . '`', '`' . $loanDueColumn . '`'];
      $insertValues = [':user_id', ':book_copy_id', ':loan_status', ':due_at'];
      $insertParams = [
        ':user_id' => $borrowerUserId,
        ':book_copy_id' => $copyId,
        ':loan_status' => 'active',
        ':due_at' => date('Y-m-d H:i:s', strtotime('+' . $loanDays . ' days')),
      ];

      if (self::hasColumn($db, 'loans', 'checked_out_at')) {
        $insertColumns[] = 'checked_out_at';
        $insertValues[] = ':checked_out_at';
        $insertParams[':checked_out_at'] = date('Y-m-d H:i:s');
      }

      if (self::hasColumn($db, 'loans', 'reservation_id')) {
        $insertColumns[] = 'reservation_id';
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

      if (self::hasColumn($db, 'reservations', 'picked_up_at')) {
        $reservationUpdateParts[] = 'picked_up_at = NOW()';
      }

      if (self::hasColumn($db, 'reservations', 'ready_until')) {
        $reservationUpdateParts[] = 'ready_until = NULL';
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

      $db->commit();

      return [
        'ok' => true,
        'message' => 'Ready reservation checked out successfully.',
        'loan_id' => $loanId,
        'reservation_id' => $reservationId,
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
   * @return array{rows: list<array<string,mixed>>, available: bool, message: string}
   */
  public static function getBooks(PDO $db, string $search = '', int $limit = 250): array
  {
    $response = [
      'rows' => [],
      'available' => true,
      'message' => '',
    ];

    if (!self::tableExists($db, 'books')) {
      $response['available'] = false;
      $response['message'] = 'Books table is missing. Run circulation migration to enable catalog listing.';
      return $response;
    }

    $search = trim($search);
    $limit = max(1, min(500, $limit));
    $where = '';
    $params = [];

    if ($search !== '') {
      $where = ' WHERE (b.title LIKE :term OR b.author LIKE :term OR COALESCE(b.isbn, \'\') LIKE :term OR COALESCE(b.category, \'\') LIKE :term)';
      $params[':term'] = '%' . $search . '%';
    }

    if (self::hasColumn($db, 'books', 'is_active')) {
      $where .= ($where === '' ? ' WHERE ' : ' AND ') . 'b.is_active = 1';
    }

    $hasCopies = self::tableExists($db, 'book_copies') && self::hasColumn($db, 'book_copies', 'book_id');

    if ($hasCopies && self::hasColumn($db, 'book_copies', 'status')) {
      $sql = "SELECT
        b.id,
        b.isbn,
        b.title,
        b.author,
        b.category,
        b.published_year,
        b.is_active,
        COUNT(bc.id) AS total_copies,
        SUM(CASE WHEN bc.status = 'available' THEN 1 ELSE 0 END) AS available_copies
      FROM books b
      LEFT JOIN book_copies bc ON bc.book_id = b.id
      {$where}
      GROUP BY b.id, b.isbn, b.title, b.author, b.category, b.published_year, b.is_active
      ORDER BY b.title ASC
      LIMIT {$limit}";
    } else {
      $sql = "SELECT
        b.id,
        b.isbn,
        b.title,
        b.author,
        b.category,
        b.published_year,
        b.is_active,
        0 AS total_copies,
        0 AS available_copies
      FROM books b
      {$where}
      ORDER BY b.title ASC
      LIMIT {$limit}";
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $response['rows'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $response;
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

    if (!self::hasColumn($db, 'reservations', 'status')) {
      $response['available'] = false;
      $response['message'] = 'Reservations schema is incompatible with queue management.';
      return $response;
    }

    $limit = max(1, min(500, $limit));
    $hasUsers = self::tableExists($db, 'users') && self::hasColumn($db, 'reservations', 'user_id');
    $hasBooks = self::tableExists($db, 'books') && self::hasColumn($db, 'reservations', 'book_id');

    $userJoin = $hasUsers ? ' LEFT JOIN users u ON u.id = r.user_id' : '';
    $bookJoin = $hasBooks ? ' LEFT JOIN books b ON b.id = r.book_id' : '';

    $sql = "SELECT
      r.id,
      r.user_id,
      r.book_id,
      r.status,
      r.queued_at,
      r.ready_until,
      r.picked_up_at,
      " . ($hasUsers ? 'u.first_name' : "''") . " AS borrower_first_name,
      " . ($hasUsers ? 'u.last_name' : "''") . " AS borrower_last_name,
      " . ($hasUsers ? 'u.email' : "''") . " AS borrower_email,
      " . ($hasBooks ? 'b.title' : "''") . " AS book_title,
      " . ($hasBooks ? 'b.author' : "''") . " AS book_author
    FROM reservations r
    {$userJoin}
    {$bookJoin}
    WHERE r.status IN ('pending', 'ready_for_pickup', 'ready')
    ORDER BY r.queued_at ASC, r.id ASC
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
        $params[':status'] = self::hasColumn($db, 'reservations', 'status') ? 'ready_for_pickup' : 'ready';

        if (self::hasColumn($db, 'reservations', 'ready_until')) {
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

        if (self::hasColumn($db, 'reservations', 'ready_until')) {
          $updateParts[] = 'ready_until = NULL';
        }

        $successMessage = 'Reservation rejected.';
      }

      if ($action === 'cancel') {
        if (!in_array($currentStatus, ['pending', 'ready_for_pickup', 'ready'], true)) {
          $db->rollBack();
          return ['ok' => false, 'message' => 'Only active queue reservations can be cancelled.'];
        }

        $updateParts[] = 'status = :status';
        $params[':status'] = 'cancelled';

        if (self::hasColumn($db, 'reservations', 'ready_until')) {
          $updateParts[] = 'ready_until = NULL';
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
