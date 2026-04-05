# QueenLib InfinityFree Deployment Guide
## THE SINGLE-FILE APPROACH 🚀

> **TL;DR:** Change ONE file (`.env.production`) and upload. Everything else is already configured!

---

## 🎯 The Concept

This application is designed so you **only need to change ONE file** to deploy to InfinityFree:

- **File to edit:** `.env.production`
- **Action:** Fill in your InfinityFree credentials
- **Upload:** Send all files to InfinityFree via FTP
- **Result:** Application runs automatically! ✅

The code itself doesn't change. The application automatically detects it's running on InfinityFree and loads the correct configuration.

---

## 📋 STEP-BY-STEP DEPLOYMENT

### STEP 1: Collect Your InfinityFree Information
**Time: 2-5 minutes**

Before you start, gather this information from InfinityFree:

#### From Welcome Email:
- [ ] **Domain/Subdomain:** `https://username.infinityfree.com` (or your custom domain)
- [ ] **FTP Host:** `ftpxx.infinityfree.com` 
- [ ] **FTP Username:** (usually same as account username)
- [ ] **FTP Password:** (from welcome email)

#### From cPanel (Log in to cPanel):
- [ ] **Database Host:** Go to MySQL Databases → look for host (usually `sql309.infinityfree.com`)
- [ ] **Database Name:** Format is `ifXXXXXXX_libraryname` (find in MySQL Databases)
- [ ] **Database User:** Format is `ifXXXXXXX_user` (created in MySQL Databases)
- [ ] **Database Password:** (you set this when creating the database user)

**Checklist for collecting info:**
```
Domain/URL:                 https://___________________
FTP Host:                   ftpXX.infinityfree.com
FTP Username:               ___________________
FTP Password:               ___________________
MySQL Host:                 sql309.infinityfree.com
MySQL Database:             ifXXXXXXX_libraryname
MySQL Username:             ifXXXXXXX_user
MySQL Password:             ___________________
```

---

### STEP 2: Edit the ONE Configuration File
**Time: 5 minutes**

1. **Open the file:** `.env.production`
   - Location: Root directory of the project
   - If it doesn't exist, copy `.env.production.example` and rename to `.env.production`

2. **Fill in your values:**

```ini
# APPLICATION CONFIGURATION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourusername.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

# DATABASE CONFIGURATION (Use values from Step 1)
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=ifXXXXXXX_libraryname
DB_USER=ifXXXXXXX_user
DB_PASS=YourDatabasePassword123
DB_CHARSET=utf8mb4

# EMAIL CONFIGURATION (Optional - for password resets)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=YourGmailAppPassword
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

# ADMIN CONFIGURATION (Change these!)
ADMIN_USERNAME=admin
ADMIN_PASSWORD=YourSecureAdminPassword123

# SECURITY
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=false
```

**Key points:**
- 🔴 **MUST CHANGE:** `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `ADMIN_PASSWORD`
- 🟡 **SHOULD CHANGE:** `MAIL_*` settings (for email features)
- 🟢 **CAN KEEP:** Everything else has sensible defaults

**Example filled file:**
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://johndoe.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if8765432_library_betonio
DB_USER=if8765432_admin
DB_PASS=SecurePassword2026!
DB_CHARSET=utf8mb4

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=john@example.com
MAIL_PASS=abcd efgh ijkl mnop
MAIL_FROM=john@example.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

ADMIN_USERNAME=admin
ADMIN_PASSWORD=AdminPass2026!

SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=false
```

3. **Save the file** (keep it in your local project folder)

---

### STEP 3: Upload to InfinityFree via FTP
**Time: 5-10 minutes**

#### Option A: Using FileZilla (Recommended)

1. **Download FileZilla** (free): https://filezilla-project.org/

2. **Connect to FTP:**
   - Host: `ftpXX.infinityfree.com` (from Step 1)
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: 21
   - Click "Quickconnect"

3. **Navigate to upload folder:**
   - On the right panel (Remote site), navigate to `public_html`
   - This is your web root directory

4. **Upload your files:**
   - From left panel (Local site): Select ALL files in your project folder
   - Drag them to right panel (Remote site) in `public_html`
   - **Important:** Include the `.env.production` file you edited
   - **Important:** Do NOT include `.env` (your localhost config)
   - **Important:** Do NOT include `.git` or `testsprite_tests` folders

5. **Verify upload:**
   - Check that files appear in Remote site panel
   - Look for these files: `index.php`, `login.php`, `includes/config.php`

#### Option B: Using cPanel File Manager

1. Log in to cPanel
2. Find "File Manager"
3. Navigate to `public_html`
4. Upload your files using the upload button
5. (Same file selection as Option A)

---

### STEP 4: Create the Database
**Time: 5 minutes**

1. **Log in to cPanel**

2. **Go to "MySQL Databases"**

3. **Create a new database:**
   - Name: Use exactly what you put in `.env.production` → `DB_NAME`
   - Click "Create Database"

4. **Create a database user:**
   - Username: Use exactly what you put in `.env.production` → `DB_USER`
   - Password: Use exactly what you put in `.env.production` → `DB_PASS`
   - Click "Create User"

