# PRD: Borrower Avatar Upload, Fines Receipt, Catalog Pagination, Manual Checkout Search

Status: needs-triage

## Problem Statement

Borrowers cannot upload a profile picture, librarian fines collection requires manual amount entry without an immediate receipt flow, the librarian book catalog risks performance issues with large inventories, and manual checkout is difficult to use at scale due to long dropdowns.

## Solution

Provide an uploadable borrower avatar that appears in the borrower profile and sidebar, auto-fill outstanding fines during collection with an editable amount and a printable receipt modal, ensure the librarian book catalog paginates to prevent overload, and replace manual checkout dropdowns with searchable autocomplete inputs.

## User Stories

1. As a borrower, I want to upload a profile photo, so that my account feels personalized.
2. As a borrower, I want my profile photo to appear in the sidebar, so that I can quickly confirm I am logged in.
3. As a borrower, I want my profile photo to appear in account settings, so that I can verify updates.
4. As a borrower, I want a default avatar when I have not uploaded a photo, so that the UI looks complete.
5. As a borrower, I want to update my profile photo later, so that I can keep it current.
6. As a librarian, I want fines to auto-fill based on outstanding balances, so that I avoid manual calculation.
7. As a librarian, I want to edit the auto-filled fine amount, so that I can handle partial or adjusted payments.
8. As a librarian, I want to see a clear fine receipt after collection, so that I can issue proof of payment.
9. As a librarian, I want the fine receipt to be printable, so that the borrower can leave with a hard copy.
10. As a librarian, I want the fine receipt to be downloadable, so that I can save a digital copy.
11. As a librarian, I want the fines list to keep working even with many fines, so that reports stay usable.
12. As a librarian, I want the books catalog to paginate, so that the page stays fast with large inventories.
13. As a librarian, I want pagination controls to respect search and filter criteria, so that navigation is consistent.
14. As a librarian, I want the catalog page to show whether more results exist, so that I can browse confidently.
15. As a librarian, I want manual checkout to offer borrower search, so that I can find users quickly.
16. As a librarian, I want manual checkout to offer book search, so that I can locate titles quickly.
17. As a librarian, I want manual checkout to store selected IDs, so that checkouts are accurate.
18. As a librarian, I want autocomplete to avoid loading huge dropdowns, so that the UI stays responsive.
19. As a librarian, I want the system to validate selected borrower and book values, so that invalid checkouts are blocked.
20. As a system admin, I want avatar file uploads to be validated, so that only images are stored.
21. As a system admin, I want avatar files stored in a public uploads folder, so that they are served safely.
22. As a system admin, I want avatar URLs stored in role profiles, so that they are easy to retrieve.

## Implementation Decisions

- Add a new avatar URL field to role profiles to store borrower avatar paths.
- Persist avatar files to a public uploads directory with type/size validation and safe file naming.
- Extend the account settings workflow to accept avatar uploads and update role profile metadata.
- Render borrower avatars in both the sidebar identity card and account summary card with fallback initials.
- Add a repository method to compute outstanding borrower fines using fine assessments when available, otherwise loan fine totals.
- Add an AJAX endpoint to fetch outstanding fine amounts for a selected borrower.
- Auto-fill the fine amount field and allow manual edits with a visible note when the amount is adjusted.
- Generate a fine collection receipt stored in the transaction receipt system with a fine-specific transaction type.
- Present a receipt modal after fine collection with print and download actions.
- Ensure book catalog pagination uses limit/offset with a `has_more` signal to drive next/previous controls.
- Replace manual checkout dropdowns with autocomplete inputs that query existing borrower/book search endpoints.
- Store selected borrower and book IDs in hidden fields used for submission.

## Testing Decisions

- Good tests verify external behavior: avatar upload validation, stored URL updates, and UI rendering fallbacks.
- Test repository methods that compute outstanding fines with and without fine assessment data.
- Test receipt creation for fine collections and that receipt metadata is returned for UI consumption.
- Test pagination returns correct counts and `has_more` behavior for large catalog datasets.
- Test manual checkout autocomplete selection flow (valid selection, invalid/empty selection).
- Use existing patterns in circulation and account services as prior art for behavior-focused tests.

## Out of Scope

- Admin and librarian avatar uploads.
- Payment gateway integration or automated payment processing.
- Large-scale UI redesign of borrower or librarian portals.
- Replacing the receipt page template beyond the new fine receipt flow.

## Further Notes

- Defaults and fallbacks should keep borrower UI functional even if avatars are missing.
- All changes should respect existing role-based access checks and CSRF protections.