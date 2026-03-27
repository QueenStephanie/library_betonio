<?php

/**
 * Email Configuration
 * Configure your SMTP settings for PHPMailer
 */

return [
  // SMTP Configuration
  'smtp' => [
    'host' => 'smtp.gmail.com',           // Change to your email provider
    'port' => 587,                        // 587 for TLS, 465 for SSL
    'username' => 'sordillamike1@gmail.com', // Your email address
    'password' => 'ibps fndh cbvv iriv',    // App-specific password
    'from_email' => 'sordillamike1@gmail.com',
    'from_name' => 'Library Betonio'
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
  'enable_ssl_verification' => true
];
