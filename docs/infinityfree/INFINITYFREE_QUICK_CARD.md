INFINITYFREE QUICK REFERENCE CARD
==================================

WHAT CHANGES
=============

LOCAL                               INFINITYFREE
─────────────────────────────────────────────────────
localhost:3307                      sql309.infinityfree.com:3306
library_betonio                     ifXXXXXXX_library_betonio
root / no password                  ifXXXXXXX / strong_password
http://localhost/library_betonio    https://username.infinityfree.com
.env                                .env.production


STEP-BY-STEP CHECKLIST
======================

1. [ ] Create InfinityFree account
2. [ ] Get database credentials from cPanel
3. [ ] Download FTP client (FileZilla)
4. [ ] Upload files to public_html/library_betonio
5. [ ] Create .env.production file in cPanel File Manager
6. [ ] Add database credentials to .env.production
7. [ ] Setup Gmail for email sending
8. [ ] Test connection: https://username.infinityfree.com/library_betonio/test-connection-prod.php
9. [ ] Import database or run init script
10. [ ] Visit main app and test registration/login


.env.PRODUCTION TEMPLATE FOR INFINITYFREE
===========================================

Copy this and replace YOUR VALUES:

───────────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR_USERNAME.infinityfree.com
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
───────────────────────────────────────────────────


FIND YOUR CREDENTIALS
======================

1. Database Credentials
   ├─ Log in to cPanel: https://YOUR_USERNAME.infinityfree.com:2083
   ├─ Find: MySQL Databases or PhpMyAdmin
   ├─ Create database (name will start with ifXXXXXXX_)
   ├─ Note down:
   │  ├─ DB_HOST: sql309.infinityfree.com (or similar)
   │  ├─ DB_NAME: ifXXXXXXX_library_betonio
   │  ├─ DB_USER: ifXXXXXXX
   │  ├─ DB_PASS: (create strong one)
   │  └─ DB_PORT: 3306
   └─ Save these!

2. FTP Credentials
   ├─ In cPanel: Find FTP Accounts
   ├─ Note down:
   │  ├─ Host: ftp.USERNAME.infinityfree.com
   │  ├─ Username: your FTP username
   │  ├─ Password: your FTP password
   │  └─ Port: 21
   └─ Use with FileZilla

3. Gmail App Password
   ├─ Go to: myaccount.google.com/apppasswords
   ├─ Select: Mail and Windows Computer
   ├─ Copy 16-character password (remove spaces)
   └─ Put in MAIL_PASS


KEY DIFFERENCES
================

These are the ONLY things that change:

1. Database Connection
   └─ FROM: localhost on port 3307
   └─ TO: sql309.infinityfree.com on port 3306
   └─ Database prefix required

2. Application URL
   └─ FROM: http://localhost/library_betonio
   └─ TO: https://username.infinityfree.com
   └─ Always use https (secure)

3. Debug Mode
   └─ FROM: APP_DEBUG=true (show all errors)
   └─ TO: APP_DEBUG=false (hide errors in production)

4. Create .env.production
   └─ FROM: .env file on localhost
   └─ TO: .env.production file on server (not uploaded)

5. Email Settings
   └─ FROM: Localhost email (might not work)
   └─ TO: Gmail SMTP with App Password


FILE PERMISSIONS (Optional but Recommended)
============================================

After creating .env.production, set permissions:

In cPanel > File Manager:
├─ Right-click .env.production
├─ Click "Change Permissions"
├─ Set to 600 (owner can read/write only)
└─ This protects your credentials


TROUBLESHOOTING
================

Problem                          Solution
────────────────────────────────────────────
Connection refused               → Check DB credentials in .env.production
Page shows blank                 → Check error logs in cPanel
Email not sending                → Verify MAIL_PASS is Gmail App Password
Database error                   → Verify database was created in cPanel
Files not showing up             → Check upload destination (public_html)
500 Error                        → Check PHP version compatibility


TESTING
========

Test 1: Can you see login page?
  └─ Visit: https://username.infinityfree.com/library_betonio
  └─ Should show login form

Test 2: Is database connected?
  └─ Visit: https://username.infinityfree.com/library_betonio/test-connection-prod.php
  └─ Should show: ✅ CONNECTION SUCCESSFUL

Test 3: Can you register?
  └─ Click Register on login page
  └─ Fill in form
  └─ Submit
  └─ Check email for verification

Test 4: Can you login?
  └─ Verify email
  └─ Login with credentials
  └─ Should see dashboard


COMMON VALUES
==============

Most InfinityFree users have similar settings:

Typical Database Host:
  sql309.infinityfree.com
  (or sql310, sql311, etc.)

Typical Database Name Pattern:
  ifXXXXXXX_library_betonio
  (where XXXXXX is your account ID)

Typical Database User:
  ifXXXXXXX
  (matches the prefix)

Typical FTP Host:
  ftp.username.infinityfree.com

Typical URL:
  https://username.infinityfree.com

Typical Control Panel:
  https://username.infinityfree.com:2083


DOES NOT CHANGE
================

These files stay the same (no modifications needed):

✓ All PHP files
✓ All HTML/CSS/JS
✓ Database schema
✓ includes/config.php
✓ backend/ directory
✓ public/ directory
✓ All application code

The ONLY file you create/change is: .env.production


IMPORTANT NOTES
================

❌ DO NOT:
  - Upload .env file (development only)
  - Hardcode credentials in PHP files
  - Use weak passwords
  - Leave debug mode on (APP_DEBUG=true)
  - Commit .env.production to git

✅ DO:
  - Create .env.production only on server
  - Use strong passwords (20+ characters)
  - Set APP_DEBUG=false
  - Use Gmail App Password (not account password)
  - Keep credentials secret
  - Test everything after uploading


NEED MORE HELP?
================

Detailed guides in your project:

1. INFINITYFREE_HOSTING_GUIDE.md
   └─ Full step-by-step guide with explanations

2. .env.production.example
   └─ 285 lines of detailed documentation

3. DATABASE_CONNECTION_GUIDE.md
   └─ Database troubleshooting

4. DEPLOYMENT_READY.md
   └─ Overall deployment guide


═════════════════════════════════════════════════════════════════════════════
                    READY TO DEPLOY? USE THIS QUICK CARD!
═════════════════════════════════════════════════════════════════════════════
