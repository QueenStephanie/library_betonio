# QueenLib InfinityFree Deployment Guide

This guide prepares the current PHP project for InfinityFree shared hosting.

## 1) Prepare local project

- Make sure `backend/vendor/` exists (PHPMailer is included already in this project).
- Confirm `.htaccess` exists in the project root.
- Confirm `.env.production.example` exists.

## 2) Create InfinityFree database

1. Open InfinityFree Control Panel.
2. Go to `MySQL Databases`.
3. Create a database.
4. Save these values:
   - MySQL Host (for example: `sql123.infinityfree.com`)
   - Database Name (for example: `if0_12345678_queenlib`)
   - Database User (for example: `if0_12345678`)
   - Database Password

## 3) Import schema

1. Open phpMyAdmin from InfinityFree panel.
2. Select your new database.
3. Import `backend/config/schema.sql`.

## 4) Upload files

1. Open File Manager (or FTP client).
2. Upload project into `htdocs`.
3. If app is at root, `index.php` should be directly inside `htdocs`.
4. If app is in subfolder, note the subfolder path (example: `/queenlib`).

## 5) Create `.env.production`

Create `.env.production` in project root (same folder as `index.php`) using this template:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.epizy.com
APP_BASE_PATH=

DB_HOST=sql123.infinityfree.com
DB_PORT=3306
DB_NAME=if0_12345678_queenlib
DB_USER=if0_12345678
DB_PASS=your_mysql_password

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=your-app-password
MAIL_FROM=your-email@gmail.com
MAIL_FROM_NAME=QueenLib

ADMIN_USERNAME=admin
ADMIN_PASSWORD=change_this_now

APP_TIMEZONE=UTC
SESSION_TIMEOUT=3600
BCRYPT_COST=12
```

Notes:
- If your app is in a subfolder, set `APP_BASE_PATH=/your-subfolder`.
- Keep `.env.production` private. It is git-ignored and blocked by `.htaccess`.

## 6) Verify protected files and folders

Try visiting these URLs in browser and confirm access is denied:
- `https://yourdomain.epizy.com/.env.production`
- `https://yourdomain.epizy.com/includes/config.php`
- `https://yourdomain.epizy.com/docs/`

## 7) Functional checks

Run these tests on live site:
- Open home page
- Register account
- Verify email link opens correct domain
- Login/logout
- Forgot password and reset password email link
- Admin login (`admin-login.php`)

## 8) Common InfinityFree fixes

- **Database connection failed**
  - Recheck `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in `.env.production`
  - Ensure `DB_PORT=3306`

- **Styles or JS not loading**
  - Recheck `APP_BASE_PATH`
  - If app in subfolder, `APP_BASE_PATH` must match exactly

- **Email not sending**
  - Use valid SMTP provider credentials
  - For Gmail, use App Password (not normal account password)

- **Redirect loops**
  - Keep HTTPS redirect lines in `.htaccess` commented unless SSL is fully active

## 9) Security reminders

- Change default admin credentials immediately.
- Never upload `.git/` directory.
- Keep `APP_DEBUG=false` in production.
- Rotate database and SMTP passwords if exposed.
