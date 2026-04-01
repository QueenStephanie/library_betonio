# Quickstart Validation: Admin Dashboard About Me and Superadmin Governance

## Purpose

Provide a practical checklist and captured results for validating feature 003 in local/dev deployment.

## Environment

- Date: 2026-04-02
- Branch: `003-admin-dashboard-superadmin`
- Runtime: XAMPP PHP/MySQL local stack

## Validation Checklist

- [x] Run schema/setup migration successfully.
- [x] Re-run setup migration to verify idempotency.
- [x] Confirm superadmin managed account exists.
- [x] Confirm repository blocks superadmin deactivation.
- [x] Confirm repository blocks superadmin deletion.
- [x] Confirm admin pages are syntax/diagnostics clean after edits.
- [ ] Manual browser check: non-superadmin cannot create/update role profiles.
- [ ] Manual browser check: superadmin can create/update role profiles.
- [ ] Manual browser check: old `admin-profile.php` route redirects to `admin-dashboard.php#about-me`.
- [ ] Manual browser check: profile nav removed from admin sidebar pages.
- [ ] Manual browser check: dashboard About Me section renders persisted profile fields.

## Executed Commands and Results

### 1) Setup run #1

Command:

```bash
php backend/setup-db.php
```

Result summary:

- success: true
- superadmin.status: `created`
- superadmin.username: `admin`
- superadmin.email: `admin@local.admin`
- superadmin.user_id: `45`

### 2) Setup run #2 (idempotency)

Command:

```bash
php backend/setup-db.php
```

Result summary:

- success: true
- superadmin.status: `updated`
- same user_id retained: `45`
- demoted_previous_count: `0`

### 3) Repository protection checks

Command:

```bash
php -r 'require "includes/config.php"; require APP_ROOT . "/backend/classes/UserRepository.php"; $super = UserRepository::getSuperadminUser($db); $result = ["has_superadmin" => (bool)$super, "superadmin_id" => $super["id"] ?? null, "deactivate_blocked" => false, "delete_blocked" => false]; if ($super) { try { UserRepository::setUserActiveState($db, (int)$super["id"], false); } catch (Exception $e) { $result["deactivate_blocked"] = stripos($e->getMessage(), "cannot") !== false; $result["deactivate_message"] = $e->getMessage(); } try { UserRepository::deleteManagedUser($db, (int)$super["id"]); } catch (Exception $e) { $result["delete_blocked"] = stripos($e->getMessage(), "cannot") !== false; $result["delete_message"] = $e->getMessage(); } } echo json_encode($result, JSON_PRETTY_PRINT), PHP_EOL;'
```

Result summary:

- has_superadmin: true
- deactivate_blocked: true (`Superadmin account cannot be deactivated.`)
- delete_blocked: true (`Superadmin account cannot be deleted.`)

## Deployment Notes

1. Add `SUPERADMIN_USERNAME` in `.env` / `.env.production`.
2. Run `php backend/setup-db.php` after deployment to ensure schema + superadmin normalization.
3. If `SUPERADMIN_USERNAME` changes, rerun setup to reassign `users.is_superadmin` to the declared identity.
4. Admin profile route now redirects to dashboard About Me; bookmarks remain valid via redirect.
5. For production, avoid default bootstrap credentials and rotate `ADMIN_PASSWORD` immediately.
