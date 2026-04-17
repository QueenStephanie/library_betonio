# Library Betonio Backend Guide

## Scope

This backend provides database setup, canonical SQL migrations, authentication APIs, and security helpers used by the PHP web pages.

## Requirements

- PHP 8.1+
- MySQL/MariaDB
- PDO + `pdo_mysql`

## Setup

Run initial schema/bootstrap:

```bash
php backend/setup-db.php
```

Apply canonical SQL migrations:

```bash
php backend/migrations/migrate.php --apply
```

Check migration state:

```bash
php backend/migrations/migrate.php --status
php backend/migrations/migrate.php --dry-run
```

Notes:

- `backend/migrations/migrate.php` is the canonical migration runner.
- `backend/migrations/2026_04_17_harden_verification_attempt_types.sql` is intentionally a superseded no-op placeholder.
- `backend/migrations/run-harden-verification-attempt-types.php` is a legacy wrapper that delegates to `migrate.php --apply`.

## Implemented Auth API Routes

All endpoints accept JSON requests and return JSON responses.

- `POST /backend/api/register.php`
- `POST /backend/api/login.php`
- `POST /backend/api/logout.php`
- `POST /backend/api/forgot-password.php`
- `POST /backend/api/verify-reset-token.php`
- `POST /backend/api/reset-password.php`

No standalone OTP API endpoints are currently exposed. Email verification is handled by web route `verify-otp.php` using emailed verification links and resend flow.

## Security Helpers (Current)

- CSRF tokens for public and admin forms (`includes/functions.php`)
- Origin/Referer same-origin validation for state-changing requests
- Session idle + absolute timeout enforcement
- Attempt logging and throttling in `verification_attempts`
- Role/permission/page gates in `backend/classes/PermissionGate.php`

## Regression Test Harness

Run lightweight integration/security regression checks:

```bash
php backend/tests/run.php
```

The runner executes migration dry-run/status smoke checks, CSRF/origin helper checks, session timeout helper checks, and role gate matrix checks.
