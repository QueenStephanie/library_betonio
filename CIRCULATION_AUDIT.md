# Circulation Flow Audit Report

## Flow Diagram
```
Register → Login → Browse → Reserve(pending)
                                ↓
                      Librarian marks ready → Email notify borrower
                                ↓
                      Librarian checkout → Loaned(active, 14d)
                                              ↓
                                    ┌────────┼─────────┐
                                    ↓        ↓          ↓
                               Returned  Overdue    Renewed
                               (checkin)  (>due)    (+7d, max 2×)
```

---

## State Machine & Implementation Status

### 1. Register → Login → Browse → Reserve (pending)
**Status: ✅ IMPLEMENTED**

| Step | File(s) | Notes |
|------|---------|-------|
| Register | `register.php` → `app/public/register.php` | PHPMailer OTP verification flow |
| Login | `login.php` → `app/public/login.php` | Session + role gate |
| Browse catalog | `app/user/catalog.php` → `CirculationRepository::getBorrowerCatalog()` | Search by title/author/isbn, category filter, availability shown |
| Reserve (create) | `app/user/catalog.php` POST → `CirculationRepository::createBorrowerReservation()` | Status: `pending`. Business rules: auth check, book exists, book active, no duplicate, max 5 active. Receipt generated. |

### 2. Librarian Marks Ready → Notify Borrower
**Status: ✅ IMPLEMENTED**

| Step | File(s) | Notes |
|------|---------|-------|
| Approve reservation | `app/librarian/reservations.php` POST(approve) → `LibrarianPortalRepository::updateReservationStatus($db, $id, 'approve')` | Status: `pending`→`ready`. Sets `ready_until` / `expires_at` = NOW() + 3 days |
| Reject reservation | Same file, action=reject | Status: `pending`→`cancelled` |
| Cancel reservation | Same file, action=cancel | Status: `pending`/`ready`→`cancelled` |
| Email notification | `LibrarianPortalRepository::sendReservationReadyNotification()` | Sends email via `MailHandler::sendReservationReadyEmail()` on approve. Joins users + books to personalize. |

### 3. Librarian Checkout → Loaned(active, 14d)
**Status: ✅ IMPLEMENTED** (two paths)

#### Path A: Manual checkout
`app/librarian/circulation.php` POST(checkout) → `LibrarianPortalRepository::checkoutLoan()`:
- Locks + assigns an `available` copy → `loaned`
- Creates loan: status=`active`, due_at=NOW()+14d
- Logs `loan_events` entry (type=`checkout`)
- Generates receipt

#### Path B: Reservation pickup checkout
Two entry points:
- `app/librarian/circulation.php` POST(checkout_reservation) → `checkoutReadyReservation()`
- `app/librarian/reservations.php` POST(checkout) → `checkoutReadyReservation()`
- Updates reservation: `ready`→`fulfilled`, sets `picked_up_at`
- Creates loan: status=`active`, due_at=NOW()+14d, links `reservation_id`
- Copy: `available`→`loaned`
- Generates receipt

### 4. Returned (Check-in)
**Status: ✅ IMPLEMENTED**

`app/librarian/circulation.php` POST(checkin) → `LibrarianPortalRepository::checkInLoan()`:
- Validates status is `active`, `overdue`, or `borrowed`
- Sets loan status → `returned`, sets `returned_at` = NOW()
- Sets copy → `available`
- Logs `loan_events` (type=`return`)
- Generates receipt

### 5. Overdue (>due)
**Status: ❌ NOT IMPLEMENTED (runtime-only)**

**Problem:** No DB status update ever changes a loan to `overdue`.

- `ACTIVE_LOAN_STATUSES = ['active', 'overdue', 'borrowed']` — lists 'overdue' but nothing sets it
- `is_overdue` is computed at **display time** via PHP: `$due_at < time() && $status !== 'returned'`
- No cron job, no background task, no stored procedure to mark loans overdue
- The DB status stays `'active'` even when past due

