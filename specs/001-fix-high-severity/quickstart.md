# Quickstart: Validate Admin High-Severity Security Remediation

## Prerequisites

- XAMPP services running:
  - Apache
  - MySQL/MariaDB on configured local port (commonly 3307 in this project)
- Project environment configured (`.env` or `.env.production` as applicable)
- Database initialized with updated schema

## 1) Initialize or refresh database schema

Run from project root:

```bash
php backend/setup-db.php
```

If using phpMyAdmin, import `backend/config/schema.sql` after creating/selecting `library_betonio`.

## 2) Verify bootstrap-mode behavior (no DB admin credential)

1. Ensure no admin credential row exists in DB.
2. Open `/admin-login.php`.
3. Attempt login with configured env credentials.
4. Expected:
   - Login succeeds.
   - Session ID is regenerated.
   - Session stores `admin_authenticated`, `admin_username`, and `admin_auth_mode=bootstrap_env`.
   - Current admin session is written to `admin_session_registry`.
   - Admin can access `/admin-users.php`.

Negative check:

- Empty/missing credential state must fail with no privileged session.
- Unsafe bootstrap credential state in non-development environments must fail with no privileged session.

## 3) Verify DB-primary authentication

1. Create admin credential in DB.
2. Attempt login with DB username/password.
3. Attempt login with env credentials.
4. Expected:
   - DB credentials succeed.
   - Env credentials are no longer accepted once DB credential exists.
   - Successful login stores `auth_mode=db` and `admin_credential_id` in session and registry.

## 4) Verify CSRF protection on privileged forms

### Profile form

1. Open `/admin-profile.php?edit=1` while authenticated.
2. Submit valid form with valid CSRF token.
3. Expected: success feedback and mutation applied.
4. Replay request with missing or tampered token.
5. Expected: rejection feedback and no mutation.

### Password form

1. Open `/admin-change-password.php` while authenticated.
2. Submit with valid token and valid passwords.
3. Expected: success feedback and password persisted.
4. Submit with missing/tampered token.
5. Expected: rejection feedback and no password change.

Extra negative checks:

- Submit profile update request without `csrf_token`: request must be rejected and profile values must remain unchanged.
- Submit password change request with tampered `csrf_token`: request must be rejected and password must remain unchanged.

## 5) Verify password-change security outcomes

1. Login in Browser A and Browser B as same admin.
2. In Browser A, change password with correct current password and matching confirmation.
3. Expected:
   - Browser A remains logged in with regenerated session ID.
   - Browser B becomes invalidated and cannot perform privileged actions.
   - Browser A receives a refreshed `admin_session_registry` binding (new session hash), and previous hash is invalidated.
4. Attempt login with old password.
5. Expected: fail.
6. Attempt login with new password.
7. Expected: succeed.

## 6) Verify logout cleanup

1. Log in as admin.
2. Trigger `/admin-logout.php`.
3. Expected:
   - Redirect to `/admin-login.php`.
   - Admin session keys and CSRF token are cleared.
   - Current `admin_session_registry` record is marked invalidated.
   - Back-navigation does not restore privileged access.

## 7) Run lint checks on changed PHP files

```bash
php -l admin-login.php
php -l admin-profile.php
php -l admin-change-password.php
php -l admin-logout.php
php -l includes/functions.php
php -l includes/config.php
php -l includes/auth.php
php -l backend/classes/AdminSecurity.php
php -l backend/classes/AuthSupport.php
php -l backend/setup-db.php
php -l admin-dashboard.php
php -l admin-users.php
```

Latest command results (2026-04-01):

- No syntax errors detected in `admin-login.php`
- No syntax errors detected in `admin-profile.php`
- No syntax errors detected in `admin-change-password.php`
- No syntax errors detected in `admin-logout.php`
- No syntax errors detected in `includes/functions.php`
- No syntax errors detected in `includes/config.php`
- No syntax errors detected in `includes/auth.php`
- No syntax errors detected in `backend/classes/AdminSecurity.php`
- No syntax errors detected in `backend/classes/AuthSupport.php`
- No syntax errors detected in `backend/setup-db.php`
- No syntax errors detected in `admin-dashboard.php`
- No syntax errors detected in `admin-users.php`

## Acceptance Mapping

- FR-001 to FR-006: Steps 2, 3, 6
- FR-007 to FR-011: Step 5
- FR-012 to FR-014: Step 4
- FR-015: Steps 2 through 6 (feedback verification)
