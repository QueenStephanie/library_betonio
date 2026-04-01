INFINITYFREE DEPLOYMENT CHECKLIST
==================================

Follow this step-by-step to deploy QueenLib to InfinityFree


PHASE 1: ACCOUNT & CREDENTIALS (15 minutes)
═════════════════════════════════════════════

☐ STEP 1: Create InfinityFree Account
  URL: https://www.infinityfree.net
  - Click "Sign Up"
  - Enter email and password
  - Verify email (check inbox)
  - Choose subdomain: username.infinityfree.com
  
  SAVE THIS:
  ├─ Username: _________________
  ├─ Password: _________________
  └─ Subdomain: _________________

☐ STEP 2: Access Control Panel
  URL: https://username.infinityfree.com:2083
  - Login with your credentials
  - Bookmark this page
  
  SAVE THIS:
  ├─ cPanel Username: _________________
  └─ cPanel Password: _________________

☐ STEP 3: Create Database
  In cPanel:
  - Find "MySQL Databases" section
  - Create new database
  - Note the full name (will start with ifXXXXXXX_)
  
  SAVE THIS:
  ├─ DB_HOST: sql309.infinityfree.com (or sql310, sql311)
  ├─ DB_NAME: _________________
  ├─ DB_USER: _________________
  └─ DB_PASS: _________________

☐ STEP 4: Get FTP Credentials
  In cPanel:
  - Find "FTP Accounts" section
  - Note or create FTP account
  
  SAVE THIS:
  ├─ FTP Host: ftp.username.infinityfree.com
  ├─ FTP Username: _________________
  ├─ FTP Password: _________________
  └─ FTP Port: 21

☐ STEP 5: Setup Gmail (if using Gmail for emails)
  - Enable 2-Factor Authentication on Gmail
  - Generate App Password at myaccount.google.com/apppasswords
  - Select: Mail + Windows Computer
  - Copy 16-character password
  
  SAVE THIS:
  ├─ Gmail Address: _________________
  └─ Gmail App Password: _________________


PHASE 2: UPLOAD FILES (20 minutes)
════════════════════════════════════

☐ STEP 6: Download FTP Client
  Recommended: FileZilla
  - Download from: filezilla-project.org
  - Install on your computer

☐ STEP 7: Connect via FTP
  In FileZilla:
  - Host: ftp.username.infinityfree.com
  - Username: Your FTP username
  - Password: Your FTP password
  - Port: 21
  - Click "Connect"

☐ STEP 8: Navigate to Upload Folder
  - In right panel (server), find: public_html
  - Double-click to enter public_html
  - This is your web root

☐ STEP 9: Upload Project Files
  - In left panel (local), browse to C:\xampp\htdocs\library_betonio
  - Select ALL files in library_betonio
  - Right-click > Upload
  - Wait for completion (5-10 minutes)
  
  Check that uploaded:
  ├─ backend/
  ├─ includes/
  ├─ public/
  ├─ images/
  ├─ docs/
  ├─ index.php
  ├─ login.php
  ├─ register.php
  ├─ .env.example
  ├─ .env.production.example
  ├─ .htaccess
  └─ All other PHP files

☐ STEP 10: Verify Upload Location
  Expected path in cPanel File Manager:
  └─ public_html/library_betonio/index.php
  
  Check:
  - Open cPanel > File Manager
  - Navigate to public_html
  - Should see library_betonio folder
  - Inside should be all your files


PHASE 3: CREATE CONFIGURATION (10 minutes)
════════════════════════════════════════════

☐ STEP 11: Create .env.production File
  In cPanel File Manager:
  - Navigate to: public_html/library_betonio
  - Right-click in empty area
  - Click "Create New File"
  - Name: .env.production
  - Click Create

☐ STEP 12: Edit .env.production
  - Right-click .env.production
  - Click "Edit"
  - Copy-paste content from template (see below)
  - Replace YOUR VALUES
  - Click Save

☐ STEP 13: Add Database Credentials
  In .env.production, update these:
  ├─ DB_HOST: sql309.infinityfree.com
  ├─ DB_NAME: ifXXXXXXX_library_betonio
  ├─ DB_USER: ifXXXXXXX
  ├─ DB_PASS: YOUR_PASSWORD
  └─ (Use values from STEP 3)

☐ STEP 14: Add App Configuration
  In .env.production, update these:
  ├─ APP_URL: https://username.infinityfree.com
  ├─ APP_DEBUG: false
  └─ ADMIN_PASSWORD: YOUR_STRONG_PASSWORD

☐ STEP 15: Add Email Configuration
  In .env.production, update these:
  ├─ MAIL_HOST: smtp.gmail.com
  ├─ MAIL_USER: your_email@gmail.com
  ├─ MAIL_PASS: YOUR_GMAIL_APP_PASSWORD
  └─ MAIL_FROM: your_email@gmail.com

☐ STEP 16: Set File Permissions (Optional but Recommended)
  - Right-click .env.production
  - Click "Change Permissions"
  - Set to: 600 (owner read/write only)
  - Click "Change"


PHASE 4: SETUP DATABASE (10 minutes)
══════════════════════════════════════

