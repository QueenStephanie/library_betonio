# InfinityFree Deployment Guide (QueenLib)

This guide prepares the project for InfinityFree shared hosting.

## 1. Prerequisites

- InfinityFree account with domain/subdomain configured
- MySQL database created from InfinityFree control panel
- FTP client (for example: FileZilla)
- SMTP account for email delivery (Gmail app password or another SMTP provider)

## 2. Upload Files

1. Open your InfinityFree account and find your hosting account.
2. Connect using FTP credentials.
3. Upload the entire project into `htdocs`.
4. If you deploy under a subfolder, note that folder name for `APP_BASE_PATH`.

## 3. Database Import (No CLI Needed)

InfinityFree typically does not provide shell access, so initialize using phpMyAdmin:

1. Open InfinityFree phpMyAdmin.
2. Select your database.
3. Import file: `backend/config/schema.sql`.
4. Wait for import to finish with no errors.

## 4. Production Environment File

Create `.env.production` in project root (same level as `index.php`) using `.env.production.example` as template.

Minimum required values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-subdomain.infinityfreeapp.com
APP_BASE_PATH=

DB_HOST=sqlXXX.infinityfree.com
DB_PORT=3306
DB_NAME=if0_xxxxxxxx_yourdbname
DB_USER=if0_xxxxxxxx
DB_PASS=your_mysql_password

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@example.com
MAIL_PASS=your-app-password
MAIL_FROM=your-email@example.com
MAIL_FROM_NAME=QueenLib

ADMIN_USERNAME=your-admin
SUPERADMIN_USERNAME=your-admin
ADMIN_PASSWORD=use-a-strong-password
```

If deployed inside a folder like `/library`, set:

```env
APP_BASE_PATH=/library
```

## 5. Security Hardening Already Included

- `.htaccess` blocks direct access to:
  - `includes/`
  - `backend/config/`, `backend/classes/`, `backend/mail/`, `backend/vendor/`
  - `backend/migrations/`, `backend/mcp/`
- `.env` and `.env.production` are denied from web access.
- `init-database.php` and `backend/setup-db.php` are blocked outside localhost.
- `init-database.php` also refuses to run when `APP_ENV=production`.

## 6. Post-Deploy Smoke Test

Test these pages on the live domain:

1. `/register.php`
2. `/login.php`
3. `/forgot-password.php`
4. `/verify-otp.php`
5. `/admin-login.php`

Expected behavior:

- Registration succeeds and stores user row.
- Login works and redirects correctly.
- Password reset email sends successfully.
- Admin login works only with configured credentials.

## 7. Common InfinityFree Issues

- Wrong DB host: use `sqlXXX.infinityfree.com` exactly from control panel.
- Wrong app path: set `APP_BASE_PATH` if app is not at `htdocs` root.
- SMTP blocked/failed: use valid SMTP credentials and app passwords.
- 403 on setup scripts: expected in production and by design.

## 8. Final Checklist

- `.env.production` exists on server
- Database imported from `backend/config/schema.sql`
- SMTP values tested
- Strong admin password configured
- `APP_URL` and `APP_BASE_PATH` correct
- Register/login/reset/admin flow verified
