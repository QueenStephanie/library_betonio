# Feature Specification: Admin High-Severity Security Remediation

**Feature Branch**: `001-fix-high-severity`  
**Created**: 2026-04-01  
**Status**: Draft  
**Input**: User description: "i want you to fix all the high severity first"

## Clarifications

### Session 2026-04-01

- Q: What should be the source of truth for admin credentials after this remediation? -> A: Hybrid: DB is primary for auth/password changes; env is bootstrap/fallback only.
- Q: After a successful admin password change, how should active admin sessions be handled? -> A: Invalidate all other sessions; keep current session active with regenerated session ID.
- Q: For the hybrid credential model, when should environment fallback be allowed? -> A: Only when no admin credential exists in DB (initial bootstrap only).
- Q: When environment bootstrap login is used (no DB admin credential exists), what should happen next? -> A: Continue using env credentials until admin manually creates DB credential later.
- Q: For privileged admin forms (profile/password), how should CSRF tokens be scoped? -> A: Session-scoped token reusable across forms until logout/session expiry.

## User Scenarios & Testing _(mandatory)_

### User Story 1 - Safe Admin Authentication (Priority: P1)

As a system owner, I need admin login to reject unsafe credential states and
establish a hardened authenticated session so unauthorized users cannot gain
admin access through configuration gaps or session abuse.

**Why this priority**: This blocks unauthorized control-plane access, which is
the highest-risk failure mode.

**Independent Test**: Validate login outcomes for valid credentials, invalid
credentials, and misconfigured credentials while verifying that successful login
creates a fresh authenticated session.

**Acceptance Scenarios**:

1. **Given** admin credentials are missing or unsafe for the active environment,
   **When** a login attempt is made, **Then** access is denied and no admin
   session is created.
2. **Given** valid admin credentials, **When** the admin logs in,
   **Then** authentication succeeds and the session identity is renewed.
3. **Given** invalid credentials, **When** login is attempted,
   **Then** authentication fails with no privileged session state set.

---

### User Story 2 - Real Password Change Enforcement (Priority: P2)

As an authenticated admin, I need password update requests to verify my current
password and persist the new secure password so account protection is real, not
cosmetic.

**Why this priority**: A non-functional password update gives a false sense of
security and leaves privileged access exposed.

**Independent Test**: Change password using correct and incorrect current
password values, then verify old password no longer works and new password works.

**Acceptance Scenarios**:

1. **Given** I am authenticated and provide the correct current password,
   **When** I submit a valid new password,
   **Then** the password is updated and future login requires the new password.
2. **Given** I provide an incorrect current password,
   **When** I submit a change request,
   **Then** the password is not changed and an error is shown.
3. **Given** I submit mismatched new password confirmation,
   **When** I submit the form,
   **Then** no update occurs and validation feedback is shown.

---

### User Story 3 - CSRF-Protected Admin Mutations (Priority: P3)

As an admin, I need profile and password updates to require request-origin
validation so malicious third-party pages cannot trigger privileged changes.

**Why this priority**: Cross-site request forgery can silently execute
high-impact admin actions.

**Independent Test**: Submit protected admin forms with valid tokens, missing
tokens, and tampered tokens to verify only valid requests succeed.

**Acceptance Scenarios**:

1. **Given** a valid authenticated admin session and valid request token,
   **When** an admin update form is submitted,
   **Then** the request is processed normally.
2. **Given** a missing or invalid request token,
   **When** a protected admin form is submitted,
   **Then** the request is rejected and no data is changed.

### Edge Cases

- Admin submits a stale form from an old browser tab after token rotation.
- Admin tries to submit privileged forms after session timeout.
- Environment credentials are partially configured (username present, password
  absent or empty).
- Environment login remains active for extended periods because no DB admin
  credential has been manually provisioned yet.
- Concurrent admin sessions attempt password change while one session logs out.
- Password changes in one browser tab while another active admin session submits
  a privileged request.
- A CSRF token from an ended session (logout/timeout) is replayed in a new
  request.

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST block admin login when required admin credentials are
  missing or empty for the active runtime environment.
- **FR-002**: System MUST authenticate admin login against database-stored admin
  credentials as the primary source of truth.
- **FR-003**: System MUST allow environment credentials only as controlled
  bootstrap-mode behavior while no admin credential exists in the database.
- **FR-004**: System MUST continue allowing environment-backed admin login until
  an admin credential is manually created in the database.
