# Research: Admin High-Severity Security Remediation

## Decision 1: Primary admin credential store

- Decision: Store admin credentials in MySQL as hashed passwords (`password_hash`) and treat DB as source of truth.
- Rationale: Meets FR-002/FR-009, enables real password changes, and aligns with constitution principle III on secure credential handling.
- Alternatives considered: Keep env-only credentials (rejected: cannot persist password changes); store plaintext or reversible secrets (rejected: insecure).

## Decision 2: Hybrid bootstrap policy

- Decision: Allow env credential fallback only when no admin credential row exists in DB.
- Rationale: Matches clarified requirement and FR-003/FR-004 while supporting first-time bootstrap on shared hosting.
- Alternatives considered: Always allow env fallback (rejected: weakens DB-primary model); disable fallback entirely (rejected: breaks bootstrap provisioning path).

## Decision 3: Password change persistence flow

- Decision: Require current password verification, new password confirmation, and minimum complexity checks before DB update; on success, persist new hash and password-changed timestamp.
- Rationale: Enforces FR-007/FR-008/FR-009 and prevents cosmetic-only updates.
- Alternatives considered: Client-side-only checks (rejected: bypassable); skipping current-password check (rejected: high-risk account takeover vector).

## Decision 4: Session invalidation model after password change

- Decision: Maintain an admin session registry table keyed by hashed session IDs and invalidate all other sessions for the same admin identity after password change; keep current session and regenerate its ID.
- Rationale: Implements FR-010 and FR-011 deterministically and supports concurrent-session revocation.
- Alternatives considered: `session_destroy()` for current session only (rejected: does not invalidate others); global session wipe (rejected: impacts unrelated user sessions).

## Decision 5: CSRF token scope and validation

- Decision: Use one session-scoped CSRF token reusable across privileged admin forms until logout/session expiry; validate using constant-time comparison on each POST mutation.
- Rationale: Matches clarification and FR-012/FR-013/FR-014 while keeping implementation simple for multi-form admin UI.
- Alternatives considered: Per-form one-time tokens (rejected: more UX friction and stale-tab complexity for current scope); no CSRF protection (rejected: constitutional violation).

## Decision 6: Route protection consolidation

- Decision: Introduce shared helper checks for admin-authenticated access and CSRF validation, and apply them consistently in `admin-profile.php` and `admin-change-password.php`.
- Rationale: Reduces policy drift and satisfies CR-001/CR-006.
- Alternatives considered: Inline checks in each page (rejected: duplicated logic and maintenance risk).

## Decision 7: Feedback and failure handling

- Decision: Keep SweetAlert2 as the canonical feedback mechanism for all admin auth/profile/password outcomes and always reject invalid requests without mutation.
- Rationale: Satisfies FR-014/FR-015 and constitution principle V.
- Alternatives considered: Mixed native alerts and inline-only errors (rejected: inconsistent UX and higher operator error risk).

## Decision 8: Database migration strategy

- Decision: Extend `backend/config/schema.sql` with admin credential/session tables and provide idempotent creation in setup scripts.
- Rationale: Maintains existing deployment workflow and shared-hosting compatibility while adding required persistence.
- Alternatives considered: Separate migration tooling dependency (rejected: unnecessary complexity for current stack).
