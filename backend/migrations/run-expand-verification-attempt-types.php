<?php

/**
 * Executes migration to expand verification_attempts.attempt_type enum values.
 *
 * Usage:
 *   php backend/migrations/run-expand-verification-attempt-types.php
 */

require_once __DIR__ . '/../../includes/config.php';

$migrationPath = __DIR__ . '/2026_04_12_expand_verification_attempt_types.sql';
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
  $statements = array_filter(array_map(static function ($chunk) {
    return trim($chunk);
  }, explode(';', $sql)));

  foreach ($statements as $statement) {
    $db->exec($statement);
  }

  echo "Expanded verification attempt enum migration completed successfully." . PHP_EOL;
  exit(0);
} catch (Exception $e) {
  fwrite(STDERR, "Migration failed: " . $e->getMessage() . PHP_EOL);
  exit(1);
}