- **FR-005**: System MUST renew session identity on successful admin login.
- **FR-006**: System MUST clear all admin-scoped session state on admin logout.
- **FR-007**: System MUST require current-password validation before applying an
  admin password change.
- **FR-008**: System MUST require and validate new-password confirmation before
  applying an admin password change.
- **FR-009**: System MUST persist successful admin password changes so they remain
  effective across sessions and browser restarts.
- **FR-010**: System MUST invalidate all other active admin sessions after a
  successful password change while keeping the current session active.
- **FR-011**: System MUST regenerate current session identity after successful
  password change.
- **FR-012**: System MUST use a session-scoped anti-forgery token that is
  reusable across privileged admin forms until logout or session expiry.
- **FR-013**: System MUST reject privileged admin form submissions that do not
  include a valid anti-forgery token for the active session.
- **FR-014**: System MUST prevent data mutation when privileged form validation
  fails.
- **FR-015**: System MUST provide clear success/error feedback for all admin auth
  and mutation outcomes.

### Constitutional Requirements _(mandatory)_

- **CR-001 (Role Boundaries)**: Only authenticated admin or superadmin contexts
  can access and mutate admin profile/password actions; unauthenticated and
  borrower/librarian contexts are denied.
- **CR-002 (Librarian Ownership)**: This feature MUST NOT introduce any change to
  book, reservation, transaction, or fine ownership; existing librarian-only
  operational boundaries remain intact.
- **CR-003 (Auth Security)**: Feature MUST harden admin authentication state
  transitions, including secure session lifecycle and secure password update
  behavior.
- **CR-004 (Data Model Impact)**: Any password or token state changes MUST
  preserve data integrity and auditable outcomes for privileged account updates.
- **CR-005 (UX Feedback)**: Admin login, profile update, and password update
  outcomes MUST present consistent success/error/confirmation feedback patterns.
- **CR-006 (Route Protection)**: Privileged admin routes and handlers MUST enforce
  authenticated role checks before processing requests.

### Key Entities _(include if feature involves data)_

- **Admin Session**: Represents authenticated admin state, including session
  identity lifecycle, authenticated flags, admin-scoped session keys, and
  concurrent-session invalidation behavior.
- **Admin Credential Configuration**: Represents runtime admin login credential
  sources and validation state where DB credentials are primary and env
  credentials are initial-bootstrap only when DB admin credentials do not exist.
- **Credential Source State**: Represents whether authentication is operating in
  bootstrap-mode (env-backed) or DB-backed mode based on DB credential
  provisioning state.
- **Admin Password Record**: Represents stored admin secret state and metadata
  used to validate current password and apply a secure replacement.
- **Anti-Forgery Token**: Represents request-origin validation token for
  privileged admin form submissions, scoped to the active session and invalid
  after logout/session expiry.

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: 100% of tested login attempts with missing/empty admin credential
  configuration are denied with no privileged session established.
- **SC-002**: 100% of successful admin logins renew session identity before
  redirecting to protected pages.
- **SC-003**: 100% of tested password changes with invalid current password or
  mismatched confirmation are rejected without changing credentials.
- **SC-004**: 100% of tested privileged form submissions without a valid
  anti-forgery token are rejected with no persisted mutation.
- **SC-005**: After successful password change, 100% of non-current active admin
  sessions are invalidated and cannot execute privileged actions.
- **SC-006**: After successful password change, old-password login success rate is
  0% and new-password login success rate is 100% in acceptance tests.
- **SC-007**: A CSRF token issued in an active session is accepted across
  privileged admin forms in that session and rejected after logout/session
  expiry.

## Assumptions

- Existing admin login and protected-page architecture remains the foundation; no
  full identity-provider replacement is included in this feature.
- Hybrid credential strategy is approved: DB-backed admin credentials are
  primary, environment credentials are initial-bootstrap only when DB admin
  credentials do not yet exist.
- Transition from bootstrap-mode to DB-backed mode is a manual administrative
  provisioning step and is not auto-enforced by first successful env login.
- High-severity remediation scope is limited to admin authentication/session,
  admin password change, and anti-forgery protections for admin mutation forms.
- Existing user-role model remains unchanged; this feature does not redefine
  business permissions outside high-severity remediation.
- Deployment environments can supply required secure admin credentials and
  maintain existing session infrastructure.
