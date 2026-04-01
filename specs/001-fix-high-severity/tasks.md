# Tasks: Admin High-Severity Security Remediation

**Input**: Design documents from `/specs/001-fix-high-severity/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/admin-auth-forms.md, quickstart.md

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Establish schema and setup-script support for new admin security persistence.

- [X] T001 Extend admin security schema in backend/config/schema.sql with `admin_credentials` and `admin_session_registry` tables
- [X] T002 Update database bootstrap logic in backend/setup-db.php to create/verify new admin security tables and indexes idempotently
- [X] T003 [P] Document schema/bootstrap changes and operational notes in docs/BACKEND.md

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Build shared security primitives required by all user stories.

**CRITICAL**: Complete this phase before starting any user story implementation.

- [X] T004 Create centralized admin security service in backend/classes/AdminSecurity.php for credential-source resolution and password persistence
- [X] T005 [P] Add admin session registry lifecycle helpers in backend/classes/AuthSupport.php for create/invalidate/refresh operations
- [X] T006 [P] Add shared admin guard and session-scoped CSRF helper functions in includes/functions.php
- [X] T007 Add bootstrap guardrail configuration logic in includes/config.php for missing/unsafe admin credential states
- [X] T008 Wire admin-security service usage into includes/auth.php for page-level admin auth entry points
- [X] T009 Enforce shared admin guard usage in admin-users.php and admin-dashboard.php

**Checkpoint**: Foundation complete; user stories can now be implemented.

---

## Phase 3: User Story 1 - Safe Admin Authentication (Priority: P1) 🎯 MVP

**Goal**: Harden admin login/logout flow so privileged sessions are created only from valid credential states.

**Independent Test**: Validate successful login (with session renewal), invalid login rejection, and missing/unsafe credential rejection with no privileged session established.

### Implementation for User Story 1

- [X] T010 [US1] Replace direct env-only comparison with DB-primary/bootstrap fallback credential verification in admin-login.php
- [X] T011 [US1] Regenerate session identity and set admin auth metadata on successful login in admin-login.php
- [X] T012 [US1] Persist successful login session in registry through backend/classes/AuthSupport.php
- [X] T013 [US1] Add explicit failure handling for missing/unsafe credential states in admin-login.php
- [X] T014 [P] [US1] Harden admin logout cleanup and invalidate current session-registry record in admin-logout.php
- [X] T015 [US1] Ensure shared admin guard is applied consistently in admin-profile.php and admin-change-password.php
- [X] T016 [P] [US1] Add/align login-logout acceptance steps in specs/001-fix-high-severity/quickstart.md

**Checkpoint**: User Story 1 should be independently functional and testable.

---

## Phase 4: User Story 2 - Real Password Change Enforcement (Priority: P2)

**Goal**: Make password updates real and secure by validating current password, persisting new hash, and revoking other active sessions.

**Independent Test**: Change password with correct/incorrect current password, verify old password fails, new password succeeds, and other sessions are invalidated.

### Implementation for User Story 2

- [X] T017 [US2] Implement server-side validation for current/new/confirm password fields in admin-change-password.php
- [X] T018 [US2] Verify current password against active credential source via backend/classes/AdminSecurity.php from admin-change-password.php
- [X] T019 [US2] Persist new password hash and `password_changed_at` in backend/classes/AdminSecurity.php
- [X] T020 [US2] Invalidate all non-current admin sessions after password change in backend/classes/AuthSupport.php
- [X] T021 [US2] Regenerate current session ID and refresh registry binding after successful password update in admin-change-password.php
- [X] T022 [US2] Add complete success/error feedback mapping for password update outcomes in admin-change-password.php
- [X] T023 [P] [US2] Add password-change verification steps and expected outcomes in specs/001-fix-high-severity/quickstart.md

**Checkpoint**: User Stories 1 and 2 should both work independently.

---

## Phase 5: User Story 3 - CSRF-Protected Admin Mutations (Priority: P3)

**Goal**: Block forged privileged mutations by requiring valid session-scoped CSRF tokens on admin profile/password forms.

**Independent Test**: Submit privileged forms with valid, missing, and tampered tokens; only valid-token requests may mutate state.

### Implementation for User Story 3

- [X] T024 [US3] Render session-scoped `csrf_token` hidden fields in admin-profile.php and admin-change-password.php
- [X] T025 [P] [US3] Validate CSRF token on profile mutation requests in admin-profile.php
- [X] T026 [P] [US3] Validate CSRF token on password mutation requests in admin-change-password.php
- [X] T027 [US3] Enforce no-mutation behavior on CSRF validation failure in admin-profile.php and admin-change-password.php
- [X] T028 [US3] Clear admin CSRF session state during logout in admin-logout.php
- [X] T029 [P] [US3] Add CSRF negative-path quickstart checks in specs/001-fix-high-severity/quickstart.md

**Checkpoint**: All three user stories should be independently functional.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final hardening and validation across all implemented stories.

- [X] T030 [P] Fix one-time welcome-session key typo handling in admin-dashboard.php
- [X] T031 [P] Run PHP syntax validation for modified admin/auth files and record command results in specs/001-fix-high-severity/quickstart.md
- [X] T032 [P] Finalize operational documentation for hybrid auth and session invalidation behavior in docs/BACKEND.md
- [X] T033 Perform cross-story security review updates in admin-login.php, admin-logout.php, admin-profile.php, and admin-change-password.php

---

## Dependencies & Execution Order

### Phase Dependencies

- Phase 1 (Setup): No dependencies.
- Phase 2 (Foundational): Depends on Phase 1 and blocks all user stories.
- Phase 3 (US1): Depends on Phase 2.
- Phase 4 (US2): Depends on Phase 2 and US1 auth/session foundations.
- Phase 5 (US3): Depends on Phase 2; coordinate with US2 because both touch admin profile/password handlers.
- Phase 6 (Polish): Depends on completion of desired user stories.

### User Story Completion Order

- US1 (P1) should be completed first for MVP security baseline.
- US2 (P2) next to remove cosmetic password-change behavior.
- US3 (P3) then finalizes request-origin protections.

---

## Parallel Execution Examples

### User Story 1

- Run T014 in parallel with T013 after T010-T012 are complete.
- Run T016 in parallel with T015 because docs and page-guard updates are independent.

### User Story 2

- Run T023 in parallel with T022 after T017-T021 are complete.

### User Story 3

- Run T025 and T026 in parallel after T024 is complete.
- Run T029 in parallel with T028 once CSRF validation behavior is implemented.

---

## Implementation Strategy

### MVP First (US1 Only)

1. Complete Phase 1.
2. Complete Phase 2.
3. Complete Phase 3 (US1).
4. Validate US1 independently before proceeding.

### Incremental Delivery

1. Deliver US1 for immediate high-severity auth protection.
2. Add US2 for real password persistence and session invalidation.
3. Add US3 for CSRF mutation protection.
4. Finish with Phase 6 polish and full quickstart validation.
