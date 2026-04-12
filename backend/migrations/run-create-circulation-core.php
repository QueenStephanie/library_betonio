<?php

/**
 * Executes migration to create minimal circulation core tables.
 *
 * Usage:
 *   php backend/migrations/run-create-circulation-core.php
 */

require_once __DIR__ . '/../../includes/config.php';

/**
 * Ensure existing circulation tables are compatible before applying migration.
 */
function assertSchemaCompatibility(PDO $db): void
{
  $requiredColumnsByTable = [
    'books' => [
      ['id'],
      ['title'],
      ['author'],
    ],
    'book_copies' => [
      ['id'],
      ['book_id'],
      ['barcode'],
      ['status'],
    ],
    'reservations' => [
      ['id'],
      ['book_id'],
      ['status'],
      ['user_id', 'borrower_user_id'],
    ],
    'loans' => [
      ['id'],
      ['user_id', 'borrower_user_id'],
      ['book_copy_id', 'book_id'],
      ['loan_status', 'status'],
      ['due_at', 'due_date'],
    ],
    'loan_events' => [
      ['id'],
      ['loan_id'],
      ['event_type'],
      ['event_at'],
    ],
  ];

  foreach ($requiredColumnsByTable as $table => $requiredColumns) {
    $tableExistsStmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
    $tableExistsStmt->execute([':table_name' => $table]);
    $tableExists = (int)$tableExistsStmt->fetchColumn() > 0;

    if (!$tableExists) {
      continue;
    }

    $columnsStmt = $db->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name');
    $columnsStmt->execute([':table_name' => $table]);
    $existingColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    $existingMap = array_flip(array_map('strtolower', $existingColumns));

    $missingGroups = [];
    foreach ($requiredColumns as $requiredColumnCandidates) {
      $groupSatisfied = false;
      foreach ($requiredColumnCandidates as $candidate) {
        if (isset($existingMap[strtolower($candidate)])) {
          $groupSatisfied = true;
          break;
        }
      }

      if (!$groupSatisfied) {
        $missingGroups[] = implode(' or ', $requiredColumnCandidates);
      }
    }

    if (!empty($missingGroups)) {
      throw new RuntimeException(
        "Schema compatibility check failed for '{$table}'. Missing columns: " . implode(', ', $missingGroups)
      );
    }
  }
}

$migrationPath = __DIR__ . '/2026_04_12_create_circulation_core.sql';
if (!file_exists($migrationPath)) {
  fwrite(STDERR, "Migration file not found: {$migrationPath}" . PHP_EOL);
  exit(1);
}

$sql = file_get_contents($migrationPath);
if ($sql === false) {
  fwrite(STDERR, "Failed to read migration file." . PHP_EOL);
  exit(1);
}

try {
  assertSchemaCompatibility($db);

  $statements = array_filter(array_map(static function ($chunk) {
    return trim($chunk);
  }, explode(';', $sql)));

  foreach ($statements as $statement) {
    $db->exec($statement);
  }

  echo "Circulation core migration completed successfully." . PHP_EOL;
  exit(0);
} catch (Exception $e) {
  fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
  exit(1);
}
