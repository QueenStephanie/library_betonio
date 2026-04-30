# Issue: Borrower avatar upload + render

Status: needs-triage

## What to build

Enable borrowers to upload an avatar image, persist the file to the public uploads folder with validation, store the avatar URL on the borrower role profile, and render the avatar (with default fallback) in both the sidebar identity card and account settings/profile view.

## Acceptance criteria

- [ ] Borrower can upload an image avatar that is validated for type/size and stored in public uploads with a safe filename.
- [ ] Borrower role profile stores the avatar URL and the UI renders it in sidebar and account settings with a default fallback when missing.
- [ ] Access checks and CSRF protections remain enforced during upload and update.

## Blocked by

None - can start immediately