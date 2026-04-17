<?php

/**
 * Canonical SQL migration runner.
 *
 * Usage:
 *   php backend/migrations/migrate.php --apply
 *   php backend/migrations/migrate.php --status
 *   php backend/migrations/migrate.php --dry-run
 */

require_once __DIR__ . '/../../includes/config.php';

/**
 * Print CLI usage instructions.
 */
function printUsage(): void
{
  $usage = [
    'Usage:',
    '  php backend/migrations/migrate.php [--apply|--status|--dry-run]',
    '',
    'Flags:',
    '  --apply    Apply all pending migrations (default)',
    '  --status   Show applied and pending migrations',
    '  --dry-run  Show pending migrations only',
  ];

  fwrite(STDOUT, implode(PHP_EOL, $usage) . PHP_EOL);
}

/**
 * Ensure migration history table exists.
 */
function ensureSchemaMigrationsTable(PDO $db): void
{
  $db->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
      migration_name VARCHAR(255) NOT NULL,
      checksum CHAR(64) NOT NULL,
      applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (migration_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );
}

/**
 * Load migration files in deterministic filename order.
 *
 * @return array<int, array{name:string,path:string,checksum:string,sql:string}>
 */
function loadMigrationFiles(string $migrationsDir): array
{
  $paths = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
  if ($paths === false) {
    throw new RuntimeException('Failed to scan migration directory.');
  }

  sort($paths, SORT_STRING);

  $migrations = [];
  foreach ($paths as $path) {
    $sql = file_get_contents($path);
    if ($sql === false) {
      throw new RuntimeException('Failed to read migration file: ' . $path);
    }

    $name = basename($path);
    $migrations[] = [
      'name' => $name,
      'path' => $path,
      'checksum' => hash('sha256', $sql),
      'sql' => $sql,
    ];
  }

  return $migrations;
}

/**
 * Fetch applied migration history.
 *
 * @return array<string, array{checksum:string,applied_at:string}>
 */
function fetchAppliedMigrations(PDO $db): array
{
  $stmt = $db->query('SELECT migration_name, checksum, applied_at FROM schema_migrations ORDER BY migration_name ASC');
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $applied = [];
  foreach ($rows as $row) {
    $name = (string)$row['migration_name'];
    $applied[$name] = [
      'checksum' => (string)$row['checksum'],
      'applied_at' => (string)$row['applied_at'],
    ];
  }

  return $applied;
}

/**
 * Remove comments and split SQL by semicolon.
 *
 * @return array<int, string>
 */
function splitSqlStatements(string $sql): array
{
  $withoutBlockComments = preg_replace('/\/\*.*?\*\//s', '', $sql);
  if ($withoutBlockComments === null) {
    $withoutBlockComments = $sql;
  }

  $lines = preg_split('/\r\n|\r|\n/', $withoutBlockComments);
  if ($lines === false) {
    $lines = [$withoutBlockComments];
  }

  $filtered = [];
  foreach ($lines as $line) {
    if (preg_match('/^\s*(--|#)/', $line) === 1) {
      continue;
    }
    $filtered[] = $line;
  }

  $normalizedSql = implode("\n", $filtered);

  return array_values(array_filter(array_map(static function ($statement) {
    return trim($statement);
  }, explode(';', $normalizedSql)), static function ($statement) {
    return $statement !== '';
  }));
}

/**
 * Apply one migration file in a transaction.
 */
function applyMigration(PDO $db, array $migration): void
{
  $statements = splitSqlStatements($migration['sql']);

  $db->beginTransaction();
  try {
    foreach ($statements as $statement) {
      $db->exec($statement);
    }

    $record = $db->prepare(
      'INSERT INTO schema_migrations (migration_name, checksum, applied_at)
       VALUES (:migration_name, :checksum, NOW())'
    );
    $record->execute([
      ':migration_name' => $migration['name'],
      ':checksum' => $migration['checksum'],
    ]);

    $db->commit();
  } catch (Exception $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }
    throw $e;
  }
}

/**
 * Parse command flag and return one of: apply, status, dry-run.
 */
function parseMode(array $argv): string
{
  $flags = array_slice($argv, 1);
  if ($flags === []) {
    return 'apply';
  }

  if (count($flags) !== 1) {
    throw new InvalidArgumentException('Provide only one flag.');
  }

  $flag = $flags[0];
  if ($flag === '--apply') {
    return 'apply';
  }
  if ($flag === '--status') {
    return 'status';
  }
  if ($flag === '--dry-run') {
    return 'dry-run';
  }

  throw new InvalidArgumentException('Unknown flag: ' . $flag);
}

try {
  $mode = parseMode($argv);
} catch (InvalidArgumentException $e) {
  fwrite(STDERR, $e->getMessage() . PHP_EOL);
  printUsage();
  exit(1);
}

try {
  ensureSchemaMigrationsTable($db);

  $migrations = loadMigrationFiles(__DIR__);
  $applied = fetchAppliedMigrations($db);

  $pending = [];
  $checksumMismatches = [];

  foreach ($migrations as $migration) {
    $name = $migration['name'];
    if (!isset($applied[$name])) {
      $pending[] = $migration;
      continue;
    }

    if ($applied[$name]['checksum'] !== $migration['checksum']) {
      $checksumMismatches[] = [
        'name' => $name,
        'recorded' => $applied[$name]['checksum'],
        'current' => $migration['checksum'],
      ];
    }
  }

  if ($mode === 'status') {
    fwrite(STDOUT, 'Applied migrations: ' . count($applied) . PHP_EOL);
    foreach ($applied as $name => $info) {
      fwrite(STDOUT, '  [APPLIED] ' . $name . ' @ ' . $info['applied_at'] . PHP_EOL);
    }

    fwrite(STDOUT, 'Pending migrations: ' . count($pending) . PHP_EOL);
    foreach ($pending as $migration) {
      fwrite(STDOUT, '  [PENDING] ' . $migration['name'] . PHP_EOL);
    }

    if ($checksumMismatches !== []) {
      fwrite(STDOUT, 'Checksum mismatches: ' . count($checksumMismatches) . PHP_EOL);
      foreach ($checksumMismatches as $mismatch) {
        fwrite(STDOUT, '  [MISMATCH] ' . $mismatch['name'] . PHP_EOL);
      }
    }

    exit(0);
  }

  if ($mode === 'dry-run') {
    fwrite(STDOUT, 'Pending migrations: ' . count($pending) . PHP_EOL);
    foreach ($pending as $migration) {
      fwrite(STDOUT, '  [PENDING] ' . $migration['name'] . PHP_EOL);
    }
    exit(0);
  }

  if ($checksumMismatches !== []) {
    fwrite(STDERR, 'Migration checksum mismatch detected. Resolve before applying.' . PHP_EOL);
    foreach ($checksumMismatches as $mismatch) {
      fwrite(STDERR, '  [MISMATCH] ' . $mismatch['name'] . PHP_EOL);
    }
    exit(1);
  }

  if ($pending === []) {
    fwrite(STDOUT, 'No pending migrations.' . PHP_EOL);
    exit(0);
  }

  foreach ($pending as $migration) {
    fwrite(STDOUT, 'Applying ' . $migration['name'] . ' ...' . PHP_EOL);
    applyMigration($db, $migration);
    fwrite(STDOUT, 'Applied ' . $migration['name'] . PHP_EOL);
  }

  fwrite(STDOUT, 'Migration run complete. Applied: ' . count($pending) . PHP_EOL);
  exit(0);
} catch (Exception $e) {
  fwrite(STDERR, 'Migration runner failed: ' . $e->getMessage() . PHP_EOL);
  exit(1);
}
