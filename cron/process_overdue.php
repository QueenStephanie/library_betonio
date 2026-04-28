<?php
declare(strict_types=1);

/**
 * Cron: Mark overdue loans in the database.
 *
 * Run daily: php cron/process_overdue.php
 * 
 * Changes loan status from 'active'/'borrowed' → 'overdue'
 * when due_at is past. Logs loan_events for each transition.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$start = microtime(true);

try {
  if (!isset($db) || !$db instanceof PDO) {
    fwrite(STDERR, "ERROR: No database connection.\n");
    exit(1);
  }

  // Resolve column names (schema-tolerant like rest of codebase)
  $loanStatusColumn = resolveLoanColumn($db, 'loans', ['loan_status', 'status']);
  $loanDueColumn = resolveLoanColumn($db, 'loans', ['due_at', 'due_date']);
  $loanIdColumn = resolveLoanColumn($db, 'loans', ['id']);
  $hasLoanEvents = tableExists($db, 'loan_events');
  $hasEventLoanId = $hasLoanEvents && hasColumn($db, 'loan_events', 'loan_id');
  $hasEventType = $hasLoanEvents && hasColumn($db, 'loan_events', 'event_type');

  if ($loanStatusColumn === null || $loanDueColumn === null || $loanIdColumn === null) {
    fwrite(STDERR, "ERROR: Loans table schema incompatible.\n");
    exit(1);
  }

  $statusCol = "`$loanStatusColumn`";
  $dueCol = "`$loanDueColumn`";
  $idCol = "`$loanIdColumn`";

  // Find loans past due that aren't already overdue or returned
  $selectSql = "SELECT {$idCol} AS id, {$dueCol} AS due_at
    FROM loans
    WHERE {$statusCol} IN ('active', 'borrowed')
      AND {$dueCol} < NOW()
    LIMIT 500";

  $rows = $db->query($selectSql)->fetchAll(PDO::FETCH_ASSOC);

  if (empty($rows)) {
    fwrite(STDOUT, "No overdue loans found.\n");
    exit(0);
  }

  $updated = 0;
  $errors = 0;

  foreach ($rows as $row) {
    $loanId = (int)($row['id'] ?? 0);
    if ($loanId <= 0) {
      continue;
    }

    try {
      $db->beginTransaction();

      // Lock + double-check status
      $checkStmt = $db->prepare(
        "SELECT {$statusCol} AS s FROM loans WHERE {$idCol} = :id FOR UPDATE"
      );
      $checkStmt->execute([':id' => $loanId]);
      $currentStatus = strtolower(trim((string)$checkStmt->fetchColumn()));
      if (!in_array($currentStatus, ['active', 'borrowed'], true)) {
        $db->rollBack();
        continue;
      }

      // Update status to overdue
      $updateStmt = $db->prepare(
        "UPDATE loans SET {$statusCol} = :status WHERE {$idCol} = :id"
      );
      $updateStmt->execute([
        ':status' => 'overdue',
        ':id' => $loanId,
      ]);

      // Log event if loan_events table exists
      if ($hasLoanEvents && $hasEventLoanId && $hasEventType) {
        $eventColumns = ['loan_id', 'event_type', 'notes'];
        $eventValues = [':loan_id', ':event_type', ':notes'];
        $eventParams = [
          ':loan_id' => $loanId,
          ':event_type' => 'overdue',
          ':notes' => 'Auto-marked overdue by cron process_overdue.php',
        ];

        if (hasColumn($db, 'loan_events', 'actor_user_id')) {
          $eventColumns[] = 'actor_user_id';
          $eventValues[] = ':actor_user_id';
          $eventParams[':actor_user_id'] = null;
        }

        $eventSql = 'INSERT INTO loan_events (' . implode(', ', $eventColumns) . ') VALUES (' . implode(', ', $eventValues) . ')';
        $eventStmt = $db->prepare($eventSql);
        $eventStmt->execute($eventParams);
      }

      $db->commit();
      $updated++;
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      error_log("process_overdue.php error for loan #{$loanId}: " . $e->getMessage());
      $errors++;
    }
  }

  $elapsed = round(microtime(true) - $start, 4);
  fwrite(STDOUT, "Overdue processed: {$updated} updated, {$errors} errors in {$elapsed}s.\n");
  exit($errors > 0 ? 2 : 0);

} catch (Exception $e) {
  fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n");
  exit(1);
}

// --- Schema-tolerant helpers (inline, minimal) ---

function tableExists(PDO $db, string $table): bool
{
  static $cache = [];
  if (isset($cache[$table])) return $cache[$table];
  $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
  $stmt->execute([':t' => $table]);
  $exists = (int)$stmt->fetchColumn() > 0;
  $cache[$table] = $exists;
  return $exists;
}

function hasColumn(PDO $db, string $table, string $column): bool
{
  static $cache = [];
  $key = $table . '.' . $column;
  if (isset($cache[$key])) return $cache[$key];
  if (!tableExists($db, $table)) return false;
  $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c");
  $stmt->execute([':t' => $table, ':c' => $column]);
  $exists = (int)$stmt->fetchColumn() > 0;
  $cache[$key] = $exists;
  return $exists;
}

function resolveLoanColumn(PDO $db, string $table, array $candidates): ?string
{
  foreach ($candidates as $candidate) {
    if (hasColumn($db, $table, $candidate)) return $candidate;
  }
  return null;
}
