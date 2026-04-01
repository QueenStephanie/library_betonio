# QueenLib - Infinity Free Deployment Guide

## Overview

This guide covers deploying QueenLib to Infinity Free hosting. The application uses PHP and MySQL, making it perfect for Infinity Free's stack.

**Deployment Time:** ~30 minutes  
**Difficulty:** Beginner-friendly

---

## Pre-Deployment Checklist

- [ ] Infinity Free account created
- [ ] Database credentials obtained from cPanel
- [ ] FTP credentials obtained from cPanel
- [ ] Gmail account ready with App Password
- [ ] All project files ready to deploy
- [ ] .env.example reviewed for all required variables

---

## Phase 1: Account Setup (5 minutes)

### 1.1 Create Infinity Free Account

1. Visit: https://www.infinityfree.net
2. Click "Sign Up"
3. Enter email and create password
4. Verify email (check inbox)
5. Choose subdomain: `username.infinityfree.com`
6. Accept terms and complete signup

### 1.2 Get Your Credentials

**From Welcome Email or cPanel:**

Access cPanel at: `https://username.infinityfree.com:2083`
- Username: Your cPanel username
- Password: Your cPanel password

**Save These Credentials:**

```
Account Username: ___________________
cPanel URL: ___________________
cPanel Password: ___________________
FTP Host: ftp.username.infinityfree.com
FTP Username: ___________________
FTP Password: ___________________
```

---

## Phase 2: Database Setup (5 minutes)

### 2.1 Create Database

In cPanel:

1. Find "MySQL Databases"
2. Create new database
3. Name: Can be anything (will be prefixed with `ifXXXXXXX_`)
4. Example: `ifXXXXXXX_library_betonio`

### 2.2 Create Database User

In cPanel MySQL section:

1. Create new user
2. Username: Will be prefixed with `ifXXXXXXX_`
3. Password: Create strong password
4. Assign user to database with all privileges

### 2.3 Note Your Database Credentials

```
Database Host: sql309.infinityfree.com (or similar)
Database Port: 3306
Database Name: ifXXXXXXX_library_betonio
Database User: ifXXXXXXX_yourusername
Database Password: ___________________
```

---

## Phase 3: File Upload (10 minutes)

### 3.1 Download FTP Client

Recommended: **FileZilla** (free for Windows/Mac/Linux)
- Download: https://filezilla-project.org/

### 3.2 Connect via FTP

In FileZilla:

1. File → Site Manager → New Site
2. Enter credentials:
   - Host: `ftp.username.infinityfree.com`
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
3. Click "Connect"

### 3.3 Upload Files

1. Navigate to `public_html` folder on server
2. Right-click → Create Directory → Name: `library_betonio`
3. Enter that directory
4. Upload all project files
5. **DO NOT upload** `.env`, `.env.example`, or `.env.production`
6. Wait for upload to complete (5-10 minutes)

---

## Phase 4: Configuration (8 minutes)

### 4.1 Create .env.production

**⚠️ Important:** Only create this file on the server, never upload it

In cPanel:

1. Go to File Manager
2. Navigate to `public_html/library_betonio`
3. Right-click → Create New File
4. Name: `.env.production` (exactly this)
5. Click Create

### 4.2 Edit .env.production

1. Right-click `.env.production` → Edit
2. Copy and paste this content:

```env
# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://username.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

# Database Configuration
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=ifXXXXXXX_library_betonio
DB_USER=ifXXXXXXX_yourusername
DB_PASS=your_database_password
DB_CHARSET=utf8mb4

# Email Configuration (Gmail)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your_email@gmail.com
MAIL_PASS=your_gmail_app_password
MAIL_FROM=your_email@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

# Admin Settings
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_strong_password

# Session Settings
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=true
```

### 4.3 Update with Your Values

Replace these placeholders:

| Placeholder | Replace With |
|---|---|
| `username` | Your Infinity Free subdomain |
| `ifXXXXXXX_library_betonio` | Your actual database name from cPanel |
| `ifXXXXXXX_yourusername` | Your actual database user from cPanel |
| `your_database_password` | Database password you created |
| `your_email@gmail.com` | Your Gmail address |
| `your_gmail_app_password` | Gmail App Password (see 4.4) |
| `your_strong_password` | Strong admin password you choose |

### 4.4 Setup Gmail App Password

To allow email sending:

1. Go to: https://myaccount.google.com
2. Click "Security" on left sidebar
3. Enable "2-Step Verification" if not enabled
4. Go to: https://myaccount.google.com/apppasswords
5. Select: Mail → Windows Computer
6. Google generates 16-character password
7. Copy it (remove spaces)
8. Paste in MAIL_PASS in .env.production

---

## Phase 5: Database Setup (5 minutes)

### 5.1 Import Database Schema

**Option A: From phpMyAdmin (Recommended)**

1. On localhost, open: http://localhost/phpmyadmin
2. Select: `library_betonio` database
3. Click "Export"
4. Format: SQL
5. Click "Go" to download SQL file

Then import on Infinity Free:

