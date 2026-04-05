# Library Betonio - PHP Backend Setup Guide

## 📋 Table of Contents

- [System Requirements](#system-requirements)
- [Installation & Configuration](#installation--configuration)
- [Database Setup](#database-setup)
- [API Endpoints](#api-endpoints)
- [Email Configuration](#email-configuration)
- [Security Features](#security-features)
- [Testing](#testing)

---

## 🖥️ System Requirements

### XAMPP Version

- **XAMPP 3.3.0** (as specified)
  - Apache 2.4.58
  - MySQL 15.1 (MariaDB) - Running on port **3307**
  - PHP 8.2.12

### Additional Requirements

- PHPMailer library (for email sending)
- PHP extensions: PDO, PDO_MySQL, OpenSSL

---

## 📦 Installation & Configuration

### 1. Start XAMPP Services

```bash
# Start Apache and MySQL (port 3307)
# Ensure MySQL is running on port 3307 (configured in XAMPP settings)
```

### 2. Directory Structure

```
library_betonio/
├── backend/
│   ├── api/                    # API endpoints
│   │   ├── register.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── forgot-password.php
│   │   ├── reset-password.php
│   │   └── verify-reset-token.php
│   ├── classes/                # Core classes
│   │   ├── Auth.php
│   │   └── PasswordRecovery.php
│   ├── config/                 # Configuration files
│   │   ├── Database.php
│   │   ├── email.config.php
│   │   ├── schema.sql
│   │   └── init-db.php
│   └── mail/                   # Email handling
│       └── MailHandler.php
```

### 3. Install PHPMailer

```bash
# Navigate to backend directory
cd c:\xampp\htdocs\library_betonio\backend

# Using Composer (recommended)
composer require phpmailer/phpmailer

# Or manually download PHPMailer from: https://github.com/PHPMailer/PHPMailer
```

---

## 🗄️ Database Setup

### Step 1: Initialize Database

1. Open browser and navigate to:
   ```
   http://localhost/library_betonio/backend/config/init-db.php
   ```
2. The script will create:
   - Database: `library_betonio`

- Tables: users, verification_attempts, login_history, admin_credentials, admin_session_registry

### Step 2: Verify Database Setup

```bash
# Access MySQL on port 3307
mysql -h localhost -P 3307 -u root

# Check database
USE library_betonio;
SHOW TABLES;
```

### Database Tables

#### users

- Stores user registration and authentication data
- Fields: id, first_name, last_name, email, password_hash, is_verified, etc.

#### verification_attempts

- Tracks password reset attempts for security and rate limiting
- Prevents brute force attacks

#### login_history

- Logs all login/logout activities
- Useful for security audits

#### admin_credentials

- DB-primary admin credential store (username + bcrypt hash)
- Tracks `password_changed_at` for operational audits
- Environment credentials are bootstrap-only fallback when this table has no active row

#### admin_session_registry

- Stores hashed admin PHP session identifiers (`sha256(session_id)`) for active-session management
- Enables cross-session invalidation after admin password changes
- Keeps per-session metadata (`auth_mode`, `ip_address`, `user_agent`, `last_seen_at`, `invalidated_at`)

---

## ⚙️ Email Configuration

### Configure SMTP Settings

Update **`backend/config/email.config.php`** with your email provider:

```php
'smtp' => [
    'host' => 'smtp.gmail.com',           // Your SMTP host
    'port' => 587,                        // 587 (TLS) or 465 (SSL)
    'username' => 'your-email@gmail.com', // Your email
    'password' => 'your-app-password',    // App-specific password
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Library Betonio'
]
```

### Gmail Setup (Recommended)

1. Enable "Less secure app access" or use App Passwords
2. Generate App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer"
   - Copy the generated password
3. Update `email.config.php` with App Password

### Alternative Email Providers

- **Outlook/Hotmail**: smtp.office365.com:587
- **SendGrid**: smtp.sendgrid.net:587
- **Mailgun**: smtp.mailgun.org:587

### Delete init-db.php After Setup

```bash
# For security, delete the initialization script after database setup
# OR rename it to something non-obvious
```

---

## 🔌 API Endpoints

### Authentication Endpoints

#### 1. **Register User**

```
POST /backend/api/register.php
Content-Type: application/json

Body:
{
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "password": "SecurePass123!",
    "password_confirm": "SecurePass123!"
}

Response (201):
{
    "success": true,
    "message": "Registration successful",
    "user_id": 1,
    "email": "john@example.com"
}
```

#### 2. **Login User**

```
POST /backend/api/login.php
Content-Type: application/json

Body:
{
    "email": "john@example.com",
    "password": "SecurePass123!"
}

Response (200):
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com"
    }
}
```

#### 3. **Logout**

```
POST /backend/api/logout.php

Response:
{
    "success": true,
    "message": "Logout successful"
}
```

### Email Verification

Email verification uses a **token-based link** sent via PHPMailer. When a user registers, a unique verification token is generated and stored in the `users` table. The user clicks the link in their email, which directs them to `verify-otp.php?email=...&token=...`. The token is validated and the account is marked as verified.

If an unverified user attempts to login, they are redirected to the verification page with a prompt to resend the verification email.

#### Token Verification (via email link)

```
GET /verify-otp.php?email=user@example.com&token=abc123...

Response: Redirects to login.php on success
```

#### Resend Verification Email

```
POST /verify-otp.php
Content-Type: application/x-www-form-urlencoded

Body:
{
    "email": "user@example.com",
    "resend_verification": "1"
}

Response: Redirects back to verify-otp.php with success/error message
```

### Password Recovery Endpoints

#### 7. **Forgot Password**

```
POST /backend/api/forgot-password.php
Content-Type: application/json

Body:
{
    "email": "john@example.com"
}

Response (200):
{
    "success": true,
    "message": "If email exists, password reset link will be sent"
}
```

#### 8. **Verify Reset Token**

```
POST /backend/api/verify-reset-token.php
Content-Type: application/json

Body:
{
    "email": "john@example.com",
    "reset_token": "token_from_email_link"
}

Response:
{
    "success": true,
    "message": "Reset token is valid",
    "user_id": 1
}
```

#### 9. **Reset Password**

```
POST /backend/api/reset-password.php
Content-Type: application/json

Body:
{
    "email": "john@example.com",
    "reset_token": "token_from_email_link",
    "new_password": "NewSecurePass456!",
    "confirm_password": "NewSecurePass456!"
}

Response:
{
    "success": true,
    "message": "Password reset successfully"
}
```

---

## 🔒 Security Features

### 1. **Password Security**

- Bcrypt hashing with cost factor 12
- Minimum 8 characters required
- Password strength validation:
  - Must contain uppercase letter
  - Must contain lowercase letter
  - Must contain number
  - Must contain special character

### 2. **Email Verification Security**

- Cryptographically secure tokens (random_bytes 32)
- 24-hour token expiration
- Token invalidated after successful verification
- Automatic rollback if verification email fails to send

### 3. **Rate Limiting**

- Password reset: 3 requests per hour
- Prevents brute force and spam attacks

### 4. **Database Security**

- Prepared statements to prevent SQL injection
- Password hashing before storage
- Tokens hashed with bcrypt
- IP address logging for audits

### 5. **Session Management**

- Session-based authentication
- Auto-logout functionality
- Login history tracking
- User agent logging

### 6. **Admin Control-Plane Hardening**

- Hybrid admin auth mode:
  - DB credentials are primary (`admin_credentials`)
  - Env credentials allowed only in bootstrap mode (no active DB admin credential)
- Session ID regeneration on admin login and successful admin password change
- Admin session registry lifecycle:
  - Create/update row on login
  - Invalidate current row on logout
  - Invalidate all non-current rows on password change
- Session-scoped reusable CSRF token for privileged admin form mutations

Operational notes:

- Run `php backend/setup-db.php` after pulling remediation changes to create/verify admin tables and indexes idempotently.
- In non-development environments, admin bootstrap credentials should never be defaults.
- Set `SUPERADMIN_USERNAME` in `.env` or `.env.production` to declare the protected superadmin identity (falls back to `ADMIN_USERNAME` if omitted).
- `backend/setup-db.php` now auto-provisions or updates one managed superadmin user (`username@local.admin`) and enforces uniqueness via `users.is_superadmin` normalization.
- Superadmin safety controls block deactivation and deletion through repository and admin-user-management flows.
- Password updates are persisted to DB even if login originally occurred in bootstrap mode.

### 7. **Email Security**

- TLS/SSL encryption for SMTP
- Secure token generation (random_bytes)
- Token expiration validation
- Email link includes one-time token

---

## 🧪 Testing

### Manual Testing Steps

#### 1. **Test Registration**

```bash
curl -X POST http://localhost/library_betonio/backend/api/register.php \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "John",
    "last_name": "Doe",
    "email": "test@example.com",
    "password": "SecurePass123!",
    "password_confirm": "SecurePass123!"
  }'
```

#### 2. **Test Email Verification**

After registration, check the email inbox for a verification link. Click the link to verify the account. The link format is:
```
http://localhost/library_betonio/verify-otp.php?email=test@example.com&token=abc123...
```

To resend a verification email:
```bash
curl -X POST http://localhost/library_betonio/verify-otp.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'email=test@example.com&resend_verification=1'
```

#### 3. **Test Login**

```bash
curl -X POST http://localhost/library_betonio/backend/api/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "SecurePass123!"
  }'
```

#### 4. **Test Password Recovery**

```bash
# Request password reset
curl -X POST http://localhost/library_betonio/backend/api/forgot-password.php \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'

# Check email for reset link and token
```

### Frontend Testing

1. Navigate to: `http://localhost/library_betonio/register.php`
2. Fill in registration form
3. Submit — should send verification email with token link
4. Open the email and click the verification link
5. Should redirect to login page with success message
6. Try logging in with unverified account — should redirect to verification page with resend prompt

---

## 📝 Environment Variables (Optional)

Create `.env` file in backend root for sensitive data:

```
DB_HOST=localhost
DB_PORT=3307
DB_NAME=library_betonio
DB_USER=root
DB_PASS=

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

Modify `Database.php` and `email.config.php` to read from `.env`

---

## 🚨 Troubleshooting

### MySQL Connection Failed

- Verify MySQL is running on port 3307
- Check XAMPP MySQL settings
- Verify credentials in `Database.php`

### Email Not Sending

- Check SMTP credentials in `email.config.php`
- Verify Gmail App Password is correct
- Check email configuration and test SMTP connection
- Review PHP error logs

### Verification Email Not Sending

- Check SMTP credentials in `email.config.php`
- Verify Gmail App Password is correct
- Check email configuration and test SMTP connection
- Review PHP error logs

### Verification Link Not Working

- Clear browser cache
- Verify database tables exist
- Check token expiration time (24 hours)
- Verify email sending works first

### Session Issues

- Ensure sessions directory is writable
- Check PHP session configuration
- Verify cookies are enabled in browser

---

## 📚 Additional Resources

- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [XAMPP Documentation](https://www.apachefriends.org/)
- [OWASP Security Best Practices](https://owasp.org/)

---

## ✅ Checklist for Deployment

- [ ] Database initialized and verified
- [ ] SMTP credentials configured
- [ ] PHPMailer installed
- [ ] SSL/TLS enabled for SMTP
- [ ] Test registration, email verification, and login
- [ ] Test password recovery
- [ ] Delete or rename `init-db.php`
- [ ] Review security logs
- [ ] Set appropriate file permissions
- [ ] Configure backup strategy

---

## 📞 Support

For issues or questions:

1. Check error logs in `backend/logs/` (if created)
2. Review database structure with `SHOW TABLES; DESCRIBE table_name;`
3. Test API endpoints with curl or Postman
4. Check browser console for frontend errors
5. Review PHP error logs in XAMPP

---

**Last Updated**: March 2026  
**Backend Version**: 1.0.0  
**PHP Version**: 8.2.12
