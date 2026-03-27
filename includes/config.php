<?php

/**
 * Configuration & Database Connection
 * Simple, centralized configuration for the entire application
 */

// Error reporting
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Session configuration
session_start();
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_PORT', 3307);
define('DB_NAME', 'library_betonio');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'QueenLib');
define('APP_URL', 'http://localhost/library_betonio');
define('APP_ENV', 'production');

// Security Configuration
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('OTP_EXPIRY', 600); // 10 minutes in seconds
define('OTP_LENGTH', 6);

// Email Configuration (Update these for your email provider)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM', 'noreply@queenslib.com');
define('MAIL_FROM_NAME', APP_NAME);

// Database Connection
class Database
{
  private $conn;

  public function connect()
  {
    try {
      $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
      $this->conn = new PDO($dsn, DB_USER, DB_PASSWORD);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      return $this->conn;
    } catch (PDOException $e) {
      die("Database connection failed: " . $e->getMessage());
    }
  }

  public function getConnection()
  {
    return $this->connect();
  }
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
