# Implementation Plan: Admin High-Severity Security Remediation

**Branch**: `001-fix-high-severity` | **Date**: 2026-04-01 | **Spec**: `specs/001-fix-high-severity/spec.md`
**Input**: Feature specification from `specs/001-fix-high-severity/spec.md`

## Summary

Harden admin control-plane security by replacing config-only admin authentication with a hybrid credential model (DB-primary, env-bootstrap fallback), implementing real password persistence with session invalidation, and enforcing session-scoped CSRF validation on privileged admin mutations (`admin-profile.php` and `admin-change-password.php`).

## Technical Context

**Language/Version**: PHP 8.2.12  
**Primary Dependencies**: PDO (MySQL), PHPMailer (existing), SweetAlert2 (existing UI feedback), session/cookie security via PHP INI settings  
**Storage**: MySQL/MariaDB (`library_betonio`), plus PHP session storage  
**Testing**: Manual integration tests on XAMPP + targeted PHP lint (`php -l`) on changed files  
**Target Platform**: Apache 2.4.58 + PHP on XAMPP (local) and shared hosting (InfinityFree-compatible)  
**Project Type**: Traditional PHP web application (page controllers + shared includes)  
**Performance Goals**: Admin login/password/profile actions complete within normal page-load latency budget on shared hosting (target p95 < 500ms server time for auth mutations under low concurrency)  
**Constraints**: No framework migration, preserve existing route URLs, shared-hosting compatibility, DB is primary credential source, env fallback only when DB admin credential is absent, session-scoped reusable CSRF token  
**Scale/Scope**: Single admin identity path today, with support for multiple concurrent admin sessions requiring post-password-change invalidation

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

- **Role Ownership Gate**: PASS. Scope is admin auth/session/profile/password security only; no librarian or borrower privilege expansion.
- **Librarian Workflow Gate**: PASS. No changes to books, reservations, transactions, or fines.
- **Identity Security Gate**: PASS (planned). Design includes session ID regeneration on login/password change, CSRF enforcement for privileged forms, prepared statements for DB operations, and explicit role checks for admin-only routes.
- **Data Integrity Gate**: PASS (planned). New admin credential/session persistence is modeled with deterministic mutation rules and audit-friendly timestamps.
- **UX Feedback Gate**: PASS. Existing SweetAlert2 feedback mechanism remains the standard for success/error outcomes.
- **Deployment Gate**: PASS. Hybrid credential strategy respects env-based bootstrap and shared-hosting constraints.

### Role Impact Matrix

| Operation                                          | Superadmin | Admin     | Librarian | Borrower  | Unauthenticated |
| -------------------------------------------------- | ---------- | --------- | --------- | --------- | --------------- |
| Access `admin-login.php`                           | Allowed    | Allowed   | Denied    | Denied    | Allowed         |
| Access `admin-users.php`                           | Allowed    | Allowed   | Denied    | Denied    | Denied          |
| Submit `admin-profile.php?edit=1`                  | Allowed    | Allowed   | Denied    | Denied    | Denied          |
| Submit `admin-change-password.php`                 | Allowed    | Allowed   | Denied    | Denied    | Denied          |
| Access circulation/book management in this feature | No change  | No change | No change | No change | No change       |

## Project Structure

### Documentation (this feature)

```text
specs/001-fix-high-severity/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── admin-auth-forms.md
└── tasks.md
```

### Source Code (repository root)

```text
admin-login.php
admin-logout.php
admin-profile.php
admin-change-password.php
admin-users.php

includes/
├── config.php
├── auth.php
└── functions.php

backend/
├── config/
│   ├── schema.sql
│   └── init-db.php
└── classes/
    ├── Auth.php
    ├── AuthSupport.php
    └── UserRepository.php

public/js/
├── sweetalert-config.js
└── page-alerts.js
```

**Structure Decision**: Keep the current monolithic/traditional PHP structure and implement security controls in shared helpers and admin page handlers rather than introducing a new service layer or framework.

## Phase 0 Output Summary

Research resolved all technical unknowns around hybrid credential behavior, password persistence, session invalidation, and CSRF scope. See `specs/001-fix-high-severity/research.md`.

## Phase 1 Design Summary

- Data model defined for DB credential source state, admin session registry, and session-scoped CSRF token lifecycle.
- Form-route contracts defined for admin login, profile mutation, password mutation, and logout behavior.
- Validation quickstart authored with reproducible acceptance checks for FR-001 through FR-015.
- Agent context updated via `.specify/scripts/powershell/update-agent-context.ps1 -AgentType copilot`.

## Post-Design Constitution Check

- **Role Ownership Gate**: PASS. Privileged routes remain admin/superadmin only.
- **Librarian Workflow Gate**: PASS. No circulation ownership changes.
- **Identity Security Gate**: PASS. Design explicitly adds CSRF checks, session renewal, and secure credential verification/update logic.
- **Data Integrity Gate**: PASS. Credential/session records and mutation guards are modeled with no hidden state transitions.
- **UX Feedback Gate**: PASS. All success/failure paths specify SweetAlert2 feedback compatibility.
- **Deployment Gate**: PASS. Design preserves environment compatibility and bootstrap behavior.

## Complexity Tracking

No constitutional violations or waivers identified for this plan.
