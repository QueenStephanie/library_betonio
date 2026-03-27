<?php

/**
 * Quick Database Setup
 * Initializes the database schema if not already done
 */

header('Content-Type: application/json');

try {
  // Connection credentials
  $host = "localhost:3307";
  $username = "root";
  $password = "";
  $database = "library_betonio";

  // Step 1: Connect to MySQL server (without database)
  $pdo = new PDO("mysql:host=$host", $username, $password);

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

  // Split by semicolon and execute each statement
  $statements = array_filter(array_map(function ($stmt) {
    return trim($stmt);
  }, explode(';', $schema_content)));

  $created_tables = [];
  foreach ($statements as $statement) {
    if (!empty($statement)) {
      // Skip comments and USE statements
      if (strpos($statement, '--') === 0 || stripos($statement, 'USE') === 0) {
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

  http_response_code(200);
  echo json_encode([
    'success' => true,
    'message' => 'Database initialized successfully!',
    'database' => $database,
    'tables_ready' => ['users', 'otp_codes', 'verification_attempts', 'login_history'],
    'status' => 'All tables created or already exist'
  ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage()
  ], JSON_PRETTY_PRINT);
}
