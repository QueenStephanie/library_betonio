# Tasks: Admin Dashboard Navigation and User Control

**Input**: Design documents from `/specs/002-admin-dashboard-management/`
**Prerequisites**: spec.md, plan.md

## Phase 1: Setup

- [x] T001 Extend schema in `backend/config/schema.sql` with `users.role`, `role_profiles`, `admin_profiles`, and `fine_collections`.
- [x] T002 Update `backend/setup-db.php` to idempotently create/verify new columns, tables, and indexes.
- [x] T003 [P] Add/extend data-access classes: `backend/classes/UserRepository.php`, `backend/classes/AdminProfileRepository.php`, `backend/classes/FineReporting.php`.

## Phase 2: Tests and Validation Baseline

- [x] T004 Create acceptance validation notes in `specs/002-admin-dashboard-management/quickstart.md` for user roles, profile persistence, and month-to-date fines report.
- [x] T005 [P] Add lightweight data-model notes in `specs/002-admin-dashboard-management/data-model.md` reflecting new schema entities.

## Phase 3: Core Implementation

- [x] T006 Refactor `admin-profile.php` to load and persist admin profile data from DB via repository class (retain CSRF + alerts).
- [x] T007 Refactor `admin-users.php` from preview mode to DB-backed user management with create, role update, and inactive-state handling.
- [x] T008 Enforce role-change cleanup behavior (delete prior role-specific data) through user-management flow and persistence helpers.
- [x] T009 Create `admin-fines.php` with month-to-date collected fines summary and detail table using `FineReporting`.
- [x] T010 [P] Add Fines Report navbar links and active-state handling across `admin-dashboard.php`, `admin-users.php`, `admin-profile.php`, `admin-change-password.php`, and `admin-fines.php`.

## Phase 4: Integration

- [x] T011 Align role labels to `admin|librarian|borrower` in UI filters, badges, and persistence validation.
- [x] T012 Ensure all privileged form mutations use existing admin CSRF/session guard patterns and emit consistent page alerts.
- [x] T013 [P] Update `public/css/admin.css` minimally for new fines and form-validation states.

## Phase 5: Polish

- [x] T014 Run database setup verification: `php backend/setup-db.php`.
- [x] T015 Run PHP lint checks for all modified files.
- [x] T016 Execute manual acceptance checks and record outcomes in `specs/002-admin-dashboard-management/quickstart.md`.

## Dependencies

- T001 -> T002 -> T003 -> (T006, T007, T008, T009)
- T010 depends on T009 for active-state finalization
- T011-T013 depend on T006-T010
- T014-T016 run after all implementation tasks

## Parallel Examples

- T003 can run in parallel for independent class file creation after T002.
- T004 and T005 can run in parallel while core coding starts.
- T010 and T013 can run in parallel once page structure is stable.
