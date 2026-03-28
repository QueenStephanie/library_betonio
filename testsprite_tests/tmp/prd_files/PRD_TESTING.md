# Product Requirements Document (PRD) - QueenLib

## Version 1.0 | March 27, 2026

---

## Executive Summary

**QueenLib** is a PHP-based library management system with secure user authentication, email verification, and account management. This document defines functional requirements, test scenarios, and acceptance criteria for comprehensive testing.

**Stack:** PHP 8.0+, MySQL 5.7+, PHPMailer, Session-based Auth  
**Database Port:** 3307  
**Timezone:** UTC (Global)

---

## 1. System Overview

### Core Features

1. **User Registration** - Create new library account with email verification
2. **Email Verification** - Token-based email link verification (24-hour expiration)
3. **User Login** - Session-based authentication with Bcrypt password hashing
4. **Password Reset** - Secure reset flow with 1-hour token expiration
5. **Dashboard** - Personalized user account and library statistics
6. **Session Management** - 1-hour timeout with security controls

### Key Constraints

- **Email Verification:** Token-based only (OTP completely removed)
- **Password Hashing:** Bcrypt with cost=12
- **Token Format:** 64-character hex strings (bin2hex(random_bytes(32)))
- **Session Cookies:** HTTPOnly, SameSite=Strict
- **Character Encoding:** UTF-8MB4

---

## 2. Functional Requirements

### 2.1 Registration Flow

**Requirement ID:** FR-REG-001  
**Title:** User Registration with Email Verification

**Functional Requirements:**

- Accept user input: First Name, Last Name, Email, Password, Password Confirmation
- Validate all fields before database insertion
- Generate 64-character verification token
- Store user with `is_verified = 0`
- Send verification email with token link
- Redirect to verification page with flash message: "Registration successful! Check your email to verify your account."

**Database Changes:**

```sql
INSERT INTO users (first_name, last_name, email, password_hash, verification_token, is_verified, created_at)
VALUES (?, ?, ?, ?, ?, 0, NOW())
```

**Email Generated:**

- Subject: "Verify Your QueenLib Account"
- Body: Contains link with format: `/verify-otp.php?email=USER_EMAIL&token=VERIFICATION_TOKEN`
- Token embedded in URL (required for auto-verification)

**Test Scenarios:**

| Scenario           | Input                              | Expected Output                                         | Status    |
| ------------------ | ---------------------------------- | ------------------------------------------------------- | --------- |
| Valid Registration | All fields filled, strong password | User created unverified, email sent, redirect to verify | PASS/FAIL |
| Duplicate Email    | Email already registered           | Error: "Email already registered"                       | PASS/FAIL |
| Weak Password      | Password < 8 characters            | Error: "Password must be at least 8 characters"         | PASS/FAIL |
| Password Mismatch  | Password ≠ Confirm Password        | Error: "Passwords do not match"                         | PASS/FAIL |
| Invalid Email      | Invalid email format               | Error: "Invalid email address"                          | PASS/FAIL |
| Empty Fields       | Missing required field             | Error: "All fields are required"                        | PASS/FAIL |

---

### 2.2 Email Verification Flow

**Requirement ID:** FR-VER-001  
**Title:** Email Token-Based Verification

**Functional Requirements:**

- Accept email and token from URL parameters
- Query database for matching email + verification_token
- Verify token is not expired (< 24 hours from creation)
- Check `is_verified` status
- On success: Update `is_verified = 1`, redirect to login with flash "Email verified! You can now log in."
- On failure: Display error message with links to register/login

**Database Query:**

```sql
SELECT id, is_verified FROM users
WHERE email = ? AND verification_token = ? AND created_at > (NOW() - INTERVAL 24 HOUR)
```

**Test Scenarios:**

| Scenario          | Input                               | Expected Result                                    | Status    |
| ----------------- | ----------------------------------- | -------------------------------------------------- | --------- |
| Valid Token (New) | Recent token, valid format, < 24h   | User verified, redirect to login                   | PASS/FAIL |
| Expired Token     | Token > 24 hours old                | Error: "Verification link expired"                 | PASS/FAIL |
| Invalid Token     | Wrong/modified token                | Error: "Invalid verification link"                 | PASS/FAIL |
| Already Verified  | Valid token, but user is_verified=1 | Error: "Account already verified" or auto-redirect | PASS/FAIL |
| Missing Email     | No email in URL                     | Redirect to register                               | PASS/FAIL |
| Missing Token     | No token in URL                     | Show processing message                            | PASS/FAIL |
| Malicious Token   | SQL injection attempt in token      | No error, token not matched                        | PASS/FAIL |

