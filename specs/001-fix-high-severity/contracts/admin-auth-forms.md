# Contract: Admin Authentication and Privileged Form Routes

## Scope

This contract defines request/response behavior for admin authentication and privileged mutation form handlers in the traditional PHP route model.

## Shared Contract Rules

- Content type: `application/x-www-form-urlencoded` form submissions.
- Authentication gate for privileged routes: `admin_authenticated === true` in session.
- CSRF gate for privileged POST routes: session-scoped token required and validated.
- Invalid auth or CSRF: request is rejected, no mutation persists.
- Feedback: outcome communicated using existing SweetAlert2 page-alert pattern.

## Route: POST /admin-login.php

### Purpose

Authenticate admin and establish hardened session.

### Request Fields

- `username` (required)
- `password` (required)

### Server Behavior

1. Resolve credential source:
   - If DB admin credential exists, authenticate against DB hash.
   - Else allow env bootstrap credential authentication.
2. On success:
   - Regenerate session ID.
   - Set admin session keys (`admin_authenticated`, username, auth mode).
   - Create session-registry record for invalidation tracking.
   - Redirect to admin protected page (`admin-users.php`).
3. On failure:
   - Do not set privileged session state.
   - Return login page with error feedback.

### Outcomes

- Success: HTTP 302 redirect to protected admin route.
- Failure: HTTP 200 with error feedback and no auth state.

## Route: GET /admin-profile.php

### Purpose

Render admin profile page and include CSRF token for edit form.

### Contract

- Requires authenticated admin session.
- Ensures CSRF token exists in session.
- Renders hidden token field in edit form submissions.

## Route: POST /admin-profile.php?edit=1

### Purpose

Apply profile mutation for authenticated admin.

### Request Fields

- `csrf_token` (required)
- `name` (required)
- `email` (required, valid email)
- `phone` (required)
- `address` (required)
- `appointment_date` (required)

### Server Behavior

1. Verify authenticated admin session.
2. Verify `csrf_token` against session token.
3. Validate required fields.
4. On success, persist allowed profile mutation target (session and/or DB per implementation).
5. On failure, do not mutate profile state.

### Outcomes

- Success: HTTP 200 with success feedback.
- Failure: HTTP 200 with error feedback, no mutation.

## Route: GET /admin-change-password.php

### Purpose

Render password change form with CSRF token.

### Contract

- Requires authenticated admin session.
- Ensures CSRF token exists in session.
- Form posts to same route.

## Route: POST /admin-change-password.php

### Purpose

Persist secure admin password change.

### Request Fields

- `csrf_token` (required)
- `current_password` (required)
- `new_password` (required)
- `confirm_password` (required)

### Server Behavior

1. Verify authenticated admin session.
2. Verify CSRF token.
3. Validate `new_password === confirm_password` and policy constraints.
4. Verify `current_password` against active credential source:
   - DB hash in DB mode.
   - Env bootstrap credential in bootstrap mode (until DB credential exists).
5. Persist new password hash to DB credential record.
6. Invalidate all other active admin sessions.
7. Regenerate current session ID and keep current session authenticated.
8. Return success feedback.

### Outcomes

- Success: HTTP 200 with success feedback and rotated session identity.
- Failure: HTTP 200 with error feedback, no credential mutation.

## Route: GET /admin-logout.php

### Purpose

Terminate admin session securely.

### Server Behavior

1. Invalidate current session-registry record.
2. Clear all admin-scoped session keys including CSRF token.
3. Redirect to `admin-login.php`.

### Outcomes

- Success: HTTP 302 to login page with no admin auth state remaining.

## Security Invariants

- No privileged mutation executes without valid authenticated admin session.
- No privileged mutation executes without valid CSRF token.
- Password changes are persistent and verifiable across new sessions.
- After password change, only current session remains active.
