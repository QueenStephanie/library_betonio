# Quickstart Validation: Admin Dashboard Navigation and User Control

## Preconditions

- Use branch `002-admin-dashboard-management`.
- Ensure database is reachable from `.env` values.
- Run setup command: `php backend/setup-db.php`.
- Login as admin at `admin-login.php`.

## Validation Scenarios

1. Navigation

- Open Dashboard, User Management, Profile, Change Password, and Fines Report via navbar links.
- Verify each destination loads and protected routes reject non-admin sessions.

2. User Creation and Role Assignment

- Create one user for each role: `borrower`, `librarian`, `admin`.
- Verify each user appears in listing with correct role badge.

3. Role Change and Role Data Cleanup

- Edit an existing user and switch role.
- Confirm previous role-specific `role_profiles` data is replaced with only the new role record.

4. Admin Profile Persistence

- Update profile fields and save.
- Reload page and verify saved values persist.

5. Fines Report Month-to-Date

- Open Fines Report page.
- Confirm report period is fixed to current month-to-date and no custom date picker/filter appears.
- Validate totals and detail rows match `fine_collections` records for current month.

6. Security

- Submit profile/users forms without valid CSRF token and confirm mutation is rejected.
- Verify existing password-change behavior still works.

## Verification Log

- Date: 2026-04-01
- Command: `php backend/setup-db.php`
  - Result: Success
  - Verified tables: `role_profiles`, `admin_profiles`, `fine_collections`
- Command: `php -l backend/setup-db.php && php -l backend/classes/UserRepository.php && php -l backend/classes/AdminProfileRepository.php && php -l backend/classes/FineReporting.php && php -l admin-profile.php && php -l admin-users.php && php -l admin-fines.php && php -l admin-dashboard.php && php -l admin-change-password.php`
  - Result: No syntax errors for all listed files
- Command: `php specs/002-admin-dashboard-management/contracts/acceptance-smoke.php`
  - Result:
    - `role_cleanup`: true
    - `profile_persistence`: true
    - `fines_mtd_report`: true
    - `errors`: []
- Browser-assisted visual walkthrough (recommended): pending local confirmation for final UI behavior and interaction polish.
