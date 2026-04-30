# Issue: Manual checkout autocomplete + validation

Status: needs-triage

## What to build

Replace manual checkout dropdowns with borrower and book autocomplete inputs that query existing search endpoints, store selected IDs in hidden fields, and validate selections before submitting.

## Acceptance criteria

- [ ] Borrower and book inputs provide autocomplete search and avoid loading full dropdowns.
- [ ] Selected borrower/book IDs are stored for submission and validated for correctness.
- [ ] Invalid or empty selections are blocked with clear feedback.

## Blocked by

None - can start immediately