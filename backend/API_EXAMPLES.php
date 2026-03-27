<?php

/**
 * API Test Examples
 * This file contains curl examples for testing all API endpoints
 * Copy and paste into terminal or use with Postman
 */

// ============================================
// 1. REGISTRATION ENDPOINT
// ============================================

/** @route POST /backend/api/register.php
 * @description Register a new user with email
 */
$register = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/register.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "first_name": "John",
        "last_name": "Doe",
        "email": "john@example.com",
        "password": "SecurePass123!",
        "password_confirm": "SecurePass123!"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Registration successful. Please verify your email.',
    'user_id' => 1,
    'email' => 'john@example.com',
    'otp_message' => 'OTP sent to your email',
    'otp_validity_seconds' => 600
  ]
];

// ============================================
// 2. REQUEST OTP ENDPOINT
// ============================================

/** @route POST /backend/api/request-otp.php
 * @description Request OTP code for email verification
 */
$request_otp = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/request-otp.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'OTP sent to your email',
    'user_id' => 1,
    'otp_validity_seconds' => 600
  ]
];

// ============================================
// 3. VERIFY OTP ENDPOINT
// ============================================

/** @route POST /backend/api/verify-otp.php
 * @description Verify OTP code from email
 */
$verify_otp = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/verify-otp.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com",
        "otp_code": "123456"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Email verified successfully',
    'user_id' => 1,
    'email' => 'john@example.com'
  ]
];

// ============================================
// 4. LOGIN ENDPOINT
// ============================================

/** @route POST /backend/api/login.php
 * @description Authenticate user with email and password
 */
$login = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/login.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com",
        "password": "SecurePass123!"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Login successful',
    'user' => [
      'id' => 1,
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john@example.com'
    ]
  ]
];

// ============================================
// 5. LOGOUT ENDPOINT
// ============================================

/** @route POST /backend/api/logout.php
 * @description Logout user and destroy session
 */
$logout = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/logout.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Logout successful'
  ]
];

// ============================================
// 6. FORGOT PASSWORD ENDPOINT
// ============================================

/** @route POST /backend/api/forgot-password.php
 * @description Initiate password reset with token email
 */
$forgot_password = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/forgot-password.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'If email exists, password reset link will be sent'
  ]
];

// ============================================
// 7. VERIFY RESET TOKEN ENDPOINT
// ============================================

/** @route POST /backend/api/verify-reset-token.php
 * @description Verify password reset token from email link
 */
$verify_reset_token = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/verify-reset-token.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com",
        "reset_token": "token_from_email_link"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Reset token is valid',
    'user_id' => 1,
    'email' => 'john@example.com'
  ]
];

// ============================================
// 8. RESET PASSWORD ENDPOINT
// ============================================

/** @route POST /backend/api/reset-password.php
 * @description Reset user password using reset token
 */
$reset_password = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/reset-password.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com",
        "reset_token": "token_from_email_link",
        "new_password": "NewSecurePass456!",
        "confirm_password": "NewSecurePass456!"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'Password reset successfully. You can now login with your new password.',
    'user_id' => 1,
    'redirect' => '/library_betonio/login.html'
  ]
];

// ============================================
// 9. RESEND OTP ENDPOINT
// ============================================

/** @route POST /backend/api/resend-otp.php
 * @description Resend OTP code to email (with rate limiting)
 */
$resend_otp = [
  'url' => 'curl -X POST http://localhost/library_betonio/backend/api/resend-otp.php',
  'headers' => '-H "Content-Type: application/json"',
  'data' => '-d \'{
        "email": "john@example.com"
    }\'',
  'expected_response' => [
    'success' => true,
    'message' => 'OTP sent to your email',
    'user_id' => 1,
    'otp_validity_seconds' => 600
  ]
];

echo "=== Library Betonio - API Test Examples ===\n\n";
echo "For complete curl commands, see the source code of this file.\n";
echo "Or use Postman to import these API specifications.\n\n";
echo "Base URL: http://localhost/library_betonio\n";
echo "Total Endpoints: 9\n";
