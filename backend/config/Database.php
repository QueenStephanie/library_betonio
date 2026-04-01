<?php

/**
 * Database Configuration and Connection Handler
 * Configured for MySQL Server running on port 3307 with Betonio Library
 */

// Set timezone to prevent timezone-related issues
date_default_timezone_set('UTC');

require_once __DIR__ . '/AppBootstrap.php';

class DatabaseConnection
{
  // Database Configuration
  private $host;
  private $port;
  private $db_name;
  private $user;
  private $password;

  private $conn;

  public function __construct()
  {
    $dbConfig = AppBootstrap::getDatabaseConfig();
    $this->host = $dbConfig['host'];
    $this->port = $dbConfig['port'];
    $this->db_name = $dbConfig['name'];
    $this->user = $dbConfig['user'];
    $this->password = $dbConfig['password'];
  }

  /**
   * Connect to database using PDO for secure operations
   */
  public function connect()
  {
    $portsToTry = [(int)$this->port];
    $dbPortFromEnv = getenv('DB_PORT');
    $hostIsLocal = in_array(strtolower($this->host), ['localhost', '127.0.0.1', '::1'], true);

    if ($dbPortFromEnv === false && $hostIsLocal) {
      if (!in_array(3306, $portsToTry, true)) {
        $portsToTry[] = 3306;
      }
      if (!in_array(3307, $portsToTry, true)) {
        $portsToTry[] = 3307;
      }
    }

    $lastError = null;
    foreach ($portsToTry as $port) {
      try {
        $dsn = "mysql:host=" . $this->host . ";port=" . (int)$port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

        $this->conn = new PDO($dsn, $this->user, $this->password);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $this->conn;
      } catch (PDOException $e) {
        $lastError = $e;
      }
    }

    error_log("Database connection error: " . ($lastError ? $lastError->getMessage() : 'unknown'));
    http_response_code(500);
    die(json_encode([
      'success' => false,
      'error' => 'Database connection failed'
    ]));
  }

  /**
   * Get database connection
   */
  public function getConnection()
  {
    return $this->connect();
  }

  /**
   * Check if database exists, create if not
   */
  public static function initializeDatabase()
  {
    try {
      $db_config = AppBootstrap::getDatabaseConfig();
      $databaseName = $db_config['name'];

      $conn = new PDO(
        "mysql:host=" . $db_config['host'] . ";port=" . $db_config['port'],
        $db_config['user'],
        $db_config['password']
      );

      // Create database if not exists
      $sql = "CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '', $databaseName) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $conn->exec($sql);

      echo "Database initialized successfully";
      return true;
    } catch (PDOException $e) {
      error_log("Database initialization error: " . $e->getMessage());
      return false;
    }
  }
}

if (!class_exists('Database')) {
  class Database extends DatabaseConnection {}
}