**Acceptance Criteria:**

- ✅ Auto-verification when clicking email link
- ✅ Immediate redirect to login on success
- ✅ Clear error message for failed verification
- ✅ No database updates if token invalid
- ✅ Rate limiting bypassed for successful verification

---

### 2.3 Login Flow

**Requirement ID:** FR-LOGIN-001  
**Title:** Secure User Login with Session Management

**Functional Requirements:**

- Accept email and password
- Validate email exists and `is_verified = 1`
- Compare submitted password against bcrypt hash
- On success: Create session, set user_id, redirect to dashboard with flash "Login successful!"
- On failure: Display error without revealing if email/password was incorrect
- Unverified users: Redirect to verify page
- Session timeout: 1 hour

**Database Query:**

```sql
SELECT id, email, password_hash, is_verified FROM users WHERE email = ?
```

**Session Management:**

```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['last_activity'] = time();
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict'
]);
```

**Test Scenarios:**

| Scenario            | Input                                  | Expected Result                        | Status    |
| ------------------- | -------------------------------------- | -------------------------------------- | --------- |
| Valid Credentials   | Correct email/password, verified       | Session created, redirect to dashboard | PASS/FAIL |
| Invalid Password    | Valid email, wrong password            | Error: "Invalid email or password"     | PASS/FAIL |
| Invalid Email       | Email doesn't exist                    | Error: "Invalid email or password"     | PASS/FAIL |
| Unverified Account  | Valid creds, but is_verified=0         | Redirect to verify page with email     | PASS/FAIL |
| Session Persistence | Login, navigate pages                  | Session maintained                     | PASS/FAIL |
| Session Timeout     | No activity > 1 hour                   | Session destroyed, redirect to login   | PASS/FAIL |
| XSS Injection       | Email: `<script>alert('xss')</script>` | Sanitized, no script execution         | PASS/FAIL |

**Acceptance Criteria:**

- ✅ Only verified users can login
- ✅ Session created with HTTPOnly, SameSite=Strict cookies
- ✅ Generic error messages (no email/password enumeration)
- ✅ Sessions timeout after 1 hour of inactivity
- ✅ User data accessible in dashboard immediately after login

---

### 2.4 Password Reset Flow

**Requirement ID:** FR-PWD-001  
**Title:** Secure Password Reset with Email Token

**Functional Requirements:**

**Stage 1 - Request Reset:**

- Accept email address
- Generate 64-character reset token
- Store token in database with 1-hour expiration
- Send email with reset link: `/reset-password.php?email=USER_EMAIL&token=RESET_TOKEN`
- Redirect to login with flash: "If an account exists, you will receive a password reset email."

**Database Update:**

```sql
UPDATE users SET reset_token = ?, reset_token_expires_at = (NOW() + INTERVAL 1 HOUR)
WHERE email = ?
```

**Stage 2 - Reset Password:**

- Accept email and token via URL parameters
- Accept new password and confirmation
- Validate token exists and not expired (< 1 hour)
- Validate password strength and match
- Update password hash, clear reset token
- Redirect to login with flash: "Password reset successfully!"

**Database Update:**

```sql
UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL
WHERE email = ? AND reset_token = ? AND reset_token_expires_at > NOW()
```

**Test Scenarios - Request Stage:**

| Scenario          | Input                   | Expected Result                          | Status    |
| ----------------- | ----------------------- | ---------------------------------------- | --------- |
| Valid Email       | Email in database       | Email sent, generic success message      | PASS/FAIL |
| Invalid Email     | Email not in database   | Generic success message (no enumeration) | PASS/FAIL |
| Already Requested | Reset link already sent | Another email sent with new token        | PASS/FAIL |

**Test Scenarios - Reset Stage:**

