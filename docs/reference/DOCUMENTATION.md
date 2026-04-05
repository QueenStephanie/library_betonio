# QueenLib - Library Management System

## Complete Documentation

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Installation & Setup](#installation--setup)
4. [Database Schema](#database-schema)
5. [Authentication System](#authentication-system)
6. [API Endpoints](#api-endpoints)
7. [File Structure](#file-structure)
8. [Usage Guide](#usage-guide)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

**QueenLib** is a PHP-based library management system with:

- ✓ User registration with email verification
- ✓ Session-based authentication
- ✓ OTP (One-Time Password) email verification
- ✓ Password reset functionality
- ✓ Secure password hashing (Bcrypt)
- ✓ Dashboard with account management
- ✓ PHPMailer integration for email delivery

**Technology Stack:**

- Backend: PHP 8.0+
- Database: MySQL 5.7+
- Email: PHPMailer 7.0 via Gmail SMTP
- Frontend: HTML5, CSS3, JavaScript
- Security: Bcrypt, HttpOnly Cookies, SameSite=Strict

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    Frontend (Browser)                    │
│                                                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  Login Page  │  │Register Page │  │ Verify Page  │   │
│  │ login.php    │  │register.php  │  │verify-otp.php│   │
│  └──────────────┘  └──────────────┘  └──────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │           Dashboard (index.php)                   │   │
│  │       Sidebar + Main Content Area                 │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                           ↓ HTTP/HTTPS
┌─────────────────────────────────────────────────────────┐
│              Backend Services (PHP)                      │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │          Authentication System                   │   │
│  │  includes/                                       │   │
│  │  ├── auth.php (AuthManager class)               │   │
│  │  ├── config.php (Database connection)           │   │
│  │  └── functions.php (Helper functions)           │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │          Email Services                          │   │
│  │  ├── sendOTPEmail()                             │   │
│  │  └── sendPasswordResetEmail()                   │   │
│  └──────────────────────────────────────────────────┘   │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │          Security Features                       │   │
│  │  ├── Bcrypt password hashing                    │   │
│  │  ├── OTP verification (6 digits, 10 min)       │   │
│  │  ├── Session timeout (1 hour)                  │   │
│  │  └── Rate limiting on attempts                 │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                           ↓ PDO
┌─────────────────────────────────────────────────────────┐
│              MySQL Database                              │
│                                                           │
│  ┌──────────────────────────────────────────────────┐   │
│  │  users                │  otp_codes               │   │
│  │  ├── id              │  ├── id                  │   │
│  │  ├── email           │  ├── user_id (FK)        │   │
│  │  ├── password_hash   │  ├── otp_code            │   │
│  │  ├── is_verified     │  ├── expires_at          │   │
│  │  ├── created_at      │  ├── is_used             │   │
│  │  └── ...             │  └── purpose             │   │
│  └────────────────────────────────────────────────────│   │
│                                                           │
│  verification_attempts  │  login_history                 │
│  (Rate limiting)        │  (Security audit)              │
└─────────────────────────────────────────────────────────┘
                           ↓ SMTP
┌─────────────────────────────────────────────────────────┐
│              Email Service (Gmail SMTP)                  │
│  smtp.gmail.com:587 (TLS)                               │
└─────────────────────────────────────────────────────────┘
```

---

## ⚙️ Installation & Setup

### Prerequisites

- PHP 8.0+
- MySQL 5.7+
- XAMPP (or similar local development environment)
- Composer (for PHPMailer)
- Gmail account with App Password enabled

### Step 1: Clone/Extract Files

```bash
cd /path/to/xampp/htdocs/
# Extract all files to library_betonio folder
```

### Step 2: Install Dependencies

```bash
cd library_betonio/backend
composer install
```

### Step 3: Configure Database

Edit `/backend/config/Database.php`:

```php
const DB_HOST = 'localhost:3307';  // Your MySQL port
const DB_NAME = 'library_betonio';
const DB_USER = 'root';
const DB_PASS = '';
```

### Step 4: Initialize Database

```bash
php /backend/setup-db.php
```

### Step 5: Configure Email

Edit `/backend/config/email.config.php`:

```php
'username' => 'your-email@gmail.com',
'password' => 'your-app-password',  // NOT your regular password
'from_email' => 'your-email@gmail.com',
'from_name' => 'QueenLib'
```

### Step 6: Access Application

- **Frontend:** `http://localhost/library_betonio/`
- **Login:** `http://localhost/library_betonio/login.php`
- **Register:** `http://localhost/library_betonio/register.php`

---

## 📊 Database Schema

### Users Table

```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_verified BOOLEAN DEFAULT FALSE,
  verification_token VARCHAR(255) NULL,
  verification_token_expires DATETIME NULL,
  reset_token VARCHAR(255) NULL,
  reset_token_expires DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  is_active BOOLEAN DEFAULT TRUE
);
```

### OTP Codes Table

```sql
CREATE TABLE otp_codes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  otp_code VARCHAR(6) NOT NULL,
  purpose ENUM('email_verification', 'password_reset') DEFAULT 'email_verification',
  is_used BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Verification Attempts Table (Rate Limiting)

```sql
CREATE TABLE verification_attempts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  attempt_type ENUM('otp_request', 'otp_verify', 'password_reset') NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  is_successful BOOLEAN DEFAULT FALSE,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Login History Table (Audit Trail)

```sql
CREATE TABLE login_history (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(500),
  login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  logout_time DATETIME NULL,
  is_successful BOOLEAN DEFAULT TRUE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 🔐 Authentication System

### Registration Flow

```
1. User fills registration form (first name, last name, email, password)
   ↓
2. Validation:
   - All fields required
   - Password >= 8 characters
   - Password confirmation matches
   - Valid email format
   - Email not already registered
   ↓
3. Password hashed with Bcrypt (cost 12)
   ↓
4. User inserted into database with is_verified = 0
   ↓
5. OTP generated (6 random digits)
   ↓
6. OTP stored with 10-minute expiry (DATE_ADD(NOW(), INTERVAL 10 MINUTE))
   ↓
7. OTP sent via email with verification link
   ↓
8. User redirected to verify-otp.php with email parameter
```

### Verification Flow

```
1. User receives email with:
   - "Verify Email Now" button (clickable link)
   - 6-digit OTP code
   - Plain text backup link
   ↓
2. User clicks button OR manually enters code
   ↓
3. Code validated:
   - Not expired (expires_at > NOW())
   - Not already used (is_used = 0)
   - Matches stored OTP
   ↓
4. Database updates:
   - OTP marked as used (is_used = 1)
   - User marked as verified (is_verified = 1)
   ↓
5. User redirected to login page
```

### Login Flow

```
1. User enters email and password
   ↓
2. Email validation:
   - User exists in database
   ↓
3. Password verification:
   - Bcrypt password_verify() check
   ↓
4. Verification status check:
   - If is_verified = 0 → Redirect to verify-otp.php
   - If is_verified = 1 → Continue to step 5
   ↓
5. Session established:
   - $_SESSION['user_id'] = $user_id
   - $_SESSION['user_name'] = $first_name $last_name
   - $_SESSION['user_email'] = $email
   - $_SESSION['login_time'] = time()
   ↓
6. Login recorded in login_history table
   ↓
7. User redirected to dashboard (index.php)
```

### Password Reset Flow

```
1. User clicks "Forgot Password" on login page
   ↓
2. User enters email address
   ↓
3. If email exists:
   - Generate reset token (32 random bytes, hex encoded)
   - Token stored with 1-hour expiry
   - Email sent with reset link
   - User sees "Check your email" message
   ↓
4. User clicks link in email
   ↓
5. Token validated:
   - Not expired
   - Matches stored token
   ↓
6. User enters new password (min 8 characters)
   ↓
7. Password updated with new Bcrypt hash
   ↓
8. Reset token cleared
   ↓
9. User redirected to login page
```

---

## 📡 API Endpoints

### Authentication Endpoints (PHP files in root)

#### Register

- **File:** `register.php`
- **Method:** POST
- **Parameters:**
  - `first_name` (string, required)
  - `last_name` (string, required)
  - `email` (string, required, valid email)
  - `password` (string, required, min 8 chars)
  - `password_confirm` (string, required, must match password)
- **Response:** Redirects to verify-otp.php with email parameter

#### Login

- **File:** `login.php`
- **Method:** POST
- **Parameters:**
  - `email` (string, required)
  - `password` (string, required)
- **Response:**
  - Success → Redirects to `index.php` (dashboard)
  - Unverified → Redirects to `verify-otp.php?email=...`
  - Error → Shows error message on login page

#### Verify OTP

- **File:** `verify-otp.php`
- **Method:** POST
- **Parameters:**
  - `email` (string, hidden input)
  - `otp` (string, 6 digits)
- **Response:**
  - Success → Redirects to `login.php` with flash message
  - Error → Shows error message on verify page

#### Forgot Password

- **File:** `forgot-password.php`
- **Method:** POST
- **Parameters:**
  - `email` (string, required)
- **Response:**
  - If user exists → Email sent with reset link
  - If user not found → Generic message (security)

#### Reset Password

- **File:** `reset-password.php`
- **Method:** POST
- **Parameters:**
  - `token` (string, from URL)
  - `password` (string, required, min 8 chars)
  - `password_confirm` (string, required, must match)
- **Response:**
  - Success → Redirects to login.php
  - Error → Shows error message with option to restart

#### Logout

- **File:** `logout.php`
- **Method:** GET/POST
- **Response:** Destroys session, redirects to index.php

### Backend API Endpoints (in backend/api/)

These are alternative endpoints that return JSON:

- `backend/api/register.php` - Registration
- `backend/api/login.php` - Login
- `backend/api/logout.php` - Logout
- `backend/api/verify-otp.php` - OTP verification
- `backend/api/request-otp.php` - Request new OTP
- `backend/api/resend-otp.php` - Resend OTP email
- `backend/api/forgot-password.php` - Initiate password reset
- `backend/api/verify-reset-token.php` - Verify reset token
- `backend/api/reset-password.php` - Reset password

---

## 📁 File Structure

```
library_betonio/
├── Core Files (Root)
│   ├── index.php              ✓ Main dashboard
│   ├── login.php              ✓ Login page
│   ├── register.php           ✓ Registration page
│   ├── verify-otp.php         ✓ OTP verification page
│   ├── forgot-password.php    ✓ Password reset request
│   ├── reset-password.php     ✓ Password reset form
│   ├── logout.php             ✓ Logout handler
│   ├── account.php            ✓ Account management page
│   └── .htaccess              ✓ URL rewriting rules
│
├── includes/ (Backend Logic)
│   ├── auth.php               ✓ AuthManager class
│   ├── config.php             ✓ Database connection & session config
│   └── functions.php          ✓ Helper functions + email sending
│
├── backend/
│   ├── config/
│   │   ├── Database.php       ✓ Database credentials
│   │   ├── email.config.php   ✓ SMTP configuration
│   │   ├── init-db.php        ✓ Database initialization
│   │   └── schema.sql         ✓ Database tables
│   │
│   ├── classes/
│   │   ├── Auth.php           ✗ (Use includes/auth.php instead)
│   │   └── PasswordRecovery.php
│   │
│   ├── api/
│   │   ├── register.php       ✓ JSON API
│   │   ├── login.php          ✓ JSON API
│   │   ├── logout.php         ✓ JSON API
│   │   ├── forgot-password.php ✓ JSON API
│   │   ├── reset-password.php ✓ JSON API
│   │   └── verify-reset-token.php ✓ JSON API
│   │
│   ├── mail/
│   │   └── MailHandler.php    ✓ Email sending utilities
│   │
│   ├── vendor/
│   │   └── phpmailer/         ✓ PHPMailer library (via Composer)
│   │
│   ├── composer.json          ✓ Dependency file
│   ├── composer.lock          ✓ Dependency lock file
│   └── README.md              ✓ Backend documentation
│
├── public/ (Frontend Assets)
│   ├── css/
│   │   ├── main.css           ✓ General styling
│   │   ├── auth.css           ✓ Login/register styling
│   │   └── dashboard.css      ✓ Dashboard layout
│   │
│   └── js/
│       ├── main.js            ✓ Main scripts
│       ├── auth.js            ✓ Authentication scripts
│       └── dashboard.js       ✓ Dashboard functionality
│
├── pages/ (Old Frontend - Can be removed)
│   ├── auth/                  ✗ Old HTML versions
│   └── dashboard/             ✗ Old HTML versions
│
├── config/
│   └── api.config.js          ✓ API configuration
│
├── README.md                  ✓ Project overview
└── DOCUMENTATION.md           ✓ This file
```

**Legend:** ✓ = Active/Required | ✗ = Legacy/Can Remove

---

## 🚀 Usage Guide

### For End Users

#### Registration

1. Go to `http://localhost/library_betonio/`
2. Click "Get Started" button
3. Fill registration form:
   - First Name: Your first name
   - Last Name: Your last name
   - Email: Your email address
   - Password: At least 8 characters
   - Confirm Password: Same as above
4. Click "Create Account"
5. You'll receive an email with verification code
6. Click "Verify Email Now" button in email
7. Email field will auto-populate
8. Enter the 6-digit code
9. Click "Verify Email"

#### Login

1. Go to `http://localhost/library_betonio/login.php`
2. Enter your email and password
3. Click "Log In"
4. You'll see your dashboard with account information

#### Forgot Password

1. On login page, click "Forgot your password?"
2. Enter your email address
3. Check your email for password reset link
4. Click the link
5. Enter your new password (min 8 characters)
6. Click "Reset Password"
7. Login with new password

#### Dashboard

- View account information
- See verification status
- Member since date
- Quick access to settings
- Logout button

### For Developers

#### Adding New Authentication Features

1. Update database schema in `/backend/config/schema.sql`
2. Create PHP functions in `/includes/functions.php`
3. Add methods to `AuthManager` class in `/includes/auth.php`
4. Create corresponding Page (`.php` file) or API endpoint

#### Email Template Customization

Edit email templates in `/includes/functions.php`:

- `sendOTPEmail()` - OTP verification emails
- `sendPasswordResetEmail()` - Password reset emails

#### Styling

- Dashboard layout: `/public/css/dashboard.css`
- Authentication pages: `/public/css/auth.css`
- General styling: `/public/css/main.css`

#### JavaScript Functionality

- Auth page interactions: `/public/js/auth.js`
- Dashboard features: `/public/js/dashboard.js`
- Main scripts: `/public/js/main.js`

---

## 🐛 Troubleshooting

### Registration Issues

**Issue:** "Email already registered"

- **Cause:** Email exists in database
- **Solution:** Use different email or contact admin to reset account

**Issue:** "Password must be at least 8 characters"

- **Cause:** Password too short
- **Solution:** Use longer password (8+ characters)

**Issue:** "All fields are required"

- **Cause:** Missed a form field
- **Solution:** Fill all fields and resubmit

### Email Verification Issues

**Issue:** "No email received"

- **Cause:** Email not sent or in spam folder
- **Solution:**
  1. Check spam/promotions folder
  2. Check email configuration in `/backend/config/email.config.php`
  3. Verify Gmail app password is set correctly
  4. Check PHP error logs

**Issue:** "Invalid or expired OTP"

- **Cause:** Code is wrong or timed out
- **Solution:**
  - Copy code exactly (no spaces)
  - Code valid for 10 minutes
  - Register again for new code

**Issue:** "Verification code already used"

- **Cause:** Code used previously
- **Solution:** Request new verification code

### Login Issues

**Issue:** "Invalid email or password"

- **Cause:** Wrong credentials or user doesn't exist
- **Solution:** Double-check email and password

**Issue:** "Please verify your email first"

- **Cause:** Account not verified yet
- **Solution:** Click verification link in email and enter OTP

**Issue:** "Session expired"

- **Cause:** Logged in for more than 1 hour
- **Solution:** Login again

### Email Configuration Issues

**Issue:** PHPMailer error "SMTP connect failed"

- **Cause:** SMTP server unreachable
- **Solution:**
  1. Check `email.config.php` settings
  2. Verify Gmail app password (not regular password)
  3. Enable "Less secure apps" if needed
  4. Check firewall/antivirus blocking

**Issue:** "SMTP Authentication failed"

- **Cause:** Wrong credentials
- **Solution:**
  1. Verify Gmail address in config
  2. Verify app password (16 characters)
  3. Ensure 2FA enabled on Gmail
  4. Generate new app password

**Issue:** "Error reading mail response: Unable to send message"

- **Cause:** Email configuration incomplete
- **Solution:**
  1. Check `from_email` is set
  2. Check `from_name` is set
  3. Verify recipient email is valid
  4. Enable IMAP in Gmail settings

### Database Issues

**Issue:** "SQLSTATE[HY000]: General error: 2002"

- **Cause:** MySQL server not running or wrong host
- **Solution:**
  1. Start XAMPP MySQL
  2. Check Database.php host/port
  3. Verify MySQL is listening on correct port

**Issue:** "SQLSTATE[28000]: Invalid authorization"

- **Cause:** Wrong database credentials
- **Solution:**
  1. Update Database.php with correct credentials
  2. Verify MySQL user exists
  3. Check MySQL user permissions

**Issue:** "Table doesn't exist"

- **Cause:** Database not initialized
- **Solution:**
  1. Run: `php /backend/setup-db.php`
  2. Or manually import `/backend/config/schema.sql`

---

## 📞 Support

### Common Commands

**Initialize Database:**

```bash
php /backend/setup-db.php
```

**Install/Update Dependencies:**

```bash
cd /backend
composer install
composer update
```

**Test Email Configuration:**

```bash
# Create a test file test-email.php:
<?php
require 'includes/config.php';
require 'includes/functions.php';
sendOTPEmail('your-email@gmail.com', '123456', 'Test');
?>

# Then run: php test-email.php
```

**Check PHP Configuration:**

```bash
php -i | grep -A 10 "mail"
```

---

## ✅ Security Best Practices

1. **Environment Variables:** Store sensitive data in .env file
2. **HTTPS:** Always use HTTPS in production
3. **Headers:** Add security headers in .htaccess
4. **Input Validation:** Always validate and sanitize user input
5. **SQL Injection:** Use prepared statements (already implemented)
6. **Password Security:**
   - Minimum 8 characters required
   - Bcrypt hashing with cost 12
   - Never store plaintext passwords
7. **Session Security:**
   - HttpOnly cookies
   - SameSite=Strict
   - 1-hour timeout
8. **Email Security:**
   - Use app-specific passwords
   - Enable 2FA on Gmail
   - TLS encryption for SMTP
9. **Rate Limiting:** Implemented for OTP and password reset attempts
10. **Audit Trail:** Login history recorded for security analysis

---

## 📝 License

This project is provided as-is for educational purposes.

---

**Last Updated:** March 27, 2026
**Version:** 1.0.0
