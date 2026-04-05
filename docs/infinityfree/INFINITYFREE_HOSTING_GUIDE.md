INFINITYFREE HOSTING GUIDE - QueenLib
=====================================

## QUICK SUMMARY
=================

What changes when moving from localhost to InfinityFree:

LOCAL (localhost)               →    INFINITYFREE (Production)
─────────────────────────────────────────────────────────────
http://localhost:3307           →    sql309.infinityfree.com:3306
library_betonio                 →    ifXXXXXXX_library_betonio
root / no password              →    ifXXXXXXX / (your password)
http://localhost/library_betonio →   https://username.infinityfree.com
.env file                       →    .env.production file


## STEP-BY-STEP GUIDE (Sequential)
==================================

STEP 1: Create InfinityFree Account
────────────────────────────────────

1. Go to: https://www.infinityfree.net
2. Click "Sign Up" or "Register"
3. Fill in your email and create password
4. Verify email (check inbox)
5. Choose subdomain: username.infinityfree.com
6. Accept terms and create account

✓ You now have:
  - Hosting account
  - Database credentials
  - FTP access
  - Control panel (cPanel)


STEP 2: Get Database Credentials
──────────────────────────────────

From email or cPanel:

1. Log in to cPanel: https://username.infinityfree.com:2083
   ├─ Username: Your cPanel username
   ├─ Password: Your cPanel password
   └─ (Check InfinityFree welcome email)

2. Find "MySQL Databases" or "PhpMyAdmin" in cPanel

3. Create new database:
   ├─ Name will be: ifXXXXXXX_yourdatabasename
   ├─ Example: ifXXXXXXX_library_betonio
   └─ Note down the full name

4. Get your credentials:
   ├─ Database Host: sql309.infinityfree.com (or similar)
   ├─ Database Name: ifXXXXXXX_library_betonio (noted above)
   ├─ Database User: ifXXXXXXX (usually same as database name prefix)
   ├─ Database Password: (create a strong password)
   └─ Database Port: 3306 (standard)

✓ Save these credentials somewhere safe!


STEP 3: Upload Files via FTP
──────────────────────────────

1. Download FTP Client:
   ├─ FileZilla (Windows/Mac/Linux) - RECOMMENDED
   ├─ WinSCP (Windows)
   ├─ Cyberduck (Mac)
   └─ Total Commander (Windows)

2. Get FTP Credentials from cPanel:
   ├─ Host: ftp.username.infinityfree.com
   ├─ Username: your FTP username
   ├─ Password: your FTP password
   ├─ Port: 21 (standard)
   └─ (Find in cPanel > FTP Accounts)

3. Connect with FTP Client:
   ├─ Input host, username, password
   ├─ Click Connect
   ├─ Navigate to htdocs folder
   └─ This is your web root

4. Upload Your Files:
   ├─ Right-click your project folder
   ├─ Select "Upload"
   ├─ Browse to C:\xampp\htdocs\library_betonio
   ├─ Select all files
   ├─ Click Upload/Start
   └─ Wait for completion (5-10 minutes)

✓ Your files are now on InfinityFree!


STEP 4: Create .env.production File
─────────────────────────────────────

⚠️ IMPORTANT: Do NOT upload .env or .env.example
              Only create .env.production on the server

1. Use cPanel File Manager:
   ├─ Log in to cPanel
   ├─ Click "File Manager"
   ├─ Navigate to your project folder
   └─ (usually public_html/library_betonio)

