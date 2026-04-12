# QueenLib - Library Management System

A PHP-based library management system with role-based access, email verification, password recovery, and admin controls.

## Quick Start (Local)

1. Copy `.env.example` to `.env` and set local values.
2. Initialize database:

```bash
php backend/setup-db.php
```

3. Start Apache/MySQL in XAMPP.
4. Open:
   - `http://localhost/library_betonio/`
   - `http://localhost/library_betonio/register.php`
   - `http://localhost/library_betonio/login.php`

## InfinityFree Hosting (Production)

1. Upload project files into `htdocs` (or a subfolder inside `htdocs`).
2. Create MySQL database in InfinityFree panel.
3. Import `backend/config/schema.sql` in phpMyAdmin.
4. Create `.env.production` at project root.
5. Configure values:
   - `APP_URL=https://your-subdomain.infinityfreeapp.com`
   - `APP_BASE_PATH=` (or `/subfolder` if deployed in one)
   - `DB_HOST=sql###.infinityfree.com`
   - `DB_NAME=if0_xxxxxxxx_dbname`
   - `DB_USER=if0_xxxxxxxx`
   - `DB_PASS=your_mysql_password`
   - SMTP credentials
   - Strong admin credentials
6. Validate register/login/reset/admin flows.

See `INFINITYFREE_DEPLOYMENT.md` for full checklist.

## Security Notes

- Sensitive backend directories are blocked via `.htaccess`.
- `.env` and `.env.production` are denied from web access.
- Setup endpoints are blocked for non-local requests.
- `init-database.php` is disabled when `APP_ENV=production`.

## Main Features

- User registration with OTP verification
- Password reset flow
- Session-based auth and role checks
- Superadmin bootstrap and protection
- Admin profile and fine-reporting pages
- Borrower account dashboard

## Project Layout

- `app/` role-based page implementations (`public`, `user`, `admin`, `system`)
- Root `*.php` files as compatibility entry loaders
- `backend/api/` JSON endpoints
- `backend/classes/` domain/auth classes
- `backend/config/` bootstrap, DB config, schema
- `public/css` and `public/js` frontend assets

## Useful Commands

```bash
php backend/setup-db.php
php -r "require 'includes/config.php'; echo 'Connected!';"
```

## Documentation

- `README.md` - overview and setup
- `backend/README.md` - backend/API details
- `INFINITYFREE_DEPLOYMENT.md` - InfinityFree production guide

## License

Educational purposes. Adapt as needed.
