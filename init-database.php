<?php
/**
 * Database Initialization Script
 * 
 * This script creates the database and tables if they don't exist.
 * Run this once after setting up your localhost environment.
 * 
 * Access: http://localhost/library_betonio/init-database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once 'includes/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #ddd;
            background: #fafafa;
        }
        .step.success {
            border-left-color: #28a745;
            background: #f0f9f5;
        }
        .step.error {
            border-left-color: #dc3545;
            background: #fdf8f8;
        }
        .status {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status.success::before { content: "✅ "; color: #28a745; }
        .status.error::before { content: "❌ "; color: #dc3545; }
        .details {
            background: white;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.6;
            overflow-x: auto;
        }
        .action-button {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .action-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Initialization</h1>

<?php
$all_success = true;

// Step 1: Check connection
echo '<div class="step ' . (isset($connection_ok) && $connection_ok ? 'success' : '') . '">';
echo '<div class="status ' . (isset($connection_ok) && $connection_ok ? 'success' : '') . '">Step 1: Test Connection</div>';

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    $root_conn = new PDO($dsn, DB_USER, DB_PASSWORD);
    $root_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection_ok = true;
    
    echo '<div class="details">';
    echo "✅ Connected to MySQL server\n";
    echo "Host: " . DB_HOST . ":" . DB_PORT . "\n";
    echo '</div>';
    
} catch (PDOException $e) {
    $connection_ok = false;
    $all_success = false;
    
    echo '<div class="details">';
    echo "❌ Cannot connect to MySQL\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "FIX: Start MySQL from XAMPP Control Panel\n";
    echo '</div>';
}
echo '</div>';

// Step 2: Create database
if ($connection_ok) {
    echo '<div class="step success">';
    echo '<div class="status success">Step 2: Create Database</div>';
    
    try {
        $dbname = DB_NAME;
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        $root_conn->exec($sql);
        $db_created = true;
        
        echo '<div class="details">';
        echo "✅ Database created/verified: $dbname\n";
        echo '</div>';
        
    } catch (Exception $e) {
        $db_created = false;
        $all_success = false;
        
        echo '<div class="step error">';
        echo '<div class="status error">Error Creating Database</div>';
        echo '<div class="details">';
        echo "❌ " . $e->getMessage() . "\n";
        echo '</div>';
    }
    echo '</div>';
}

// Step 3: Connect to database
if ($connection_ok && $db_created) {
    echo '<div class="step success">';
    echo '<div class="status success">Step 3: Connect to Database</div>';
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $db_conn = new PDO($dsn, DB_USER, DB_PASSWORD);
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo '<div class="details">';
        echo "✅ Connected to database: " . DB_NAME . "\n";
        echo '</div>';
        
    } catch (PDOException $e) {
        $db_created = false;
        $all_success = false;
        
        echo '<div class="step error">';
        echo '<div class="status error">Error Connecting to Database</div>';
        echo '<div class="details">';
        echo "❌ " . $e->getMessage() . "\n";
        echo '</div>';
    }
    echo '</div>';
}

// Step 4: Check existing tables
if ($connection_ok && $db_created) {
    echo '<div class="step">';
    echo '<div class="status">Step 4: Check Existing Tables</div>';
    
    try {
        $stmt = $db_conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<div class="details">';
        if (empty($tables)) {
            echo "⚠️  No tables found. Database is empty.\n\n";
            echo "You need to:\n";
            echo "1. Create database schema (tables)\n";
            echo "2. Run migrations if available\n";
            echo "3. Check for SQL dump files to import\n";
        } else {
            echo "✅ Found " . count($tables) . " table(s):\n\n";
            foreach ($tables as $table) {
                echo "  • " . $table . "\n";
            }
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="details">';
        echo "❌ Error checking tables: " . $e->getMessage() . "\n";
        echo '</div>';
    }
    echo '</div>';
}

// Summary
echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-top: 20px;">';

if ($all_success && $connection_ok && $db_created) {
    echo '<h3 style="color: #155724; margin-top: 0;">✅ Database Initialized Successfully!</h3>';
    echo '<p style="color: #155724;">Your database is ready to use.</p>';
    
    if (!isset($tables) || empty($tables)) {
        echo '<p style="color: #155724;"><strong>Next Steps:</strong></p>';
        echo '<ul style="color: #155724;">';
        echo '<li>Check for migration files in the backend/ directory</li>';
        echo '<li>Look for SQL dump files to import</li>';
        echo '<li>Or create tables manually through phpMyAdmin</li>';
        echo '</ul>';
    }
} else {
    echo '<h3 style="color: #721c24; margin-top: 0;">⚠️ Database Initialization Incomplete</h3>';
    echo '<p style="color: #721c24;">Please fix the errors above before proceeding.</p>';
    echo '<p style="color: #721c24;"><strong>Quick Checklist:</strong></p>';
    echo '<ul style="color: #721c24;">';
    echo '<li>Is MySQL running? (Check XAMPP Control Panel)</li>';
    echo '<li>Is .env file configured correctly?</li>';
    echo '<li>Can you access phpMyAdmin? (http://localhost/phpmyadmin)</li>';
    echo '</ul>';
}

echo '</div>';

// Navigation
echo '<div style="margin-top: 30px; text-align: center;">';
if ($all_success && $connection_ok && $db_created) {
    echo '<a href="index.php" class="action-button">Go to Application</a>';
    echo ' <a href="test-connection.php" class="action-button">Test Connection</a>';
} else {
    echo '<a href="test-connection.php" class="action-button">Test Connection</a>';
    echo ' <a href="init-database.php" class="action-button">Retry Initialization</a>';
}
echo '</div>';

echo '</div>'; // container
?>
</body>
</html>