2. Create new file:
   ├─ Right-click in folder
   ├─ Click "Create New File"
   ├─ Name: .env.production
   ├─ Click Create
   └─ (Don't add .txt extension!)

3. Edit the file:
   ├─ Right-click .env.production
   ├─ Click "Edit"
   ├─ Paste content below
   ├─ Save
   └─ Close

4. Copy-Paste This Content:
   (Replace with YOUR actual credentials)

───────────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_URL=https://username.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

# InfinityFree Database Credentials
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=ifXXXXXXX_library_betonio
DB_USER=ifXXXXXXX
DB_PASS=YOUR_DATABASE_PASSWORD
DB_CHARSET=utf8mb4

# Email Configuration
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your_email@gmail.com
MAIL_PASS=your_gmail_app_password
MAIL_FROM=your_email@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_strong_password
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=true
───────────────────────────────────────────────────

⚠️ REPLACE THESE:
  - username → your InfinityFree subdomain
  - ifXXXXXXX → your actual database prefix
  - YOUR_DATABASE_PASSWORD → database password you created
  - your_email@gmail.com → your Gmail address
  - your_gmail_app_password → Gmail App Password (see Step 5)
  - your_strong_password → strong admin password


STEP 5: Setup Email (Gmail)
────────────────────────────

Using Gmail to send emails:

1. Enable 2-Factor Authentication on Gmail:
   ├─ Go to myaccount.google.com
   ├─ Click "Security" on left
   ├─ Enable "2-Step Verification"
   └─ Follow Google's steps

2. Create Gmail App Password:
   ├─ Go to myaccount.google.com/apppasswords
   ├─ Select "Mail" and "Windows Computer"
   ├─ Google generates 16-character password
   ├─ Copy it (remove spaces)
   └─ This goes in MAIL_PASS

3. Add to .env.production:
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=your_email@gmail.com
   MAIL_PASS=xxxxxxxxxxxx (the 16-char password)
   MAIL_ENCRYPTION=tls

✓ Email sending now works!


STEP 6: Test Connection
────────────────────────

1. Upload test file via FTP or create in File Manager:
   
   Create file: test-connection-prod.php
   Content:
   ───────────────────────────────────────────────────
   <?php
   require_once 'includes/config.php';
   
   echo "Configuration loaded:<br>";
   echo "Database Host: " . DB_HOST . "<br>";
   echo "Database Port: " . DB_PORT . "<br>";
   echo "Database Name: " . DB_NAME . "<br>";
   echo "Database User: " . DB_USER . "<br>";
   echo "<hr>";
   
   try {
       $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
       $conn = new PDO($dsn, DB_USER, DB_PASSWORD);
       $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       
       echo "✅ DATABASE CONNECTION SUCCESSFUL!<br>";
       
       // Test query
       $stmt = $conn->query("SELECT 1");
       echo "✅ QUERY TEST PASSED!<br>";
       
   } catch (PDOException $e) {
       echo "❌ CONNECTION FAILED<br>";
       echo "Error: " . $e->getMessage() . "<br>";
   }
   ?>
   ───────────────────────────────────────────────────

2. Visit in browser:
   https://username.infinityfree.com/library_betonio/test-connection-prod.php

3. Should show:
   ✅ DATABASE CONNECTION SUCCESSFUL!
   ✅ QUERY TEST PASSED!

⚠️ If error, check:
   - .env.production values are correct
   - Database name is right (ifXXXXXXX_library_betonio)
   - Database user is correct
   - Database password is correct


STEP 7: Setup Database Tables
───────────────────────────────

If database is empty:

Option A: Import from Local (EASIEST)
  1. On localhost, export database:
     - Open phpMyAdmin (http://localhost/phpmyadmin)
     - Select database: library_betonio
     - Click "Export"
     - Format: SQL
     - Click "Go"
     - Saves SQL file

  2. Import on InfinityFree:
     - Log in to cPanel
     - Click "PhpMyAdmin"
     - Select your database: ifXXXXXXX_library_betonio
     - Click "Import"
     - Upload your SQL file
     - Click "Import"
     ✓ Tables created!

Option B: Run Init Script
  1. Access via browser:
     https://username.infinityfree.com/library_betonio/init-database.php
  
  2. Follow steps
  
  3. Should create database and tables


STEP 8: Verify Everything Works
─────────────────────────────────

1. Visit main app:
   https://username.infinityfree.com/library_betonio/

2. Check for errors:
   - Can you see login page?
   - Are images loading?
   - Do forms work?

3. Try registration:
   - Create test account
   - Check if email received
   - Can you login?

4. Check error logs:
   - cPanel > Error Logs
   - Look for PHP errors
   - Fix any issues

✓ If everything works, you're done!


## CHANGES SUMMARY
===================

What you MUST change:

1. DATABASE CREDENTIALS
   └─ Host: localhost → sql309.infinityfree.com
   └─ Port: 3307 → 3306
   └─ Name: library_betonio → ifXXXXXXX_library_betonio
   └─ User: root → ifXXXXXXX
   └─ Password: (empty) → (your strong password)

2. APP URL
   └─ http://localhost/library_betonio
   └─ ↓
   └─ https://username.infinityfree.com

3. DEBUG MODE
   └─ APP_DEBUG: true → false
   └─ (Hide errors from users in production)

4. EMAIL CREDENTIALS
   └─ Set up Gmail App Password
   └─ Add to .env.production

5. CREATE .env.production
   └─ Only on server (not uploaded)
   └─ Contains production secrets

What STAYS the SAME:

- All PHP code
- All HTML/CSS/JS
- Database schema (tables)
- File structure
- includes/config.php (still reads .env.production)


## FILE LOCATIONS
===================

Important files to know:

Local Development:
  C:\xampp\htdocs\library_betonio\.env
  └─ Your development configuration

InfinityFree Production:
  public_html/library_betonio/.env.production
  └─ Your production configuration (CREATE THIS)

Configuration Reader (Same on both):
  includes/config.php
  └─ Reads .env or .env.production automatically

Database Connection (Same on both):
  backend/config/Database.php
  └─ Uses database credentials from config.php


## TROUBLESHOOTING
===================

"Connection refused" or "Unable to connect"
  ✓ Check .env.production has correct host/port
  ✓ Verify database credentials
  ✓ Check database was created
  ✓ Try test-connection-prod.php

"Page not found" or 404 errors
  ✓ Check files uploaded to correct folder
  ✓ Verify .env.production exists
  ✓ Check htaccess configuration
  ✓ Ask InfinityFree support

"Email not sending"
  ✓ Verify Gmail App Password created
  ✓ Check MAIL_USER and MAIL_PASS in .env.production
  ✓ Enable "Less secure apps" or use App Password
  ✓ Check email configuration

"Error 500 - Internal Server Error"
  ✓ Check error logs in cPanel
  ✓ Verify .env.production syntax (no extra spaces)
  ✓ Check PHP version compatibility
  ✓ Verify all included files exist


## REFERENCE DOCUMENTS
=======================

For more details, see:

1. .env.production.example
   └─ Template with 285 lines of documentation
   └─ Explains each setting

2. DATABASE_CONNECTION_GUIDE.md
   └─ Database troubleshooting
   └─ Common issues and fixes

3. DEPLOYMENT_READY.md
   └─ Overall deployment guide
   └─ Security considerations


## QUICK REFERENCE - What to Change
====================================

DATABASE:
  DB_HOST=sql309.infinityfree.com
  DB_PORT=3306
  DB_NAME=ifXXXXXXX_library_betonio (from cPanel)
  DB_USER=ifXXXXXXX (from cPanel)
  DB_PASS=YourDatabasePassword

APP:
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://username.infinityfree.com
  APP_BASE_PATH=

EMAIL:
  MAIL_HOST=smtp.gmail.com
  MAIL_PORT=587
  MAIL_USER=your_email@gmail.com
  MAIL_PASS=YourGmailAppPassword

ADMIN:
  ADMIN_USERNAME=admin
  ADMIN_PASSWORD=YourStrongPassword


## FINAL CHECKLIST
===================

Before considering deployment complete:

- [ ] InfinityFree account created
- [ ] Database credentials obtained
- [ ] Files uploaded via FTP
- [ ] .env.production created on server
- [ ] Database tables created/imported
- [ ] test-connection-prod.php shows ✅
- [ ] Main app loads without errors
- [ ] Can register and login
- [ ] Email sending works
- [ ] No errors in cPanel error logs


═════════════════════════════════════════════════════════════════════════════
                         🚀 DEPLOYMENT COMPLETE 🚀
═════════════════════════════════════════════════════════════════════════════