| Scenario          | Input                       | Expected Result                                 | Status    |
| ----------------- | --------------------------- | ----------------------------------------------- | --------- |
| Valid Token       | Fresh token, < 1 hour       | Password updated, redirect to login             | PASS/FAIL |
| Expired Token     | Token > 1 hour old          | Error: "Reset link expired"                     | PASS/FAIL |
| Invalid Token     | Wrong/modified token        | Error: "Invalid reset token"                    | PASS/FAIL |
| Weak Password     | Password < 8 characters     | Error: "Password must be at least 8 characters" | PASS/FAIL |
| Password Mismatch | New password ≠ confirmation | Error: "Passwords do not match"                 | PASS/FAIL |
| Missing Token     | No token in URL             | Error: "Invalid reset link"                     | PASS/FAIL |

**Acceptance Criteria:**

- ✅ Reset email sent with correct token in URL
- ✅ Token expires after 1 hour
- ✅ Original password should not be accepted after reset
- ✅ No SQL injection via token parameter
- ✅ Email pre-filled in reset form

---

### 2.5 Dashboard & Account Management

**Requirement ID:** FR-DASH-001  
**Title:** User Dashboard and Account Information

**Functional Requirements:**

- Display only to logged-in verified users
- Show user greeting: "Hi, [First Name]"
- Display account information:
  - Email address
  - Verification status
  - Member since (registration date)
  - Account action buttons (change password, update profile)
- Sidebar navigation with Dashboard, Account, Logout links
- Session validation on every page load

**Test Scenarios:**

| Scenario        | Input                                     | Expected Result                         | Status    |
| --------------- | ----------------------------------------- | --------------------------------------- | --------- |
| Logged In User  | Access dashboard                          | Dashboard displays user info            | PASS/FAIL |
| Not Logged In   | Access dashboard                          | Redirect to login, show timeout message | PASS/FAIL |
| Session Expired | Activity > 1 hour                         | Redirect to login with timeout message  | PASS/FAIL |
| User Navigation | Click Account link                        | Redirect to account.php                 | PASS/FAIL |
| User Navigation | Click Logout link                         | Session destroyed, redirect to login    | PASS/FAIL |
| XSS in Username | First name: `<img src=x onerror=alert()>` | Sanitized display, no execution         | PASS/FAIL |

**Acceptance Criteria:**

- ✅ Only verified, logged-in users access dashboard
- ✅ Session validation prevents access after timeout
- ✅ User information displayed without XSS vulnerabilities
- ✅ Navigation links work correctly

---

### 2.6 Logout

**Requirement ID:** FR-LOGOUT-001  
**Title:** Secure Session Termination

**Functional Requirements:**

- Clear session data
- Set flash message: "You have been logged out successfully."
- Redirect to login page
- Prevent back-button access to dashboard

**Test Scenarios:**

| Scenario       | Input                             | Expected Result                        | Status    |
| -------------- | --------------------------------- | -------------------------------------- | --------- |
| User Logs Out  | Click Logout                      | Session destroyed, redirected to login | PASS/FAIL |
| Back Button    | After logout, browser back button | Cannot access dashboard without login  | PASS/FAIL |
| Session Access | Try session data                  | $\_SESSION['user_id'] empty            | PASS/FAIL |

---

## 3. Security Requirements

### 3.1 Authentication Security

| Requirement                            | Status    | Test                           |
| -------------------------------------- | --------- | ------------------------------ |
| Bcrypt Password Hashing (cost=12)      | MANDATORY | Verify hash cost in Auth class |
| HTTPOnly Cookies                       | MANDATORY | Check XSS vulnerability        |
| SameSite=Strict                        | MANDATORY | Verify CSRF protection         |
| Session Timeout (1 hour)               | MANDATORY | Test inactivity logout         |
| Password Reset Token (64-char hex)     | MANDATORY | Verify token entropy           |
| Email Verification Token (64-char hex) | MANDATORY | Verify token entropy           |

### 3.2 Input Validation & Sanitization

| Input Type         | Validation           | Sanitization            | Test                                 |
| ------------------ | -------------------- | ----------------------- | ------------------------------------ |
| Email              | Valid format, unique | `sanitize()`            | Test SQL injection, duplicate emails |
| Password           | 8+ chars, strong     | None (plain input)      | Test weak passwords                  |
| First/Last Name    | Non-empty, string    | `sanitize()`            | Test HTML/JS injection               |
| Reset Token        | 64-char hex          | `trim()` (not sanitize) | Test token preservation              |
| Verification Token | 64-char hex          | `trim()` (not sanitize) | Test token preservation              |

### 3.3 SQL Injection Prevention

