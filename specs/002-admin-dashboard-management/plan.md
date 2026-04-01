# Implementation Plan: Admin Dashboard Navigation and User Control

**Branch**: `002-admin-dashboard-management` | **Date**: 2026-04-01 | **Spec**: `specs/002-admin-dashboard-management/spec.md`
**Input**: Feature specification from `specs/002-admin-dashboard-management/spec.md`

## Summary

Implement the clarified admin dashboard scope end-to-end in the existing traditional PHP architecture: DB-backed user management with role assignment/change, DB-backed admin profile editing, a new month-to-date fines report page, and consistent admin navbar routing and guards across pages.

## Technical Context

**Language/Version**: PHP 8.2+  
**Primary Dependencies**: PDO/MySQL, existing SweetAlert2 page-alert rendering, existing admin auth/session helpers  
**Storage**: MySQL/MariaDB (`library_betonio`)  
**Testing**: Manual acceptance flows + `php -l` syntax validation  
**Target Platform**: XAMPP Apache/PHP local and shared-hosting compatible deployment  
**Project Type**: Traditional PHP page controllers with shared includes/classes  
**Constraints**:

- Keep existing route/page architecture (no framework/API migration)
- Preserve current admin session registry guard behavior
- Role-change behavior is destructive for previous role-specific data
- Fines report period is fixed month-to-date only (no date-range filter)

## Constitution Check

- **Role Boundaries**: Only admin-authenticated sessions may access admin profile/password/user/fines pages and mutation actions.
- **Librarian Ownership**: Fine collection remains operationally librarian-driven; feature provides admin visibility reporting only.
- **Auth Security**: Reuse `requireAdminAuth`, CSRF tokens, and session registry enforcement.
- **Data Model Impact**: Add missing role/fines/profile persistence structures with idempotent setup behavior.
- **UX Feedback**: Reuse `renderPageAlerts`/SweetAlert for mutation outcomes.
- **Route Protection**: All new privileged page actions stay under existing admin guard.

## Project Structure

### Docs

```text
specs/002-admin-dashboard-management/
├── plan.md
├── tasks.md
└── spec.md
```

### Source

```text
admin-dashboard.php
admin-users.php
admin-profile.php
admin-change-password.php
admin-fines.php (new)

includes/
├── config.php
└── functions.php

backend/
├── setup-db.php
├── config/schema.sql
└── classes/
    ├── UserRepository.php
    ├── AdminProfileRepository.php (new)
    └── FineReporting.php (new)
```

## Implementation Strategy

1. Extend schema and setup bootstrap first (role, role profile, admin profile, fine collections).
2. Add/extend repository classes for persistence and report aggregation.
3. Refactor admin pages to consume real DB data and process form submissions.
4. Add missing fines page and navbar links across all admin pages.
5. Validate with setup script run, PHP lint, and acceptance walkthroughs.

## Risks

- Existing demo data assumptions in `admin-users.php` may diverge from DB shape.
- Admin profile currently session-only; migration to DB must preserve fallback behavior.
- New tables must be idempotently created for existing environments.

## Done Criteria

- FR-001 through FR-015 mapped and implemented.
- Tasks file fully checked with `[X]` for completed tasks.
- All touched PHP files pass `php -l`.
- Admin flows validate manually for profile, password, users/roles, fines report, and route protection.