5. **Grant permissions:**
   - Add the user to the database
   - Grant ALL privileges
   - Click "Make Changes"

6. **Verify:**
   - Go to phpMyAdmin
   - Log in with your new username/password
   - Confirm you can access your database

---

### STEP 5: Initialize Database (First Time Only)
**Time: 2 minutes**

1. **Visit this URL in your browser:**
   ```
   https://yourusername.infinityfree.com/init-database.php
   ```
   (Replace `yourusername` with your actual username)

2. **You should see:**
   - ✅ "Database initialized successfully"
   - Tables created: `users`, `login_history`, `otp_codes`, `verification_attempts`

3. **If you get an error:**
   - Check that `.env.production` is uploaded correctly
   - Verify database credentials in Step 4
   - Check database name, username, password match `.env.production`

---

### STEP 6: Verify Everything Works
**Time: 2 minutes**

Visit these URLs to verify:

1. **Homepage:**
   ```
   https://yourusername.infinityfree.com
   ```
   Should see: QueenLib welcome page with "Log In" and "Get Started" buttons

2. **Login page:**
   ```
   https://yourusername.infinityfree.com/login.php
   ```
   Should see: Login form

3. **Registration page:**
   ```
   https://yourusername.infinityfree.com/register.php
   ```
   Should see: Registration form

4. **Connection test (optional):**
   ```
   https://yourusername.infinityfree.com/test-connection.php
   ```
   Should see: ✅ "Database Connection Successful!"

---

## ✅ Verification Checklist

After completing all steps, verify:

- [ ] `.env.production` file is uploaded to server
- [ ] All application files are in `public_html`
- [ ] Database is created in cPanel
- [ ] Database user is created and has all privileges
- [ ] Homepage loads at `https://yourusername.infinityfree.com`
- [ ] Login page is accessible
- [ ] Registration page is accessible
- [ ] Database test shows ✅ (optional)

---

## 🐛 Troubleshooting

### Problem: "Database connection failed"
**Solution:**
1. Check `.env.production` is uploaded
2. Verify database name, user, password in cPanel match `.env.production`
3. Confirm database user has ALL privileges
4. Check `test-connection.php` for specific error message

### Problem: Page shows "Blank page"
**Solution:**
1. Check that `public_html` contains files (not in a subfolder)
2. Verify Apache is serving the files correctly
3. Try visiting `test-connection.php` to see more details

### Problem: "App URL doesn't match"
**Solution:**
- Set `APP_URL` in `.env.production` to exactly match your domain
- If using subdomain: `https://username.infinityfree.com`
- If using custom domain: `https://yourdomain.com`
- Must include `https://` (InfinityFree provides free SSL)

### Problem: Database initialization fails
**Solution:**
1. Make sure database was created in Step 4
2. Check credentials in `.env.production` are correct
3. Visit phpMyAdmin to manually verify database exists
4. Try `init-database.php` again

### Problem: Email features don't work
**Solution:**
1. Email is optional - the app works without it
2. If needed, configure `MAIL_*` settings in `.env.production`
3. For Gmail: Enable "App Passwords" in Gmail settings
4. Copy your Gmail App Password exactly into `MAIL_PASS`

---

## 🔒 Security Reminders

1. **Never commit `.env.production` to Git**
   - It contains your database password!
   - The file should be in `.gitignore`

2. **Keep `.env.production` secret**
   - Don't share it with anyone
   - Delete it if you no longer need it
   - In production, consider making it read-only

3. **Use strong passwords**
   - Database password: 20+ characters
   - Admin password: 15+ characters
   - Mix uppercase, lowercase, numbers, symbols

4. **Change admin credentials**
   - Don't leave as `admin:admin123`
   - Update to something unique

---

## 📊 File Structure on InfinityFree

After successful deployment, your `public_html` should contain:

```
public_html/
├── .env.production          ← THE ONE FILE YOU CHANGED
├── .htaccess
├── index.php
├── login.php
├── register.php
├── admin-login.php
├── forgot-password.php
├── verify-otp.php
├── account.php
├── admin-dashboard.php
├── logout.php
├── includes/
│   ├── config.php
│   ├── auth.php
│   └── functions.php
├── backend/
│   ├── api/
│   ├── classes/
│   ├── config/
│   └── mail/
├── public/
│   ├── css/
│   └── js/
├── images/
├── init-database.php
├── test-connection.php
└── ... (other files)
```

---

## 🎉 Done!

Your QueenLib application is now live on InfinityFree! 

**Next steps:**
- Test the registration and login flow
- Create test admin account
- Configure email if needed (optional)
- Invite users to your library

---

## 📞 Need Help?

If something doesn't work:
1. Check `test-connection.php` for database status
2. Review the `.env.production` values against your InfinityFree account
3. Check that database user has "ALL" privileges (not just SELECT)
4. Make sure `.env.production` was actually uploaded to the server

---

**Last Updated:** March 2026  
**Application:** QueenLib  
**Deployment Target:** InfinityFree Hosting
