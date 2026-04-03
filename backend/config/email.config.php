<?php

/**
 * Email Configuration
 * Configure your SMTP settings for PHPMailer
 */

require_once __DIR__ . '/AppBootstrap.php';
AppBootstrap::loadEnvironment();

$mailUser = trim((string) (AppBootstrap::env('MAIL_USER') ?: (AppBootstrap::env('MAIL_USERNAME') ?: '')));
$mailPassRaw = trim((string) (AppBootstrap::env('MAIL_PASS') ?: (AppBootstrap::env('MAIL_PASSWORD') ?: '')));
$mailPass = str_replace(' ', '', $mailPassRaw);
$mailFrom = trim((string) (AppBootstrap::env('MAIL_FROM') ?: ''));
$mailEncryption = strtolower(trim((string) (AppBootstrap::env('MAIL_ENCRYPTION') ?: 'tls')));

if ($mailFrom === '' && $mailUser !== '') {
  $mailFrom = $mailUser;
}

return [
  // SMTP Configuration
  'smtp' => [
    'host' => AppBootstrap::env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => (int) (AppBootstrap::env('MAIL_PORT', 587)),
    'username' => $mailUser,
    'password' => $mailPass,
    'from_email' => $mailFrom ?: 'noreply@example.com',
    'from_name' => AppBootstrap::env('MAIL_FROM_NAME', 'QueenLib'),
    'encryption' => in_array($mailEncryption, ['ssl', 'tls'], true) ? $mailEncryption : 'tls'
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
  'enable_ssl_verification' => filter_var(AppBootstrap::env('MAIL_VERIFY_SSL', true), FILTER_VALIDATE_BOOL)
];
