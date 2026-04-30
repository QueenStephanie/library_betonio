# Issue: Fines list scalability

Status: needs-triage

## What to build

Ensure the fines list remains usable with many records by adding pagination or equivalent server-side limiting and updating the UI to navigate large datasets without performance degradation.

## Acceptance criteria

- [ ] Fines list endpoints return paginated/limited results suitable for large datasets.
- [ ] UI exposes navigation that keeps the fines list responsive with many records.
- [ ] Behavior is covered with tests for large result sets.

## Blocked by

None - can start immediately