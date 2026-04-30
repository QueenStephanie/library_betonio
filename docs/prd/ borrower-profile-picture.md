# Borrower Profile Picture — PRD

## Problem Statement

Borrowers on QueenLib have no profile picture. The system currently generates initials-based avatar fallbacks in two places (the account settings hero card and the portal sidebar), but there is no way for a borrower to upload, change, or remove their own photo. This reduces personalization and makes it harder for library staff to identify borrowers at the checkout desk.

## Solution

Allow borrowers to upload a profile picture from the Account Settings page (`account.php`). The photo is stored securely outside the webroot, resized server-side, and displayed consistently across the borrower portal — in the sidebar, on the dashboard hero, and in the account settings hero card. A "Remove photo" option reverts the borrower to the initials avatar.

---

## User Stories

### Upload & Management

1. As a borrower, I want to upload a profile picture (JPEG, PNG, or WebP, max 2MB) from the Account Settings page, so that I can personalize my library account.
2. As a borrower, I want the system to automatically resize my uploaded image to 400×400 pixels, so that the file is compact and the display is consistent.
3. As a borrower, I want to remove my current profile picture, so that I can revert to the default initials avatar if I no longer want a photo.
4. As a borrower, I want to replace my existing profile picture by uploading a new one, so that I can update my photo without needing a separate "remove then upload" step.
5. As a borrower, I want to see a preview of my current profile picture in the account settings, so that I know what is currently set before making changes.

### Viewing & Display

6. As a borrower, I want my profile picture to appear in the portal sidebar, so that I can see it on every page and feel the interface is personal to me.
7. As a borrower, I want my profile picture to appear in the dashboard hero card on the borrower index page, so that the first thing I see on login is my own photo.
8. As a borrower, I want my profile picture to appear in the account settings hero card, so that I can see it alongside my name and email while editing my profile.
9. As a librarian, I want to see borrowers' profile pictures in the sidebar when I am logged in, so that I can visually identify borrowers at the checkout desk.
10. As an admin, I want to see borrowers' profile pictures in the sidebar when I am logged in, so that the admin portal feels consistent with the borrower experience.

### Default State

11. As a new borrower who has never uploaded a photo, I want to see a visually styled initials avatar by default, so that my account does not look incomplete.
12. As a borrower who has removed their photo, I want the system to display the initials avatar again immediately, so that the change takes effect without reloading.

### Error Handling

13. As a borrower, I want the system to reject files that are not JPEG, PNG, or WebP, so that invalid file types cannot be uploaded.
14. As a borrower, I want the system to reject profile picture uploads larger than 2MB, so that storage is not abused.
15. As a borrower, I want to see a clear error message if my upload fails, so that I understand what went wrong and can try again.
16. As a borrower, I want the system to keep my existing photo if the upload fails partway through, so that I do not lose my current picture due to a transient error.

### Security & Access Control

17. As a borrower, I want only my own profile picture to be stored under my user ID, so that there is no confusion with other users' photos.
18. As a librarian/admin, I want to be able to view any borrower's profile picture, so that I can identify them at the desk.
19. As a guest (unauthenticated user), I do NOT want to be able to view borrowers' profile pictures, so that photos are not exposed publicly.
20. As a borrower, I want my profile picture to be served with proper authentication checks, so that it cannot be hotlinked or accessed by unauthorized users.

### Persistence

21. As a borrower, I want my profile picture to persist across sessions, so that I do not have to re-upload it every time I log in.
22. As a system, when a borrower uploads a new profile picture and already has one, the old file must be deleted from storage, so that orphaned files do not accumulate.

---

## Implementation Decisions

### Schema Change

- Add a nullable `profile_pic_url` VARCHAR(1024) column to the `users` table. When NULL, the initials avatar is shown.

### Storage Architecture

- Files are stored in `storage/profile-pics/` (outside the webroot `public/` directory).
- Filename pattern: `borrower-{user_id}-{sha256_hash}.{ext}` where the hash is derived from `user_id + timestamp`. Example: `borrower-42-a3f8c1e9...jpg`
- The extension is derived from the MIME type (`.jpg` for `image/jpeg`, `.png` for `image/png`, `.webp` for `image/webp`).

### Image Processing

- On upload, the image is validated (MIME type, size ≤ 2MB, valid image).
- It is resized to fit within a 400×400 bounding box (square crop via `imagecopyresampled`), maintaining aspect ratio, then center-cropped to exactly 400×400.
- Output quality: 85%.
- Output format: same MIME type as the upload (JPEG stays JPEG, etc.).

### File Deletion

- When a borrower uploads a new photo and already has a stored file, the old file is deleted from `storage/profile-pics/` before the new one is saved.
- When a borrower removes their photo, the stored file is deleted and `profile_pic_url` is set to NULL.

### Service Module: ProfilePicStorageService

A new `ProfilePicStorageService` class (placed in `includes/services/`) handles:
- `store(PDO $db, int $userId, array $file): array` — validates, resizes, saves file, updates DB, returns `['success' => true, 'path' => $storedPath]` or error
- `remove(PDO $db, int $userId): array` — deletes file, clears DB column, returns status
- `getUrl(int $userId): ?string` — returns the stored profile pic URL from DB (for session refresh after upload/remove)