☐ STEP 17: Import Database Schema
  Option A - From Local Backup (Recommended):
    1. On localhost, export database:
       - PhpMyAdmin > library_betonio > Export
       - Format: SQL
       - Click Go
       - Saves SQL file
    
    2. On InfinityFree, import:
       - cPanel > PhpMyAdmin
       - Select your database
       - Import tab
       - Choose SQL file
       - Click Import
  
  Option B - Run Init Script:
    1. Visit: https://username.infinityfree.com/library_betonio/init-database.php
    2. Follow steps


PHASE 5: TESTING (15 minutes)
═══════════════════════════════

☐ STEP 18: Test Database Connection
  Visit: https://username.infinityfree.com/library_betonio/test-connection-prod.php
  
  Should see:
  ✅ DATABASE CONNECTION SUCCESSFUL
  ✅ QUERY TEST PASSED
  ✅ Tables found
  
  If ❌ error:
  - Check .env.production values
  - Verify database name is correct
  - Check database user has permissions
  - See DATABASE_CONNECTION_GUIDE.md


☐ STEP 19: Test Main Application
  Visit: https://username.infinityfree.com/library_betonio/
  
  Check:
  ├─ [ ] Login page loads
  ├─ [ ] Images/CSS display correctly
  ├─ [ ] No PHP errors shown
  └─ [ ] URL shows https (secure)

☐ STEP 20: Test Registration
  - Click "Register"
  - Fill in form with test data
  - Submit
  
  Check:
  ├─ [ ] Form submits without error
  ├─ [ ] Verification email received
  ├─ [ ] Email has valid link
  └─ [ ] Can verify email

☐ STEP 21: Test Login
  - Click verification link in email
  - Login with test credentials
  
  Check:
  ├─ [ ] Email verification successful
  ├─ [ ] Login successful
  ├─ [ ] Dashboard loads
  ├─ [ ] Can access profile
  └─ [ ] No database errors

☐ STEP 22: Check Error Logs
  In cPanel:
  - Find "Error Logs"
  - Check last 20 lines
  - Should be mostly empty or just warnings
  
  If PHP errors:
  - Note the error
  - Fix in code
  - Re-upload file


PHASE 6: FINAL VERIFICATION (5 minutes)
═════════════════════════════════════════

☐ STEP 23: Verify Everything Works
  URL Check:
  ├─ [ ] https:// (not http)
  ├─ [ ] No localhost references
  ├─ [ ] Correct domain name
  
  Functionality Check:
  ├─ [ ] Login page works
  ├─ [ ] Registration works
  ├─ [ ] Email sending works
  ├─ [ ] Database queries work
  ├─ [ ] No console errors (F12)
  └─ [ ] No visible errors on page

☐ STEP 24: Security Check
  - APP_DEBUG should be: false
  - .env.production should exist (not .env)
  - No sensitive data in error messages
  - Database credentials in .env.production only

☐ STEP 25: Backup Check
  - Keep local copy of .env.production
  - Note database credentials somewhere safe
  - Document any custom changes


═════════════════════════════════════════════════════════════════════════════
                            DEPLOYMENT COMPLETE! 
═════════════════════════════════════════════════════════════════════════════


.env.PRODUCTION TEMPLATE
═════════════════════════

Copy-paste into your .env.production file:

───────────────────────────────────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_URL=https://username.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=ifXXXXXXX_library_betonio
DB_USER=ifXXXXXXX
DB_PASS=YOUR_DATABASE_PASSWORD
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your_email@gmail.com
MAIL_PASS=YOUR_GMAIL_APP_PASSWORD
MAIL_FROM=your_email@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

ADMIN_USERNAME=admin
ADMIN_PASSWORD=YOUR_STRONG_PASSWORD
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
───────────────────────────────────────────────────────────────────────────

Replace these:
  - username → your subdomain
  - ifXXXXXXX_library_betonio → your database name
  - ifXXXXXXX → your database user
  - YOUR_DATABASE_PASSWORD → database password from cPanel
  - your_email@gmail.com → your Gmail
  - YOUR_GMAIL_APP_PASSWORD → 16-char password from apppasswords
  - YOUR_STRONG_PASSWORD → strong admin password


CREDENTIALS SAVED
══════════════════

Keep this filled out for reference:

Account Info:
  Subdomain: _________________________________
  cPanel URL: https://__________________:2083

Database Info:
  Host: _________________________________
  Name: _________________________________
  User: _________________________________
  Pass: _________________________________

FTP Info:
  Host: _________________________________
  User: _________________________________
  Pass: _________________________________

Email Info:
  Gmail: _________________________________
  App Password: _________________________________

Admin Login:
  Username: admin
  Password: _________________________________


HELP RESOURCES
═══════════════

Guides in your project:

1. INFINITYFREE_HOSTING_GUIDE.md
   └─ Detailed step-by-step guide

2. INFINITYFREE_QUICK_CARD.md
   └─ Quick reference

3. DATABASE_CONNECTION_GUIDE.md
   └─ Database troubleshooting

4. .env.production.example
   └─ Configuration documentation


═════════════════════════════════════════════════════════════════════════════
                    Follow the checklist from top to bottom!
═════════════════════════════════════════════════════════════════════════════
