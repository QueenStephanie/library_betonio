# Implementation Plan: Admin Dashboard About Me and Superadmin Governance

**Branch**: `003-admin-dashboard-superadmin` | **Date**: 2026-04-02 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-admin-dashboard-superadmin/spec.md`

## Summary

Modify the existing admin implementation to consolidate personal profile UX into a dashboard About Me portfolio section, remove standalone profile navigation, and introduce an environment-declared superadmin that is auto-provisioned and undeletable. The solution will reuse current session guards, repositories, setup-db idempotent migration style, and SweetAlert-based feedback without introducing a new architecture.

## Technical Context

**Language/Version**: PHP 8.0+ (current project target; running on XAMPP)  
**Primary Dependencies**: PDO/MySQL, PHPMailer, SweetAlert2, existing app helper classes (`UserRepository`, `AdminProfileRepository`, `AdminSecurity`, `AuthSupport`)  
**Storage**: MySQL/MariaDB schema in `backend/config/schema.sql`  
**Testing**: Existing PHP smoke-style script pattern (`specs/002-admin-dashboard-management/contracts/acceptance-smoke.php`) and manual admin flow verification  
**Target Platform**: Apache + PHP web app on localhost/shared hosting (InfinityFree docs present)  
**Project Type**: Traditional server-rendered PHP web application  
**Performance Goals**: Preserve current admin page responsiveness; no additional heavy queries per request  
**Constraints**: Must modify existing admin code paths only, preserve CSRF/session registry checks, keep idempotent DB setup behavior, no breaking route removals (redirect required)  
**Scale/Scope**: Single feature affecting admin dashboard/profile/users management and setup/bootstrap logic

## Constitution Check

_GATE: Must pass before Phase 0 research. Re-check after Phase 1 design._

- **Role Ownership Gate**: PASS. Superadmin is granted unique protected-account governance; non-superadmin admins are explicitly forbidden from modifying/deleting/deactivating the superadmin account.
- **Librarian Workflow Gate**: PASS. No circulation ownership behavior is changed; librarian-mediated flows remain untouched.
- **Identity Security Gate**: PASS. Existing `requireAdminAuth()`, session registry validation, CSRF tokens, and credential verification remain in place and are reused.
- **Data Integrity Gate**: PASS. Minimal schema impact (superadmin marker on users) plus idempotent setup migration and deterministic provisioning rules.
- **UX Feedback Gate**: PASS. Existing page alert + SweetAlert rendering will be reused for blocked actions and success/failure outcomes.
- **Deployment Gate**: PASS. Feature relies on existing `.env` loading and setup flow; adds `SUPERADMIN_USERNAME` with safe fallback behavior.

## Project Structure

### Documentation (this feature)

```text
specs/003-admin-dashboard-superadmin/
├── spec.md
├── plan.md
├── checklists/
│   └── requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
backend/
├── classes/
│   ├── UserRepository.php
│   ├── AdminProfileRepository.php
│   ├── AdminSecurity.php
│   └── AuthSupport.php
├── config/
│   └── schema.sql
└── setup-db.php

includes/
├── config.php
├── auth.php
└── functions.php

public/
└── css/
   └── admin.css

admin-dashboard.php
admin-profile.php
admin-users.php
admin-fines.php
admin-change-password.php
admin-login.php
```

**Structure Decision**: Keep the existing monolithic server-rendered PHP layout and implement feature changes in-place across current admin pages, shared include helpers, repositories, and setup migration logic.

## Complexity Tracking

No constitutional violations identified for this feature scope.

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --------- | ---------- | ------------------------------------ |
| None      | N/A        | N/A                                  |