1. In cPanel → PhpMyAdmin
2. Select your database: `ifXXXXXXX_library_betonio`
3. Click "Import"
4. Choose your SQL file
5. Click "Import"

**Option B: Using init-database.php**

1. Visit: `https://username.infinityfree.com/library_betonio/init-database.php`
2. Follow prompts to initialize database

---

## Phase 6: Verification (2 minutes)

### 6.1 Test Database Connection

1. In cPanel File Manager, create file: `test-connection.php`
2. Add this content:

```php
<?php
require_once 'includes/config.php';

echo "<h3>Connection Test</h3>";
echo "Host: " . DB_HOST . "<br>";
echo "Database: " . DB_NAME . "<br>";
echo "User: " . DB_USER . "<br>";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
    $conn = new PDO($dsn, DB_USER, DB_PASSWORD);
    echo "✅ <b>CONNECTION SUCCESSFUL!</b>";
} catch (PDOException $e) {
    echo "❌ <b>CONNECTION FAILED</b><br>";
    echo "Error: " . $e->getMessage();
}
?>
```

3. Visit: `https://username.infinityfree.com/library_betonio/test-connection.php`
4. Should show: ✅ CONNECTION SUCCESSFUL

### 6.2 Test Application

1. Visit: `https://username.infinityfree.com/library_betonio/`
2. Check if login page loads
3. Images should display
4. No errors should appear

### 6.3 Test Registration

1. Try creating test account
2. Verify email should arrive
3. Complete registration
4. Try logging in

---

## Common Issues & Solutions

### Issue: "Connection Refused"

**Check:**
- [ ] .env.production has correct database host
- [ ] Database was created in cPanel
- [ ] Database user has privileges
- [ ] Database password is correct

**Fix:** Review .env.production values with cPanel credentials

---

### Issue: "Table 'library_betonio.users' doesn't exist"

**Check:**
- [ ] Database was imported/initialized
- [ ] SQL file was valid
- [ ] init-database.php was run

**Fix:** Import database schema again or run init-database.php

---

### Issue: "Email Not Sending"

**Check:**
- [ ] Gmail App Password created (not account password)
- [ ] MAIL_USER and MAIL_PASS are correct
- [ ] 2-Factor Authentication enabled on Gmail

**Fix:** Generate new Gmail App Password and update .env.production

---

### Issue: "500 Error"

**Check:**
- [ ] cPanel Error Logs for specific error
- [ ] .env.production syntax (no stray characters)
- [ ] All required files uploaded

**Go to:** cPanel → Logs → Error Logs

---

### Issue: "Connection Failed - 404 Not Found"

**Check:**
- [ ] Files uploaded to `public_html/library_betonio`
- [ ] .htaccess file exists in project root
- [ ] .env.production exists

**Fix:** Verify file upload destination

---

## Security Checklist

Before considering deployment complete:

- [ ] APP_DEBUG is set to `false`
- [ ] Using `https://` (Infinity Free provides free SSL)
- [ ] .env.production exists (not .env)
- [ ] .env file NOT uploaded to server
- [ ] Admin password is strong (12+ characters, mixed case, numbers, symbols)
- [ ] Database password is strong
- [ ] No error messages reveal database details

---

## Performance Tips

1. **Enable Caching:**
   - Set CACHE_DRIVER in .env.production

2. **Optimize Images:**
   - Compress images before upload
   - Use appropriate file formats (WebP where possible)

3. **Monitor Bandwidth:**
   - Infinity Free has bandwidth limits
   - Monitor from cPanel
   - Optimize database queries

---

## File Reference

Important files in your project:

```
library_betonio/
├── .env.example              ← Development template
├── .htaccess                 ← URL rewriting rules
├── index.php                 ← Entry point
├── includes/
│   ├── config.php           ← Reads .env.production
│   └── ...
├── backend/
│   ├── config/Database.php  ← Database connection
│   └── ...
├── public/
│   ├── css/
│   ├── js/
│   └── images/
└── docs/
    └── 00-DEPLOYMENT_GUIDE.md (this file)
```

---

## Support Resources

If you need more detailed information:

- **Database Issues:** See `docs/DATABASE_CONNECTION_GUIDE.md`
- **Configuration:** See `.env.example` for all variables
- **Production Setup:** See `docs/INFINITYFREE_HOSTING_GUIDE.md`
- **Troubleshooting:** See `docs/INFINITYFREE_DEPLOYMENT_CHECKLIST.md`

---

## Next Steps

After deployment:

1. Monitor application for errors
2. Check cPanel logs regularly
3. Backup database regularly (cPanel provides backups)
4. Monitor bandwidth usage
5. Keep dependencies updated

---

## Deployment Summary

**What you did:**
✅ Created Infinity Free account  
✅ Set up database and user  
✅ Uploaded files via FTP  
✅ Created .env.production with credentials  
✅ Configured email with Gmail  
✅ Imported database schema  
✅ Tested connection and application  

**Result:** Your QueenLib application is now live!

**Access:** https://username.infinityfree.com/library_betonio/

---

**Last Updated:** March 2026  
**For Issues:** Check cPanel Logs → Error Logs
