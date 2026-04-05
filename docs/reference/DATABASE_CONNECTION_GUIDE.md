DATABASE CONNECTION TROUBLESHOOTING GUIDE
==========================================

## PROBLEM DIAGNOSIS
================================================

Your application was failing to connect to the database because:

1. ✗ MySQL/MariaDB service was NOT running
2. ✗ No .env configuration file existed (deleted during cleanup)
3. ✗ Port mismatch: code defaulted to 3306, but XAMPP uses 3307

## ROOT CAUSE
================================================

The `.env` file was deleted during the security cleanup, which meant:
- No database configuration was being loaded
- Application fell back to default settings (port 3306)
- But XAMPP's MySQL runs on port 3307
- Connection attempts failed because nothing was listening on port 3306


## STEP-BY-STEP SOLUTION
================================================

### STEP 1: START MYSQL SERVICE (CRITICAL!)
----------------------------------------------

**Windows - XAMPP Control Panel Method:**
1. Open XAMPP Control Panel
   - Go to: C:\xampp\xampp-control.exe
   - Or search "XAMPP" in Windows Start Menu
   
2. Look for "MySQL" in the list
   - If status shows "Running" → Good! Skip to Step 2
   - If status shows "Stopped" → Click the "Start" button next to MySQL
   
3. Wait 10-15 seconds for MySQL to start
   - Status should change from "Stopped" to "Running"
   - Green checkmark or "Running" label will appear

4. If it fails to start:
   - Check if port 3307 is already in use: netstat -an | findstr 3307
   - Check MySQL logs: C:\xampp\mysql\data\error.log
   - Try stopping and restarting from Control Panel

**Alternative - Command Line (Administrator):**
```batch
cd C:\xampp\mysql\bin
mysqld --port=3307
```

---

### STEP 2: VERIFY CONFIGURATION FILE
----------------------------------------------

✓ Created: C:\xampp\htdocs\library_betonio\.env

This file contains:
- DB_HOST=localhost
- DB_PORT=3307          ← CORRECT PORT FOR XAMPP
- DB_NAME=library_betonio
- DB_USER=root
- DB_PASS=              ← Empty (default XAMPP)


### STEP 3: CREATE DATABASE (If Not Exists)
----------------------------------------------

**Option A: Using phpMyAdmin (Web UI) - EASIEST**
1. Go to: http://localhost/phpmyadmin
2. Click "New" in left sidebar
3. Enter database name: library_betonio
4. Collation: utf8mb4_general_ci
5. Click "Create"

**Option B: Using MySQL Command Line**
```sql
mysql -u root -P 3307
CREATE DATABASE IF NOT EXISTS library_betonio 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Option C: PHP Script (Create in project root)**
Create file: C:\xampp\htdocs\library_betonio\setup-database.php
```php
<?php
require_once 'includes/config.php';

