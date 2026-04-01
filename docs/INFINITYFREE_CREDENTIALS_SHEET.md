# InfinityFree Setup - Credentials Collection Sheet

## Print this page and fill it out before deploying!

---

## YOUR INFINITYFREE ACCOUNT INFORMATION

### From Welcome Email

Complete these fields from your InfinityFree welcome email:

```
Your Domain:         mikesordilla-lab-queenlib.xo.je

FTP Host:             ftp________________________.infinityfree.com

FTP Username:         if0_41511175

FTP Password:         W6l6QviqpJhS9A2

---

## MYSQL DATABASE INFORMATION

### Step 1: Log into cPanel

- URL: Your InfinityFree account → cPanel
- Username: \***\*\_\_\_\_\*\***
- Password: \***\*\_\_\_\_\*\***

### Step 2: Go to "MySQL Databases" and collect this info:

```
MySQL Host:           sql309.infinityfree.com

MySQL Database Name:  if0_41511175_library_betonio
                      (Look under "Current Databases")

MySQL Username:       if0_41511175
                      (Look under "MySQL Users")

MySQL Password:       W6l6QviqpJhS9A2
                      (You create this when adding user to database)
```

---

## YOUR .env.production FILE TEMPLATE

Copy and paste this into your `.env.production` file, replacing the underscores with your actual values:

```ini
# APPLICATION CONFIGURATION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://USERNAME.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

# DATABASE CONFIGURATION
DB_HOST=sql_________.infinityfree.com
DB_PORT=3306
DB_NAME=if__________library_betonio
DB_USER=if__________admin
DB_PASS=________________________________
DB_CHARSET=utf8mb4

# EMAIL CONFIGURATION (Optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=xxxx xxxx xxxx xxxx
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

# ADMIN CONFIGURATION
ADMIN_USERNAME=admin
ADMIN_PASSWORD=________________________________

# SECURITY
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=false
```

---

## EXAMPLE (FILLED OUT)

Here's what a completed file looks like:

```ini
# APPLICATION CONFIGURATION
APP_ENV=production
APP_DEBUG=false
APP_URL=https://johndoe.infinityfree.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib

# DATABASE CONFIGURATION
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if8765432_library_betonio
DB_USER=if8765432_admin
DB_PASS=SecurePassword2026!AtInfinity
DB_CHARSET=utf8mb4

# EMAIL CONFIGURATION
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=john.doe@gmail.com
MAIL_PASS=abcd efgh ijkl mnop
MAIL_FROM=john.doe@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls

# ADMIN CONFIGURATION
ADMIN_USERNAME=admin
ADMIN_PASSWORD=AdminPassword2026!Secure

# SECURITY
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=false
```

---

## DEPLOYMENT CHECKLIST

### Before Upload

- [ ] I have collected all credentials from Steps 1-2
- [ ] I have filled in `.env.production` with my credentials
- [ ] I saved `.env.production` in the project root directory
- [ ] I double-checked the database credentials match

### FTP Upload

- [ ] FileZilla is installed and running
- [ ] I am connected to FTP server: `ftpXX.infinityfree.com`
- [ ] I navigated to `public_html` folder on remote server
- [ ] I uploaded ALL files including `.env.production`
- [ ] I can see the files in `public_html` on the remote server

### cPanel Database Setup

- [ ] I logged into cPanel
- [ ] I created the MySQL database with the name from `.env.production`
- [ ] I created the MySQL user with the username from `.env.production`
- [ ] I added the user to the database
- [ ] I granted ALL privileges to the user

### Verification

- [ ] I visited `init-database.php` and saw success message
- [ ] I visited the homepage and it loaded
- [ ] I saw the QueenLib welcome page
- [ ] I can click "Log In" and "Get Started" buttons

---

## IMPORTANT SECURITY NOTES

⚠️ **NEVER:**

- Upload `.env` file (your localhost config)
- Commit `.env.production` to Git
- Share `.env.production` with anyone
- Use weak passwords (use 15+ characters, mix case/numbers/symbols)
- Leave admin username as "admin"

✅ **DO:**

- Keep `.env.production` secure on the server
- Use unique, strong passwords
- Change ADMIN_PASSWORD from default
- Test the application after deployment
- Monitor error logs for issues

---

## GETTING GMAIL APP PASSWORD (For Email Features)

If you want email to work (optional):

1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification" if not already enabled
3. Go to "App passwords"
4. Select "Mail" and "Windows Computer"
5. Google will show you a 16-character password
6. Copy it and remove all spaces: `abcdefghijklmnop`
7. Paste into MAIL_PASS in `.env.production`

---

## NEED HELP?

- See full guide: `INFINITYFREE_DEPLOY_SINGLE_FILE.md`
- Quick reference: `INFINITYFREE_QUICK_REF.md`
- Test connection: Visit `/test-connection.php` on your deployed site
- Database issues: Check credentials in `.env.production` match cPanel

---

**Print this page and fill it out before you start!**
