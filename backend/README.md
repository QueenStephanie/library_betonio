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
    - Tables: users, verification_attempts, login_history

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

### Email Verification Endpoints

#### 4. **Request OTP**

```
POST /backend/api/request-otp.php
Content-Type: application/json

Body:
{
    "email": "john@example.com"
}

Response (200):
{
    "success": true,
    "message": "OTP sent to your email",
    "otp_validity_seconds": 600
}
```

#### 5. **Verify OTP**

```
POST /backend/api/verify-otp.php
Content-Type: application/json

Body:
{
    "email": "john@example.com",
    "otp_code": "123456"
}

Response (200):
{
    "success": true,
    "message": "Email verified successfully",
    "user_id": 1
}
```

#### 6. **Resend OTP**

```
POST /backend/api/resend-otp.php
Content-Type: application/json

Body:
{
    "email": "john@example.com"
}

Response (200):
{
    "success": true,
    "message": "OTP resent successfully"
}
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

### 2. **OTP Security**

- 6-digit random codes
- 10-minute expiration (configurable)
- Rate limiting: 3 requests per hour
- Auto-invalidation of old OTPs

### 3. **Rate Limiting**

- OTP requests: 3 per hour
- OTP verification: 5 attempts per hour
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

### 6. **Email Security**

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

```bash
# Request OTP
curl -X POST http://localhost/library_betonio/backend/api/request-otp.php \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com"}'

# Verify OTP (check email for code)
curl -X POST http://localhost/library_betonio/backend/api/verify-otp.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp_code": "123456"
  }'
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

1. Navigate to: `http://localhost/library_betonio/register.html`
2. Fill in registration form
3. Submit - should send OTP email
4. Go to: `http://localhost/library_betonio/pages/auth/verify-email.html?email=test@example.com`
5. Enter OTP from email
6. Should redirect to login page

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

### OTP Not Working

- Clear browser cache
- Verify database tables exist
- Check OTP expiration time
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
- [ ] Test registration, OTP, and login
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