| Query Type           | Protection                          | Status         |
| -------------------- | ----------------------------------- | -------------- |
| All database queries | PDO prepared statements             | ✅ IMPLEMENTED |
| Token lookups        | Database-level timestamp comparison | ✅ IMPLEMENTED |
| User verification    | Purpose filtering in queries        | ✅ IMPLEMENTED |

### 3.4 XSS Prevention

| Output Location | Escaping Method      | Test                         |
| --------------- | -------------------- | ---------------------------- |
| HTML attributes | `htmlspecialchars()` | Test script tags in names    |
| Text content    | `htmlspecialchars()` | Test event handlers          |
| URLs            | `urlencode()`        | Test URL parameter injection |

---

## 4. Database Requirements

### 4.1 Core Tables

**users Table:**

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    verification_token VARCHAR(64),
    reset_token VARCHAR(64),
    reset_token_expires_at TIMESTAMP NULL,
    is_verified BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Key Indexes:**

- `idx_users_email` - For fast lookups by email
- `idx_users_verification_token` - For token verification
- `idx_users_reset_token` - For password reset lookups

### 4.2 Database Tests

| Test             | Query                         | Expected Result                         | Status    |
| ---------------- | ----------------------------- | --------------------------------------- | --------- |
| User Creation    | INSERT new user               | Row created with verification_token     | PASS/FAIL |
| Email Uniqueness | INSERT duplicate email        | Error: Unique constraint violation      | PASS/FAIL |
| Token Lookup     | SELECT by verification_token  | Correct user returned                   | PASS/FAIL |
| Expiration Check | SELECT with timestamp filter  | Only non-expired tokens returned        | PASS/FAIL |
| Trial Deletion   | Try to access deleted_at user | Should not be returned (if soft delete) | PASS/FAIL |

---

## 5. Email System Requirements

### 5.1 Email Configuration

**Service:** Gmail SMTP (PHPMailer)  
**From Email:** sordillamike1@gmail.com  
**Character Set:** UTF-8

### 5.2 Email Templates

**Registration Email:**

```
Subject: Verify Your QueenLib Account
To: [user_email]

Hi [first_name],

Click the link below to verify your email and activate your account:
[VERIFICATION_LINK_WITH_TOKEN]

This link expires in 24 hours.

Best regards,
QueenLib Team
```

**Password Reset Email:**

```
Subject: Reset Your QueenLib Password
To: [user_email]

Hi [first_name],

Click the link below to reset your password:
[RESET_LINK_WITH_EMAIL_AND_TOKEN]

This link expires in 1 hour.

Best regards,
QueenLib Team
```

### 5.3 Email Test Scenarios

| Scenario        | Input              | Expected Result                       | Status    |
| --------------- | ------------------ | ------------------------------------- | --------- |
| Email Delivery  | Registration       | Email sent to inbox within 30 seconds | PASS/FAIL |
| Email Content   | Check email body   | Contains verification token link      | PASS/FAIL |
| Email Subject   | Check subject line | Subject matches template              | PASS/FAIL |
| Spam Filtering  | Check spam folder  | Email in inbox, not spam              | PASS/FAIL |
| Multiple Emails | Resend request     | New email sent with new token         | PASS/FAIL |

---

## 6. Performance Requirements

### 6.1 Response Time SLAs

| Operation               | Target      | Threshold                            | Status    |
| ----------------------- | ----------- | ------------------------------------ | --------- |
| HTTP Request (any page) | < 500ms     | 200ms for login, 300ms for dashboard | PASS/FAIL |
| Database Query (single) | < 100ms     | Average query execution              | PASS/FAIL |
| Email Send              | < 5 seconds | Asynchronous preferred               | PASS/FAIL |
| Session Lookup          | < 50ms      | Per-request overhead                 | PASS/FAIL |

### 6.2 Load Testing (Out of scope for initial PRD)

- Concurrent users: 100+
- Requests per second: 50+
- Database connections: Pool size 20

---

## 7. Non-Functional Requirements

### 7.1 Compatibility

| Browser | Version | Status |
| ------- | ------- | ------ |
| Chrome  | Latest  | Test   |
| Firefox | Latest  | Test   |
| Safari  | Latest  | Test   |
| Edge    | Latest  | Test   |

### 7.2 Mobile Responsive

- Viewport: 320px - 1920px width
- Touch-friendly buttons (min 44px)
- Mobile form input optimization

