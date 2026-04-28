<?php
declare(strict_types=1);

/**
 * Cron: Expire unclaimed ready reservations.
 *
 * Run daily: php cron/expire_reservations.php
 *
 * Changes reservation status from 'ready' → 'cancelled'
 * when ready_until/expires_at is past.
 * Logs an event if reservation_events table exists.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$start = microtime(true);

try {
  if (!isset($db) || !$db instanceof PDO) {
    fwrite(STDERR, "ERROR: No database connection.\n");
    exit(1);
  }

  if (!tableExists($db, 'reservations')) {
    fwrite(STDOUT, "Reservations table missing, skipping.\n");
    exit(0);
  }

  // Resolve columns (schema-tolerant)
  $statusCol = resolveColumn($db, 'reservations', ['status']);
  $expiryCol = resolveColumn($db, 'reservations', ['ready_until', 'expires_at']);
  $idCol = resolveColumn($db, 'reservations', ['id']);

  if ($statusCol === null || $expiryCol === null || $idCol === null) {
    fwrite(STDERR, "ERROR: Reservations schema incompatible (need status + ready_until/expires_at + id).\n");
    exit(1);
  }

  $sCol = "`$statusCol`";
  $eCol = "`$expiryCol`";

  $hasEventsTable = tableExists($db, 'reservation_events');
  $hasEventRid = $hasEventsTable && hasColumn($db, 'reservation_events', 'reservation_id');
  $hasEventType = $hasEventsTable && hasColumn($db, 'reservation_events', 'event_type');

  // Find expired ready reservations
  $selectSql = "SELECT {$idCol} AS id, {$eCol} AS expires_at
    FROM reservations
    WHERE {$sCol} = 'ready'
      AND {$eCol} < NOW()
    LIMIT 500";

  $rows = $db->query($selectSql)->fetchAll(PDO::FETCH_ASSOC);

  if (empty($rows)) {
    fwrite(STDOUT, "No expired reservations found.\n");
    exit(0);
  }

  $updated = 0;
  $errors = 0;

  foreach ($rows as $row) {
    $rid = (int)($row['id'] ?? 0);
    if ($rid <= 0) continue;

    try {
      $db->beginTransaction();

      // Lock + double-check status
      $checkStmt = $db->prepare(
        "SELECT {$sCol} AS s FROM reservations WHERE {$idCol} = :id FOR UPDATE"
      );
      $checkStmt->execute([':id' => $rid]);
      $current = strtolower(trim((string)$checkStmt->fetchColumn()));
      if ($current !== 'ready') {
        $db->rollBack();
        continue;
      }

      // Cancel it
      $updateStmt = $db->prepare(
        "UPDATE reservations SET {$sCol} = :status WHERE {$idCol} = :id"
      );
      $updateStmt->execute([':status' => 'cancelled', ':id' => $rid]);

      // Log event
      if ($hasEventsTable && $hasEventRid && $hasEventType) {
        $eventCols = ['reservation_id', 'event_type', 'notes'];
        $eventVals = [':rid', ':etype', ':notes'];
        $eventParams = [
          ':rid' => $rid,
          ':etype' => 'expired',
          ':notes' => 'Auto-expired: unclaimed ready reservation by cron expire_reservations.php',
        ];
        if (hasColumn($db, 'reservation_events', 'actor_user_id')) {
          $eventCols[] = 'actor_user_id';
          $eventVals[] = ':actor';
          $eventParams[':actor'] = null;
        }
        $esql = 'INSERT INTO reservation_events (' . implode(',', $eventCols) . ') VALUES (' . implode(',', $eventVals) . ')';
        $db->prepare($esql)->execute($eventParams);
      }

      $db->commit();
      $updated++;
    } catch (Exception $e) {
      if ($db->inTransaction()) $db->rollBack();
      error_log("expire_reservations.php error for #{$rid}: " . $e->getMessage());
      $errors++;
    }
  }

  $elapsed = round(microtime(true) - $start, 4);
  fwrite(STDOUT, "Expired reservations: {$updated} cancelled, {$errors} errors in {$elapsed}s.\n");
  exit($errors > 0 ? 2 : 0);

} catch (Exception $e) {
  fwrite(STDERR, "FATAL: " . $e->getMessage() . "\n");
  exit(1);
}

// --- Schema-tolerant helpers (inline, minimal, deduped via function_exists) ---

if (!function_exists('tableExists')) {
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
}

if (!function_exists('hasColumn')) {
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
}

if (!function_exists('resolveColumn')) {
function resolveColumn(PDO $db, string $table, array $candidates): ?string
{
  foreach ($candidates as $candidate) {
    if (hasColumn($db, $table, $candidate)) return $candidate;
  }
  return null;
}
}