try {
    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS library_betonio 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci";
    
    $db->exec($sql);
    echo "Database created successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

Then visit: http://localhost/library_betonio/setup-database.php


### STEP 4: TEST CONNECTION
----------------------------------------------

Create a test file: C:\xampp\htdocs\library_betonio\test-db.php

```php
<?php
/**
 * Database Connection Test
 */

require_once 'includes/config.php';

echo "<h2>Database Connection Test</h2>";
echo "<pre>";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    
    echo "Connection String:\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Port: " . DB_PORT . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "---\n";
    
    // Try to connect
    $test_conn = new PDO($dsn, DB_USER, DB_PASSWORD);
    $test_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ CONNECTION SUCCESSFUL!\n\n";
    
    // Test query
    $stmt = $test_conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ QUERY TEST PASSED!\n\n";
    
    // List tables
    $stmt = $test_conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll();
    
    if (empty($tables)) {
        echo "⚠️  No tables found. Database is empty.\n";
        echo "You may need to run database migrations.\n";
    } else {
        echo "Tables found:\n";
        foreach ($tables as $table) {
            echo "  - " . $table[0] . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ CONNECTION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Is MySQL running? Check XAMPP Control Panel\n";
    echo "2. Is port 3307 correct? Check .env file\n";
    echo "3. Database exists? Check phpMyAdmin\n";
}

echo "</pre>";
?>
```

Then visit: http://localhost/library_betonio/test-db.php


## COMMON ISSUES & FIXES
================================================

### ❌ Issue: "Connection refused"
**Cause:** MySQL is not running
**Fix:** 
1. Start MySQL from XAMPP Control Panel
2. Wait 10-15 seconds
3. Refresh page

---

### ❌ Issue: "SQLSTATE[HY000]: General error: 2006 MySQL server has gone away"
**Cause:** MySQL crashed or stopped
**Fix:**
1. Check MySQL error log: C:\xampp\mysql\data\error.log
2. Restart MySQL from XAMPP Control Panel
3. Check available disk space

---

### ❌ Issue: "Port already in use"
**Cause:** Another application or MySQL instance using port 3307
**Fix:**
1. Find what's using port 3307: netstat -ano | findstr 3307
2. Either stop that application or change port in .env
3. Alternative: Edit C:\xampp\mysql\bin\my.cnf and change port

---

### ❌ Issue: "Access denied for user 'root'@'localhost'"
**Cause:** Wrong password in .env
**Fix:**
1. Check default XAMPP root password (usually empty)
2. Update .env: DB_PASS=your_password
3. Or reset password via phpMyAdmin

---

### ❌ Issue: "Unknown database 'library_betonio'"
**Cause:** Database doesn't exist
**Fix:**
1. Go to phpMyAdmin: http://localhost/phpmyadmin
2. Create database named: library_betonio
3. Use utf8mb4 collation

---

### ❌ Issue: "Table 'library_betonio.users' doesn't exist"
**Cause:** Tables haven't been created yet
**Fix:**
1. Look for database schema/migration files
2. Run SQL migration scripts
3. Or set up from database dump/backup

---

## VERIFICATION CHECKLIST
================================================

Before assuming everything works:

- [ ] MySQL is running (check XAMPP Control Panel)
- [ ] Port 3307 is listening: netstat -an | findstr 3307
- [ ] .env file exists with DB_PORT=3307
- [ ] Database 'library_betonio' exists
- [ ] Can access phpMyAdmin: http://localhost/phpmyadmin
- [ ] test-db.php shows "✅ CONNECTION SUCCESSFUL"
- [ ] Application homepage loads without errors
- [ ] Can login/register without database errors

---

## CONFIGURATION FILES
================================================

### Current .env (Development)
Location: C:\xampp\htdocs\library_betonio\.env
- DB_HOST=localhost
- DB_PORT=3307
- DB_NAME=library_betonio
- DB_USER=root
- DB_PASS= (empty)

### Config Reader
Location: C:\xampp\htdocs\library_betonio\includes\config.php
- Loads .env file automatically
- Falls back to defaults if .env not found
- Sets up database connection constants

---

## QUICK REFERENCE
================================================

**Start MySQL:**
1. Open XAMPP Control Panel
2. Click "Start" next to MySQL
3. Wait for green checkmark

**Test Connection:**
1. Visit: http://localhost/library_betonio/test-db.php
2. Should show "✅ CONNECTION SUCCESSFUL"

**View Database:**
1. Visit: http://localhost/phpmyadmin
2. Look for 'library_betonio' database
3. Check tables are created

**View Application:**
1. Visit: http://localhost/library_betonio
2. Should load without "Database connection failed" error

---

## IF PROBLEMS PERSIST
================================================

1. Check MySQL error log:
   C:\xampp\mysql\data\error.log

2. Check web server logs:
   C:\xampp\apache\logs\error.log

3. Enable debug mode in .env:
   APP_DEBUG=true

4. Check available disk space on C: drive

5. Make sure no firewall is blocking port 3307

6. Try changing .env DB_HOST from 'localhost' to '127.0.0.1'

7. Verify file permissions:
   .env should be readable by Apache

---

For more help, check:
- .env.example (template for all variables)
- .env.production.example (production setup)
- DEPLOYMENT_READY.md (complete guide)
