<!--
Sync Impact Report
Version change: N/A (template) -> 1.0.0
Modified principles:
- [PRINCIPLE_1_NAME] -> I. Role Ownership Is Mandatory
- [PRINCIPLE_2_NAME] -> II. Librarian-Centric Operations
- [PRINCIPLE_3_NAME] -> III. Verified Identity and Recovery Security (NON-NEGOTIABLE)
- [PRINCIPLE_4_NAME] -> IV. Data Integrity and Auditability
- [PRINCIPLE_5_NAME] -> V. Consistent User Feedback with SweetAlert2
Added sections:
- Technical Standards and Deployment Constraints
- Delivery Workflow and Quality Gates
Removed sections:
- None
Templates requiring updates:
- ✅ .specify/templates/plan-template.md
- ✅ .specify/templates/spec-template.md
- ✅ .specify/templates/tasks-template.md
- ⚠ pending .specify/templates/commands/*.md (directory not present)
Runtime guidance updates:
- ✅ Reviewed README.md, docs/QUICK_START.md, and docs/BACKEND.md; no conflicting constitution references found
Follow-up TODOs:
- TODO(COMMAND_TEMPLATES_DIR): add .specify/templates/commands/ if command-level constitutional checks are required
-->

# Library Betonio Constitution

## Core Principles

### I. Role Ownership Is Mandatory

The system MUST enforce this role hierarchy and ownership model at route, API,
and UI levels: Superadmin (full control, immutable account), Admin (user
management only), Librarian (books, reservations, transactions, fines), and
Borrower (self-service account and borrowing requests). Admins MUST NOT manage
books or approve/reject reservations. Librarians MUST be the primary operator
for circulation workflows. Superadmin records MUST NOT be modified or deleted.
Rationale: strict separation of duties reduces privilege misuse and ensures clear
operational accountability.

### II. Librarian-Centric Operations

Book management and reservation decisions MUST be handled only by Librarian
accounts. Reservation lifecycle MUST be `pending -> approved|rejected` and MUST
be actioned by Librarian users. Borrowing checkout, return processing, due-date
updates, and fine clearance MUST be librarian-mediated operations. Borrowers
MUST NOT directly checkout books or mutate circulation records. Rationale: a
single accountable operator role preserves inventory integrity and policy
consistency.

### III. Verified Identity and Recovery Security (NON-NEGOTIABLE)

Email verification MUST be completed before first successful login for borrower
accounts. Verification and password reset flows MUST use cryptographically
strong, time-limited tokens and MUST invalidate tokens after use. Passwords MUST
be stored using `password_hash()`. All database writes and lookups MUST use
prepared statements (PDO). State-changing forms MUST include CSRF protection.
Session authentication and role-based route protection MUST be enforced for all
privileged endpoints. Rationale: account lifecycle security is foundational to
trust and abuse prevention.

### IV. Data Integrity and Auditability

The data model MUST include verification state and token expiry fields on user
accounts, a password reset token store with expirations, and reservation status
tracking with explicit state values. Privileged and sensitive operations (role
changes, reservation decisions, fine clearance, credential recovery) MUST be
auditable by actor, timestamp, and outcome. Rationale: traceable state and
action history are required for supportability, compliance, and incident review.

### V. Consistent User Feedback with SweetAlert2

UI feedback for success, error, and confirmation states MUST use SweetAlert2 for
admin, librarian, and borrower flows. Destructive and approval actions MUST use
explicit confirmation dialogs before commit. Security and auth failures MUST
present clear, actionable error messaging without leaking sensitive internals.
Rationale: consistent feedback improves usability and reduces operator mistakes.

## Technical Standards and Deployment Constraints

- Technology stack MUST remain compatible with: HTML/CSS/JavaScript frontend,
  PHP 8.2.12 backend, MySQL storage, and Apache 2.4.58 under XAMPP for local
  development.
- Deployment artifacts MUST remain shared-hosting compatible, including
  InfinityFree constraints.
- Email delivery MUST be configurable via SMTP/PHPMailer with environment-based
  credentials.
- Token-bearing links in production MUST use HTTPS URLs.
- Access-control decisions MUST be centralized and reused across page routes and
  API routes to avoid policy drift.

## Delivery Workflow and Quality Gates

- Every spec and plan MUST include a role-impact matrix proving compliance with
  the role ownership model.
- Every feature touching auth, reservations, circulation, or fines MUST include
  negative-path tests for unauthorized role access.
- Schema changes affecting verification, reset, or reservation status MUST ship
  with migration and rollback notes.
- UI changes for action outcomes MUST include SweetAlert2 behavior definitions
  for success, confirm, and error states.
- Pull requests MUST include a constitution compliance checklist before merge.

## Governance

This constitution supersedes conflicting local workflow preferences for this
repository. Amendments MUST be submitted through a documented change proposal
that includes: rationale, impacted principles/sections, template sync updates,
and migration notes for in-flight work.

Versioning policy for this constitution:

- MAJOR: backward-incompatible governance changes or principle removals.
- MINOR: new principle/section or materially expanded mandatory guidance.
- PATCH: wording clarifications, typo fixes, and non-semantic refinements.

Compliance review expectations:

- Planning artifacts MUST pass Constitution Check gates before implementation.
- Code review MUST verify role boundaries, security controls, and auditability.
- Any approved exception MUST be time-bound, explicitly justified, and tracked.

**Version**: 1.0.0 | **Ratified**: 2026-04-01 | **Last Amended**: 2026-04-01
