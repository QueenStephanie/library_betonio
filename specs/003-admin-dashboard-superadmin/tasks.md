# Tasks: Admin Dashboard About Me and Superadmin Governance

**Input**: Design documents from `/specs/003-admin-dashboard-superadmin/`  
**Prerequisites**: plan.md (required), spec.md (required)

**Tests**: Automated test tasks are not included because the specification does not explicitly request TDD or mandatory automated tests.

**Organization**: Tasks are grouped by user story so each story can be implemented and validated independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (`[US1]`, `[US2]`, `[US3]`)
- Every task includes an exact file path

## Phase 1: Setup (Shared Configuration)

**Purpose**: Add superadmin configuration inputs and shared constants required by later phases.

- [x] T001 Document `SUPERADMIN_USERNAME` environment key in `.env.example`
- [x] T002 Add `SUPERADMIN_USERNAME` loading and constant definition in `includes/config.php`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Add core schema and reusable permission helpers that block all user story work.

**⚠️ CRITICAL**: No user story work starts until this phase is complete.

- [x] T003 Add `is_superadmin` column to `users` table definition in `backend/config/schema.sql`
- [x] T004 Add idempotent `is_superadmin` column migration guard in `backend/setup-db.php`
- [x] T005 Add superadmin lookup helpers in `backend/classes/UserRepository.php`
- [x] T006 Add shared helper to compare current admin identity against configured superadmin in `includes/functions.php`
- [x] T007 Add session cleanup for superadmin-related state in `includes/functions.php` and `admin-login.php`

**Checkpoint**: Foundation ready; user stories can begin.

---

## Phase 3: User Story 1 - Protected Superadmin Bootstrap (Priority: P1) 🎯 MVP

**Goal**: Auto-provision the declared superadmin account and make it undeletable.

**Independent Test**: Run setup-db twice with `SUPERADMIN_USERNAME` configured, verify exactly one flagged superadmin exists, and confirm delete/deactivate attempts are blocked.

- [x] T008 [US1] Implement idempotent superadmin provisioning routine in `backend/setup-db.php`
- [x] T009 [US1] Wire superadmin provisioning into setup execution flow and response payload in `backend/setup-db.php`
- [x] T010 [US1] Implement repository-level protection against deleting/deactivating superadmin in `backend/classes/UserRepository.php`
- [x] T011 [US1] Add protected delete action handler and superadmin guard checks in `admin-users.php`
- [x] T012 [US1] Add delete control and superadmin-safe action rendering in users table UI in `admin-users.php`
- [x] T013 [US1] Add confirmation and blocked-action messaging for superadmin-protected operations in `admin-users.php`

**Checkpoint**: Superadmin is auto-created, unique, and undeletable.

---

## Phase 4: User Story 2 - Superadmin Role Profile Control (Priority: P1)

**Goal**: Restrict borrower/librarian/admin profile creation and management to superadmin only.

**Independent Test**: As superadmin, create and update borrower/librarian/admin profiles successfully; as non-superadmin admin, receive denied actions with clear feedback.

- [x] T014 [US2] Add actor-aware authorization checks for managed role/profile mutations in `backend/classes/UserRepository.php`
- [x] T015 [US2] Enforce superadmin-only create/update role profile flows in POST handlers in `admin-users.php`
- [x] T016 [US2] Block non-superadmin role reassignment UI paths in add/edit user modals in `admin-users.php`
- [x] T017 [US2] Standardize unauthorized action alert content for role-profile restrictions in `admin-users.php`

**Checkpoint**: Role-profile governance is enforced by superadmin-only permissions.

---

## Phase 5: User Story 3 - Dashboard About Me Portfolio View (Priority: P2)

**Goal**: Remove standalone admin profile UX and consolidate profile viewing into a portfolio-style About Me section on dashboard.

**Independent Test**: Visit dashboard and verify About Me profile rendering; visit old profile route and verify redirect to dashboard About Me anchor; confirm profile nav item is removed across admin pages.

- [x] T018 [US3] Load admin profile repository data for dashboard rendering in `admin-dashboard.php`
- [x] T019 [US3] Replace static developer block with portfolio-style About Me section and anchor in `admin-dashboard.php`
- [x] T020 [US3] Convert profile route to authenticated redirect to dashboard About Me in `admin-profile.php`
- [x] T021 [P] [US3] Remove Profile nav item from sidebar in `admin-dashboard.php`
- [x] T022 [P] [US3] Remove Profile nav item from sidebar in `admin-users.php`
- [x] T023 [P] [US3] Remove Profile nav item from sidebar in `admin-fines.php`
- [x] T024 [P] [US3] Remove Profile nav item from sidebar in `admin-change-password.php`
- [x] T025 [US3] Add/adjust About Me portfolio presentation styles in `public/css/admin.css`

**Checkpoint**: Dashboard is the single profile destination with portfolio-style About Me presentation.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Finalize docs, verification flow, and rollout safety notes.

- [x] T026 [P] Update admin env/config documentation for superadmin bootstrap in `docs/BACKEND.md`
- [x] T027 [P] Update feature summary and usage notes in `README.md`
- [x] T028 Create manual validation checklist for all acceptance scenarios in `specs/003-admin-dashboard-superadmin/quickstart.md`
- [x] T029 Record final validation results and deployment notes in `specs/003-admin-dashboard-superadmin/quickstart.md`

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup)**: No dependencies
- **Phase 2 (Foundational)**: Depends on Phase 1 and blocks all user stories
- **Phase 3 (US1)**: Depends on Phase 2
- **Phase 4 (US2)**: Depends on Phase 2; can begin after foundational checkpoint
- **Phase 5 (US3)**: Depends on Phase 2; independent from US1 and US2 domain logic
- **Phase 6 (Polish)**: Depends on completion of selected user stories

### User Story Dependencies

- **US1 (P1)**: No dependency on other stories after foundation; defines core superadmin lifecycle
- **US2 (P1)**: No dependency on US3; uses foundational permissions and repository guards
- **US3 (P2)**: Independent of US1/US2 business rules after foundation

### Recommended Delivery Order

1. Complete Phase 1 and Phase 2
2. Deliver US1 as MVP for safety-critical governance
3. Deliver US2 for role/profile control
4. Deliver US3 for dashboard/profile UX consolidation
5. Finish Polish tasks

---

## Parallel Opportunities

- T021, T022, T023, and T024 can run in parallel (different admin page files)
- T026 and T027 can run in parallel (different documentation files)

### Parallel Example: User Story 3

Run these in parallel once T020 is complete:

- Task T021 in `admin-dashboard.php`
- Task T022 in `admin-users.php`
- Task T023 in `admin-fines.php`
- Task T024 in `admin-change-password.php`

### Parallel Example: Polish

Run these in parallel after implementation is complete:

- Task T026 in `docs/BACKEND.md`
- Task T027 in `README.md`

---

## Implementation Strategy

### MVP First (US1)

1. Complete Setup and Foundational phases
2. Complete Phase 3 (US1) only
3. Validate superadmin auto-provisioning and undeletable behavior
4. Ship safety-critical controls first

### Incremental Delivery

1. US1: superadmin bootstrap and undeletable protection
2. US2: superadmin-only role/profile governance
3. US3: dashboard About Me portfolio redesign and profile route consolidation

### Team Parallelization

1. One developer handles backend schema/setup/repository tasks (T003-T015)
2. One developer handles admin UI updates (T012-T013, T016-T017, T018-T025)
3. One developer handles docs and validation capture (T026-T029)
