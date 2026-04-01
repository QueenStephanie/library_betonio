<?php

/**
 * Quick Database Setup
 * Initializes the database schema if not already done
 */

header('Content-Type: application/json');

/**
 * Ensure an index exists only once.
 */
function ensureIndex(PDO $pdo, $database, $table, $indexName, $createSql)
{
  $check = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = :schema AND table_name = :table_name AND index_name = :index_name'
  );
  $check->execute([
    ':schema' => $database,
    ':table_name' => $table,
    ':index_name' => $indexName,
  ]);

  if ((int)$check->fetchColumn() === 0) {
    $pdo->exec($createSql);
  }
}

/**
 * Verify admin remediation tables and indexes idempotently.
 */
function ensureAdminSecuritySchema(PDO $pdo, $database)
{
  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS admin_credentials (
      id INT PRIMARY KEY AUTO_INCREMENT,
      username VARCHAR(100) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      is_active BOOLEAN DEFAULT TRUE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      password_changed_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );

  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS admin_session_registry (
      id INT PRIMARY KEY AUTO_INCREMENT,
      admin_identity VARCHAR(120) NOT NULL,
      admin_credential_id INT NULL,
      session_id_hash CHAR(64) NOT NULL UNIQUE,
      auth_mode ENUM('db', 'bootstrap_env') NOT NULL,
      ip_address VARCHAR(45) NULL,
      user_agent VARCHAR(500) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      last_seen_at DATETIME NULL,
      invalidated_at DATETIME NULL,
      CONSTRAINT fk_admin_session_credential
        FOREIGN KEY (admin_credential_id) REFERENCES admin_credentials(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
  );

  ensureIndex(
    $pdo,
    $database,
    'admin_credentials',
    'idx_admin_credentials_active',
    'CREATE INDEX idx_admin_credentials_active ON admin_credentials(is_active)'
  );
  ensureIndex(
    $pdo,
    $database,
    'admin_session_registry',
    'idx_admin_session_identity',
    'CREATE INDEX idx_admin_session_identity ON admin_session_registry(admin_identity)'
  );
  ensureIndex(
    $pdo,
    $database,
    'admin_session_registry',
    'idx_admin_session_active',
    'CREATE INDEX idx_admin_session_active ON admin_session_registry(admin_identity, invalidated_at)'
  );
}

try {
  require_once __DIR__ . '/config/AppBootstrap.php';
  $dbConfig = AppBootstrap::getDatabaseConfig();

  // Connection credentials
  $host = $dbConfig['host'];
  $port = (int)$dbConfig['port'];
  $username = $dbConfig['user'];
  $password = $dbConfig['password'];
  $database = $dbConfig['name'];
  $charset = $dbConfig['charset'];

  // Step 1: Connect to MySQL server (without database)
  $pdo = new PDO(
    "mysql:host=$host;port=$port;charset=$charset",
    $username,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );

  // Step 2: Create database if not exists
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");

  // Step 3: Select database
  $pdo->exec("USE `$database`");

  // Step 4: Read and execute schema
  $schema_file = __DIR__ . '/config/schema.sql';
  if (!file_exists($schema_file)) {
    throw new Exception("Schema file not found: $schema_file");
  }

  $schema_content = file_get_contents($schema_file);
  if ($schema_content === false) {
    throw new Exception('Failed to read schema file');
  }

  // Remove full-line SQL comments before splitting.
  $schema_lines = preg_split('/\r\n|\r|\n/', $schema_content);
  $filtered_lines = [];
  foreach ($schema_lines as $line) {
    if (preg_match('/^\s*--/', $line) === 1) {
      continue;
    }
    $filtered_lines[] = $line;
  }
  $schema_sql = implode("\n", $filtered_lines);

  // Split by semicolon and execute each statement
  $statements = array_filter(array_map(function ($stmt) {
    return trim($stmt);
  }, explode(';', $schema_sql)));

  $created_tables = [];
  foreach ($statements as $statement) {
    if (!empty($statement)) {
      // Skip USE statements from imported schema.
      if (stripos($statement, 'USE ') === 0) {
        continue;
      }

      try {
        $pdo->exec($statement);

        // Extract table name from CREATE TABLE statement
        if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $statement, $matches)) {
          $created_tables[] = $matches[1];
        }
      } catch (PDOException $e) {
        // Table might already exist - that's fine
      }
    }
  }

  // Step 5: Idempotent verification for remediation tables/indexes.
  ensureAdminSecuritySchema($pdo, $database);

  http_response_code(200);
  echo json_encode([
    'success' => true,
    'message' => 'Database initialized successfully!',
    'database' => $database,
    'tables_ready' => [
      'users',
      'otp_codes',
      'verification_attempts',
      'login_history',
      'admin_credentials',
      'admin_session_registry'
    ],
    'status' => 'All tables created or already exist'
  ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ], JSON_PRETTY_PRINT);
}
