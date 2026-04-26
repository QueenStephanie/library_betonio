# QueenLib - Agent Guide

PHP/MySQL library management system with role-based access (superadmin, admin, librarian, borrower).

## Environment & Setup

- **PHP**: Requires PDO MySQL extension
- **Database**: MySQL (default port 3307, auto-falls back to 3306 for localhost)
- **Dependencies**: Run `composer install` in `backend/` (PHPMailer for email)
- **Config**: Copy `.env.example` → `.env` and edit. Local dev uses `.env`, production prefers `.env.production`
- **Base Path**: Set `APP_BASE_PATH=/library_betonio` for subfolder installs

## Key Entry Points

- **Web root**: `index.php` (delegates to `app/user/index.php`)
- **Auth pages**: `login.php`, `register.php`, `admin-login.php`
- **Role dashboards**:
  - Borrower: `index.php` → `app/user/`
  - Librarian: `librarian-dashboard.php` → `app/librarian/`
  - Admin: `admin-dashboard.php` → `app/admin/`
- **Database init**: `init-database.php` (localhost-only via .htaccess block)

## Architecture

```
Root PHP files       # Entry points, role-specific dashboards
app/                 # View layer (admin/, librarian/, user/, shared/, system/)
backend/             # API, classes, config, migrations, mail
includes/            # config.php (env + DB), functions.php (helpers), auth.php
api/                 # Additional API endpoints
public/              # JS/CSS assets
```

- **Config flow**: `includes/config.php` → `backend/config/AppBootstrap.php` → `.env` file
- **API bootstrap**: All backend APIs include `backend/api/_bootstrap.php`
- **Auth helpers**: `backend/classes/Auth.php`, `AuthSupport.php`

## Critical Conventions

### Database Access
- Use global `$db` (PDO instance from `includes/config.php`)
- Repository classes in `backend/classes/` (e.g., `LibrarianPortalRepository.php`, `UserRepository.php`)
- Schema in `backend/config/schema.sql`, seeds in `seed-superadmin.sql`, `seed_books.sql`

### Security Patterns
- **CSRF**: Use `getAdminCsrfToken()` / `validateAdminCsrfToken()` for admin pages; `getPublicCsrfToken()` / `validatePublicCsrfToken()` for public forms
- **Session timeout**: Enforced via `enforceAuthenticatedSessionTimeout()` in `functions.php`
- **Origin validation**: `validateStateChangingRequestOrigin()` for state-changing requests
- **Permission gate**: `backend/classes/PermissionGate.php` for role enforcement

### Path Building
- Use `appPath($path, $query)` and `appUrl($path, $query)` from `functions.php` (respects `APP_BASE_PATH`)
- Never hardcode URLs; always use these helpers for links/redirects

### Mail
- Use `getMailHandler()` → `MailHandler` class (PHPMailer wrapper)
- Configured via `MAIL_*` env vars; Gmail app passwords supported

## Graphify Knowledge Graph

This project has a graphify knowledge graph at `graphify-out/`.

- Before answering architecture or codebase questions, read `graphify-out/GRAPH_REPORT.md` for god nodes and community structure
- If `graphify-out/wiki/index.md` exists, navigate it instead of reading raw files
- After modifying code files in this session, run `/graphify . --update` to keep the graph current (AST-only, no API cost)

## Dev Workflow

1. Edit PHP files directly (no build step)
2. `.env` changes take effect immediately (no restart needed)
3. Test on localhost before production; production uses `.env.production` if present

## Testing Database Connection

Visit `check_db.php` or `test_db.php` in browser for diagnostics.

## Important .htaccess Blocks

- `includes/`, `backend/config/`, `backend/classes/`, `backend/mail/`, `backend/vendor/`, `app/` → Denied
- `init-database.php`, `backend/setup-db.php` → Localhost only
- Executing scripts from `images/`, `public/` → Forbidden

## Env Vars That Matter

```
APP_ENV=development|production
APP_DEBUG=true|false
APP_BASE_PATH=/subfolder_or_empty
DB_HOST=localhost
DB_PORT=3307
DB_NAME=library_betonio
DB_USER=root
DB_PASS=
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=app-password
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin1234
SUPERADMIN_USERNAME=admin
SUPERADMIN_PASSWORD=admin1234
SESSION_TIMEOUT=3600
BCRYPT_COST=12
```
