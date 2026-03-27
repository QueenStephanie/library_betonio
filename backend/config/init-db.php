<?php

/**
 * Database Initialization Script
 * Creates database and tables for Library Betonio
 * 
 * ⚠️ SECURITY WARNING: Delete this file after first run!
 * This file exposes your database structure.
 */

header('Content-Type: text/html; charset=utf-8');

// Initial connection without database (to create DB)
$servername = "localhost:3307";
$username = "root";
$password = "";
$database = "library_betonio";

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Database Init</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5}";
echo ".success{color:green;background:#e8f5e9;padding:10px;margin:5px 0;border-left:4px solid green}";
echo ".error{color:red;background:#ffebee;padding:10px;margin:5px 0;border-left:4px solid red}";
echo ".info{color:#0066cc;background:#e3f2fd;padding:10px;margin:5px 0;border-left:4px solid #0066cc}";
echo ".panel{background:white;padding:20px;border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1);max-width:800px;margin:0 auto}";
echo "h1{color:#333}h2{color:#666;margin-top:20px}code{background:#f5f5f5;padding:2px 5px;border-radius:3px;font-family:monospace}";
echo "</style></head><body><div class='panel'>";
echo "<h1>🗄️ Library Betonio - Database Initialization</h1>";

try {
  // Step 1: Connect to MySQL server
  echo "<p class='info'>📝 Step 1: Connecting to MySQL Server...</p>";
  $conn = new mysqli($servername, $username, $password);

  if ($conn->connect_error) {
    throw new Exception("Connection failed: " . $conn->connect_error);
  }
  echo "<p class='success'>✓ Connected to MySQL Server</p>";

  // Step 2: Create database
  echo "<p class='info'>📝 Step 2: Creating/Verifying Database...</p>";
  $sql = "CREATE DATABASE IF NOT EXISTS `$database`";
  if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Database ready (created or already exists)</p>";
  } else {
    throw new Exception("Error creating database: " . $conn->error);
  }

  // Step 3: Connect to database
  echo "<p class='info'>📝 Step 3: Connecting to Database...</p>";
  $conn->select_db($database);
  if ($conn->error) {
    throw new Exception("Error selecting database: " . $conn->error);
  }
  echo "<p class='success'>✓ Connected to database <code>$database</code></p>";

  // Step 4: Create tables
  echo "<p class='info'>📝 Step 4: Creating Tables...</p>";

  // Users table
  $sql = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `is_verified` BOOLEAN DEFAULT FALSE,
    `verification_token` VARCHAR(255),
    `verification_token_expires` DATETIME,
    `reset_token` VARCHAR(255),
    `reset_token_expires` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME,
    `is_active` BOOLEAN DEFAULT TRUE,
    KEY `idx_email` (`email`),
    KEY `idx_verification_token` (`verification_token`),
    KEY `idx_reset_token` (`reset_token`),
    KEY `idx_is_verified` (`is_verified`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Table <code>users</code> created</p>";
  } else {
    throw new Exception("Error creating users table: " . $conn->error);
  }

  // OTP Codes table
  $sql = "CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `otp_code` VARCHAR(6) NOT NULL,
    `purpose` VARCHAR(50) NOT NULL,
    `is_used` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_used` (`is_used`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Table <code>otp_codes</code> created</p>";
  } else {
    throw new Exception("Error creating otp_codes table: " . $conn->error);
  }

  // Verification attempts table
  $sql = "CREATE TABLE IF NOT EXISTS `verification_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `attempt_type` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45),
    `is_successful` BOOLEAN DEFAULT FALSE,
    `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_email_type` (`email`, `attempt_type`),
    KEY `idx_ip_time` (`ip_address`, `attempted_at`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Table <code>verification_attempts</code> created</p>";
  } else {
    throw new Exception("Error creating verification_attempts table: " . $conn->error);
  }

  // Login history table
  $sql = "CREATE TABLE IF NOT EXISTS `login_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `logout_time` DATETIME,
    `is_successful` BOOLEAN DEFAULT TRUE,
    KEY `idx_user_id` (`user_id`),
    KEY `idx_login_time` (`login_time`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

  if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✓ Table <code>login_history</code> created</p>";
  } else {
    throw new Exception("Error creating login_history table: " . $conn->error);
  }

  // Step 5: Verify all tables
  echo "<p class='info'>📝 Step 5: Verifying All Tables...</p>";
  $result = $conn->query("SHOW TABLES");
  $tables = [];
  while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
  }

  if (count($tables) >= 4) {
    echo "<p class='success'>✓ All 4 required tables created:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
      echo "<li><code>$table</code></li>";
    }
    echo "</ul>";
  } else {
    throw new Exception("Not all tables were created. Found: " . implode(", ", $tables));
  }

  echo "<h2>✅ Database Initialization Complete!</h2>";
  echo "<p class='success'>Your database is ready to use.</p>";

  echo "<div style='background:#fff3cd;padding:10px;margin-top:20px;border-radius:5px;border-left:4px solid #ffc107'>";
  echo "<strong>⚠️ IMPORTANT SECURITY NOTICE:</strong><br>";
  echo "This file (<code>init-db.php</code>) should be <strong>deleted immediately</strong> for security reasons!<br>";
  echo "It exposes your database structure. Delete it from: <code>/backend/config/init-db.php</code>";
  echo "</div>";

  $conn->close();
} catch (Exception $e) {
  echo "<h2>❌ Error During Initialization</h2>";
  echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";

  echo "<div style='background:#f5f5f5;padding:10px;margin-top:10px;border-radius:5px;font-family:monospace;font-size:12px;'>";
  echo "<strong>Troubleshooting:</strong><br>";
  echo "1. Make sure MySQL is running (XAMPP Control Panel)<br>";
  echo "2. Check MySQL is on port 3307<br>";
  echo "3. Verify username <code>root</code> has no password<br>";
  echo "4. Try again or check error logs<br>";
  echo "</div>";
}

echo "</div></body></html>";
