<?php

/**
 * CirculationRepository
 * Read-only summary queries for borrower and admin dashboards.
 */
class CirculationRepository
{
  /** @var array<string, array<string, bool>> */
  private static $columnCache = [];

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

    $loanSql = "SELECT
      COUNT(CASE WHEN {$loanStatusColumn} IN ({$activeStatuses}) THEN 1 END) AS current_loans,
      COUNT(CASE WHEN {$loanStatusColumn} IN ({$activeStatuses}) AND {$loanDueColumn} >= CURDATE() AND {$loanDueColumn} <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) THEN 1 END) AS due_soon,
      COUNT(CASE WHEN {$loanStatusColumn} IN ({$returnedStatuses}) THEN 1 END) AS loan_history_count
      FROM loans
      WHERE {$loanUserColumn} = :user_id";

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

    $reservationSql = "SELECT COUNT(*) FROM reservations WHERE {$reservationUserColumn} = :user_id AND status IN ('pending', 'ready_for_pickup', 'ready')";
    $reservationStmt = $db->prepare($reservationSql);
    $reservationStmt->execute([':user_id' => $userId]);
    $overview['active_reservations'] = (int)$reservationStmt->fetchColumn();

    if ($loanFineColumn !== null) {
      $loanFineSql = "SELECT COALESCE(SUM(CASE WHEN {$loanStatusColumn} IN ({$activeStatuses}) THEN {$loanFineColumn} ELSE 0 END), 0) FROM loans WHERE {$loanUserColumn} = :user_id";
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
    $overview['active_loans'] = (int)$db->query("SELECT COUNT(*) FROM loans WHERE {$loanStatusColumn} IN ('active', 'overdue', 'borrowed')")->fetchColumn();

    $overview['active_reservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status IN ('pending', 'ready_for_pickup', 'ready')")->fetchColumn();

    if ($overview['active_reservations'] < 0) {
      $overview['active_reservations'] = 0;
    }

    return $overview;
  }
}
