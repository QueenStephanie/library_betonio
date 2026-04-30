# Issue: Fine auto-fill + receipt modal

Status: needs-triage

## What to build

When collecting a fine, auto-fill the amount based on the borrower’s outstanding balance, allow edits with a visible adjustment note, create a fine-specific receipt transaction, and show a receipt modal with print and download actions after collection.

## Acceptance criteria

- [ ] Fine amount auto-fills from outstanding balance and allows manual edits with an adjustment note shown.
- [ ] Fine collection creates a receipt record with a fine-specific transaction type and returns metadata for UI use.
- [ ] Receipt modal renders after collection and supports print and download actions.

## Blocked by

None - can start immediately