### 7.3 Accessibility

- WCAG 2.1 Level AA compliance
- Semantic HTML structure
- ARIA labels for form inputs
- Screen reader compatibility

---

## 8. Test Execution Plan

### 8.1 Test Environment Setup

**Prerequisites:**

- XAMPP 3.3.0+ running
- MySQL running on port 3307
- Database schema initialized (run `backend/setup-db.php`)
- PHPMailer configured with Gmail credentials

**Reset Between Tests:**

- Clear database (delete test users)
- Clear session storage
- Clear browser cookies

### 8.2 Manual Test Checklist

**Registration Flow:**

- [ ] Register with valid data
- [ ] Verify email link works
- [ ] Login with verified account
- [ ] Register with duplicate email (error)
- [ ] Register with weak password (error)

**Password Reset Flow:**

- [ ] Request password reset
- [ ] Click email reset link
- [ ] Set new password
- [ ] Login with new password

**Security Tests:**

- [ ] Test XSS injection in form fields
- [ ] Test SQL injection in login form
- [ ] Test session hijacking (copy PHPSESSID)
- [ ] Test CSRF (cross-origin form submission)

**Session Management:**

- [ ] Login and access dashboard
- [ ] Check session timeout (1 hour)
- [ ] Navigate between pages
- [ ] Logout and verify session cleared

### 8.3 Success Criteria

✅ **All test scenarios pass**  
✅ **No security vulnerabilities detected**  
✅ **Response times within SLA**  
✅ **Email delivery successful**  
✅ **Session management working**  
✅ **No XSS/SQL injection vulnerabilities**

---

## 9. Known Limitations & Future Scope

### 9.1 Current Scope

- Session-based authentication only (no JWT/OAuth)
- Single-user sessions (no multi-device support)
- Basic account information (no full profile editing)
- No two-factor authentication (2FA)
- No role-based access control (RBAC)

### 9.2 Future Enhancements

- API token authentication (for mobile/SPA apps)
- User profile picture uploads
- Two-factor authentication (SMS/Authenticator)
- Social login (Google, GitHub)
- Account deletion with data cleanup
- Email notification preferences

---

## 10. Sign-Off

**Document Version:** 1.0  
**Date:** March 27, 2026  
**Last Updated:** March 27, 2026  
**Status:** Ready for Testing

**Stakeholders:**

- [ ] QA Lead - Approval
- [ ] Backend Architect - Approval
- [ ] Product Manager - Approval

---

## Appendix A: API Reference

### A.1 Frontend Login Flow

**POST** `/login.php`

```
Request:
  email: user@example.com
  password: SecurePassword123

Response Success (302 Redirect):
  Location: /library_betonio/index.php
  Set-Cookie: PHPSESSID=...; HttpOnly; SameSite=Strict

Response Error (200 OK):
  error: "Invalid email or password"
```

### A.2 Frontend Registration Flow

**POST** `/register.php`

```
Request:
  first_name: Jane
  last_name: Austen
  email: jane@example.com
  password: SecurePassword123
  password_confirm: SecurePassword123

Response Success (302 Redirect):
  Location: /library_betonio/verify-otp.php?email=jane@example.com
  Flash: "Registration successful! Check your email..."

Response Error (200 OK):
  error: "Email already registered"
```

### A.3 Email Verification

**GET** `/verify-otp.php?email=jane@example.com&token=VERIFICATION_TOKEN64BYTES`

```
Response Success (302 Redirect):
  Location: /library_betonio/login.php
  Flash: "Email verified! You can now log in."

Response Error (200 OK):
  error: "Verification link expired"
```

### A.4 Password Reset

**POST** `/forgot-password.php`

```
Request:
  email: jane@example.com

Response (302 Redirect):
  Location: /library_betonio/login.php
  Flash: "If an account exists, you will receive..."
```

**GET** `/reset-password.php?email=jane@example.com&token=RESETTOKEN64BYTES`

```
Displays form with pre-filled email
```

**POST** `/reset-password.php`

```
Request:
  email: jane@example.com
  reset_token: RESETTOKEN64BYTES
  password: NewPassword123
  password_confirm: NewPassword123

Response Success (302 Redirect):
  Location: /library_betonio/login.php
  Flash: "Password reset successfully!"

Response Error (200 OK):
  error: "Invalid or expired reset token"
```

---

**End of PRD Document**
