<?php

/**
 * Database Configuration and Connection Handler
 * Configured for MySQL Server running on port 3307 with Betonio Library
 */

// Set timezone to prevent timezone-related issues
date_default_timezone_set('UTC');

class Database
{
  // Database Configuration
  private $host = 'localhost';
  private $port = 3307;
  private $db_name = 'library_betonio';
  private $user = 'root';
  private $password = '';

  private $conn;

  /**
   * Connect to database using PDO for secure operations
   */
  public function connect()
  {
    try {
      $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

      $this->conn = new PDO($dsn, $this->user, $this->password);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      return $this->conn;
    } catch (PDOException $e) {
      http_response_code(500);
      die(json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $e->getMessage()
      ]));
    }
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
      $db_config = [
        'host' => 'localhost',
        'port' => 3307,
        'user' => 'root',
        'password' => ''
      ];

      $conn = new PDO(
        "mysql:host=" . $db_config['host'] . ";port=" . $db_config['port'],
        $db_config['user'],
        $db_config['password']
      );

      // Create database if not exists
      $sql = "CREATE DATABASE IF NOT EXISTS library_betonio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
      $conn->exec($sql);

      echo "Database initialized successfully";
      return true;
    } catch (PDOException $e) {
      error_log("Database initialization error: " . $e->getMessage());
      return false;
    }
  }
}
