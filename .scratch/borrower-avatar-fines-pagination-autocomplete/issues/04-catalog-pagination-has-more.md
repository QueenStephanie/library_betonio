# Issue: Catalog pagination with has_more

Status: needs-triage

## What to build

Paginate the librarian catalog with limit/offset and a `has_more` signal, ensuring pagination controls respect active search and filter criteria and clearly indicate when more results are available.

## Acceptance criteria

- [ ] Catalog query supports limit/offset and returns `has_more` based on remaining results.
- [ ] Pagination controls preserve search/filter criteria across navigation.
- [ ] UI indicates whether more results exist while browsing.

## Blocked by

None - can start immediately