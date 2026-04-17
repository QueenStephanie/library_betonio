<?php

/**
 * LibrarianPortalRepository
 *
 * Lightweight, schema-tolerant read/write operations for librarian pages.
 */
class LibrarianPortalRepository
{
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
