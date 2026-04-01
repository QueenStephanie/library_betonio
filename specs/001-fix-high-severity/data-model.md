# Data Model: Admin High-Severity Security Remediation

## Entity: AdminCredential

Represents the persistent admin login identity and password source of truth.

### Fields

- `id` (INT, PK, auto-increment)
- `username` (VARCHAR(100), unique, not null)
- `password_hash` (VARCHAR(255), not null)
- `is_active` (BOOLEAN, default true)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)
- `password_changed_at` (DATETIME, nullable)

### Validation Rules

- `username` must be non-empty and unique.
- `password_hash` must be produced by `password_hash()` using bcrypt options from config.
- Inactive credentials must be denied during login.

## Entity: CredentialSourceState (Derived)

Represents whether authentication should use DB credentials or bootstrap env fallback.

### Derived States

- `db_mode`: at least one active `AdminCredential` exists.
- `bootstrap_env_mode`: no active `AdminCredential` exists; env credentials may be used.

### Validation Rules

- Env fallback is valid only in `bootstrap_env_mode`.
- Once DB credential exists, all login attempts must authenticate against DB.

## Entity: AdminSessionRegistry

Tracks active admin sessions to support cross-session invalidation.

### Fields

- `id` (INT, PK, auto-increment)
- `admin_identity` (VARCHAR(120), not null) - usually admin username
- `admin_credential_id` (INT, nullable, FK -> `AdminCredential.id`)
- `session_id_hash` (CHAR(64), unique, not null) - SHA-256 hash of session ID
- `auth_mode` (ENUM: `db`, `bootstrap_env`)
- `ip_address` (VARCHAR(45), nullable)
- `user_agent` (VARCHAR(500), nullable)
- `created_at` (TIMESTAMP)
- `last_seen_at` (DATETIME)
- `invalidated_at` (DATETIME, nullable)

### Validation Rules

- `session_id_hash` must never store raw PHP session IDs.
- `auth_mode` must reflect credential source used at login.
- Only non-invalidated sessions are considered active.

## Entity: AdminCsrfToken (Session-Scoped)

Represents anti-forgery token state stored in the active PHP session.

### Fields (Session Keys)

- `admin_csrf_token` (string, random bytes hex/base64)
- `admin_csrf_issued_at` (int timestamp)

### Validation Rules

- Token is generated once per authenticated session if missing.
- Token is reused across admin privileged forms until logout/session expiry.
- Token validation uses constant-time comparison.
- Missing or invalid token blocks mutation and returns error feedback.

## Entity: AdminAuthSession (Session Keys)

Represents authenticated admin state in PHP session.

### Fields (Session Keys)

- `admin_authenticated` (bool)
- `admin_username` (string)
- `admin_auth_mode` (`db` or `bootstrap_env`)
- `admin_session_version` (optional monotonic marker)
- `show_admin_welcome` (bool, one-time UI flag)

### Validation Rules

- Session ID must be regenerated on successful login.
- Session ID must be regenerated after successful password change.
- Logout clears all admin-scoped keys and invalidates registry entry.

## Relationships

- `AdminCredential (1) -> (many) AdminSessionRegistry` via `admin_credential_id`.
- `AdminAuthSession` maps to one active `AdminSessionRegistry` row via `session_id_hash`.
- `AdminCsrfToken` belongs to one `AdminAuthSession`.

## State Transitions

### Credential Source State

- `bootstrap_env_mode -> db_mode`: occurs when admin credential is manually created in DB.
- `db_mode` is sticky while at least one active admin credential exists.

### Admin Session Lifecycle

- `anonymous -> authenticated`: successful login + session regeneration + registry insert.
- `authenticated -> rotated`: successful password change + other sessions invalidated + current session regenerated + registry refreshed.
- `authenticated -> logged_out`: logout clears session keys and marks registry entry invalidated.
- `authenticated -> expired`: session timeout removes valid auth state and CSRF token.