**Impact:**
- `getCirculationRows()` returns loans with `is_overdue=true` only in UI display, not in DB
- Queries filtering on status='overdue' will return zero rows
- Fine automation cannot trigger because overdue status never materializes in DB
- `getBorrowerOverview()` counts overdue loans by checking DB status only (misses them)

### 6. Renewed (+7d, max 2×)
**Status: ✅ IMPLEMENTED** (both sides)

**Borrower self-renew:** `app/user/history.php` POST → `CirculationRepository::renewBorrowerLoan()`:
- Checks: owns loan, status active, no queue for title, renewal count < 2
- Extends due_at by 7 days from max(NOW(), current_due)
- Sets status→`active`, increments renewal count
- Logs `loan_events` (type=`renewal`)
- Generates receipt

**Librarian renew:** `app/librarian/circulation.php` POST(renew) → `LibrarianPortalRepository::renewLoan()`:
- Same logic but also checks queue on book_copy's book_id

### 7. Fines
**Status: ⚠️ MANUAL ONLY**

- `app/librarian/fines.php`: Manual fine collection form + reporting (MTD, all-time)
- No automated overdue fine calculation or accrual
- No fine auto-assessment on check-in
- `getBorrowerOverview()` checks `loans.fine_amount` or `fine_assessments` table
- No integration between check-in → fine calculation

---

## Critical Issues

### P0: No Overdue Automation — ✅ FIXED
**Fix:** Created `cron/process_overdue.php` — run daily via `php cron/process_overdue.php`.
- Marks loans past due (`due_at < NOW()`) with status `'active'`/`'borrowed'` → `'overdue'`
- Uses `FOR UPDATE` row-level locking per loan
- Logs `loan_events` with type `'overdue'`
- Schema-tolerant (resolves columns, checks table existence)

### P1: Fine Calculation Missing — ✅ FIXED
**Fix:** `LibrarianPortalRepository` now has `FINE_RATE_PER_DAY = 10.0`.
- `checkInLoan()` auto-calculates fine when loan is overdue: `ceil(overdue_days) × FINE_RATE_PER_DAY`
- Fine is saved to `loans.fine_amount` column (if it exists)
- Non-destructive: uses `COALESCE(..., 0) + fine` so existing fines aren't overwritten

### P2: ~~Renewal Queue Check Gap in Borrower Self-Renew~~ FALSE ALARM (VERIFIED CORRECT)
`renewBorrowerLoan()` does a real queue check inside the transaction (lines 1026-1048) before calling `evaluateBorrowerRenewalRules()` (lines 1050-1059). The initial `preCheck` outside the transaction is just an optimistic gate. The actual business rules are re-evaluated after the real queue check. This is correct behavior.

### P3: No Reservation Expiry Automation — ✅ FIXED
**Fix:** Created `cron/expire_reservations.php` — run daily via `php cron/expire_reservations.php`.
- Cancels `'ready'` reservations where `ready_until`/`expires_at` < NOW()
- Uses `FOR UPDATE` locking per reservation
- Logs event if `reservation_events` table exists
- Schema-tolerant

---

## Summary by Flow Step

| Step | Status | Notes |
|------|--------|-------|
| Register | ✅ | OTP email verification |
| Login | ✅ | Session + role gate |
| Browse | ✅ | Search, filter, availability |
| Reserve (pending) | ✅ | Rules: auth, book valid, no dupe, ≤5 active |
| Librarian approve (ready) | ✅ | 3-day pickup window, email sent |
| Borrower notified | ✅ | Email |
| Librarian checkout (loaned) | ✅ | 14d loan, copy→loaned, receipt |
| Returned (checkin) | ✅ | Copy→available, receipt |
| Overdue (>due) | ⚠️✅ | `cron/process_overdue.php` — run daily to mark DB status 'overdue' |
| Renewed (+7d, max 2×) | ✅ | Both borrower & librarian paths |
| Fines | ⚠️✅ | Auto-calculated on check-in via `checkInLoan()` (PHP10/day). Historical fines still manual. |
| Reserve expiry | ✅ | `cron/expire_reservations.php` — run daily to cancel unclaimed ready reservations |
