<?php

/**
 * Email Configuration
 * Configure your SMTP settings for PHPMailer
 */

return [
  // SMTP Configuration
  'smtp' => [
    'host' => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
    'port' => (int) (getenv('MAIL_PORT') ?: 587),
    'username' => getenv('MAIL_USER') ?: (getenv('MAIL_USERNAME') ?: ''),
    'password' => getenv('MAIL_PASS') ?: (getenv('MAIL_PASSWORD') ?: ''),
    'from_email' => getenv('MAIL_FROM') ?: 'noreply@example.com',
    'from_name' => getenv('MAIL_FROM_NAME') ?: 'QueenLib'
  ],

  // Alternative: Local Sendmail Configuration
  // Uncomment to use instead of SMTP
  // 'use_sendmail' => true,

  // Email Templates Settings
  'otp_validity' => 600,      // OTP valid for 10 minutes (600 seconds)
  'max_otp_attempts' => 3,    // Maximum OTP verification attempts
  'otp_resend_delay' => 60,   // Wait 1 minute before resend

  // Rate Limiting
  'rate_limit' => [
    'otp_requests' => 3,     // 3 OTP requests per hour
    'otp_verifications' => 5 // 5 verification attempts per hour
  ],

  // Security
  'enable_ssl_verification' => filter_var(getenv('MAIL_VERIFY_SSL') ?: true, FILTER_VALIDATE_BOOL)
];