This module is testable in isolation (it operates on a PDO and the filesystem, both injectable/mockable).

### AccountService Extension

- Add `updateProfilePic(int $userId, string $profilePicUrl): array` — updates `profile_pic_url` in the `users` table.
- Add `removeProfilePic(int $userId): array` — sets `profile_pic_url` to NULL.
- Both methods use the existing transactional pattern from `AccountService`.

### Viewing Endpoint

- Create `serve-profile-pic.php` in the app root (or borrower directory) that:
  1. Checks authentication — only logged-in users may view.
  2. Looks up `profile_pic_url` from the `users` table for the authenticated user (or for a `user_id` parameter when a librarian/admin is viewing a borrower's pic).
  3. Serves the file with `readfile()` using correct `Content-Type` and `Cache-Control` headers.
  4. Returns a 404 if the file does not exist or the user has no photo set.

### UI: Account Settings Page (`account.php`)

- Add a new "Profile Photo" section alongside the existing Profile and Security sections.
- The section shows the current photo (or initials avatar fallback) with a "Change Photo" file input and a "Remove Photo" button.
- The "Remove Photo" button is only visible when a photo is set.
- Form submits via a new `action = 'update_profile_pic'` POST handler in the existing form block (same page, same CSRF scope).

### UI: Portal Sidebar (`app/shared/portal-sidebar.php`)

- When rendering the sidebar avatar for a borrower, check if `$user['profile_pic_url']` is set and non-empty.
- If set: output `<img src="<?php echo appPath('serve-profile-pic.php'); ?>" alt="<?php echo htmlspecialchars($portalIdentityName); ?>" class="admin-sidebar-avatar-img">` inside the `<span class="admin-sidebar-avatar">` wrapper, replacing the SVG icon.
- If not set: render the existing SVG icon (no change to existing behavior).
- Add CSS: `.admin-sidebar-avatar-img` should have `width: 100%; height: 100%; object-fit: cover; border-radius: inherit;` to fill the avatar circle.

### UI: Dashboard Hero (`app/user/index.php`)

- The borrower dashboard "Borrower identity" section currently shows a `<span class="borrower-account-avatar">` with initials.
- When `$user['profile_pic_url']` is set, replace the initials span with `<img src="<?php echo appPath('serve-profile-pic.php'); ?>" alt="<?php echo htmlspecialchars($borrowerFullName); ?>">` inside the same styled wrapper.
- This does not require a new endpoint — the same `serve-profile-pic.php` is used for the dashboard avatar display.

---

## Testing Decisions

### What makes a good test here

- Test external behavior: given a valid image file input, the user record is updated and the file exists on disk.
- Test error paths: given an oversized file, wrong MIME type, or corrupted image, the user record is unchanged and no file is written.
- Do not test internal GD resizing logic directly — test the outcome (output file dimensions, file exists).

### Modules to test

1. **ProfilePicStorageService** — unit tests for `store()` and `remove()`:
   - Valid upload flow: file saved, DB updated, old file deleted if applicable
   - Invalid MIME: no file written, no DB change
   - Oversized file: no file written, no DB change
   - Corrupted image: no file written, no DB change
   - Remove: file deleted, DB cleared
   - Remove when no file: DB cleared gracefully, no error

2. **AccountService** — add integration-style tests (or unit with mocked PDO) for `updateProfilePic` and `removeProfilePic`.

3. **serve-profile-pic.php** — functional test:
   - Unauthenticated request → 403 or redirect to login
   - Authenticated borrower with no photo → 404
   - Authenticated borrower with photo → file served with correct Content-Type
   - Librarian viewing borrower photo → file served

### Prior art in the codebase

The existing book cover upload in `app/librarian/books.php` (`$storeUploadedBookCover`) is the closest prior art for file validation, directory creation, and image validation. Tests for the profile picture feature should follow the same patterns but use the new `ProfilePicStorageService` class.

---

## Out of Scope

- Drag-and-drop or paste-from-clipboard upload (simple file input only).
- Cropping tool on the client side (CSS `object-fit: cover` handles visual cropping).
- Animated GIF support (JPEG, PNG, WebP only).
- Automatic cropping to a specific shape beyond CSS square crop.
- Profile pictures for librarians or admins (only borrowers for now, though the storage service is general enough to extend later).
- Background processing or async upload (direct synchronous upload only).
- Multiple profile pictures or picture history.

---

## Further Notes

- The `storage/profile-pics/` directory must be created at deploy time or by a setup script — it does not exist yet and `mkdir` must be called with `recursive = true` on first use.
- The existing retired `/admin/upload-avatar.php` endpoint returns 410 Gone — no migration of old avatar data is needed since it was never fully implemented for borrowers.
- CSRF protection on the upload form uses the existing `accountCsrfScope = 'account_settings'` token, so no new CSRF infrastructure is needed.
- The `borrower-account-avatar` CSS class in `borrower.css` already has fixed dimensions (58×58px) and border-radius. The `<img>` substitution fits cleanly without layout changes.