<?php

/**
 * Email Configuration
 * Configure your SMTP settings for PHPMailer
 */

require_once __DIR__ . '/AppBootstrap.php';
AppBootstrap::loadEnvironment();

$mailUser = trim((string) (AppBootstrap::env('MAIL_USER') ?: (AppBootstrap::env('MAIL_USERNAME') ?: '')));
$mailPassRaw = trim((string) (AppBootstrap::env('MAIL_PASS') ?: (AppBootstrap::env('MAIL_PASSWORD') ?: '')));
$mailPass = $mailPassRaw;
$mailFrom = trim((string) (AppBootstrap::env('MAIL_FROM') ?: ''));
$mailEncryption = strtolower(trim((string) (AppBootstrap::env('MAIL_ENCRYPTION') ?: 'tls')));
$mailAuth = filter_var(AppBootstrap::env('MAIL_AUTH', true), FILTER_VALIDATE_BOOL);

if ($mailFrom === '' && $mailUser !== '') {
  $mailFrom = $mailUser;
}

return [
  // SMTP Configuration
  'smtp' => [
    'host' => AppBootstrap::env('MAIL_HOST', 'smtp.gmail.com'),
    'port' => (int) (AppBootstrap::env('MAIL_PORT', 587)),
    'auth' => $mailAuth,
    'username' => $mailUser,
    'password' => $mailPass,
    'from_email' => $mailFrom ?: 'noreply@example.com',
    'from_name' => AppBootstrap::env('MAIL_FROM_NAME', 'QueenLib'),
    'encryption' => in_array($mailEncryption, ['ssl', 'tls', 'none', ''], true) ? $mailEncryption : 'tls'
  ],

  // Alternative: Local Sendmail Configuration
  // Uncomment to use instead of SMTP
  // 'use_sendmail' => true,

  // Security
  'enable_ssl_verification' => filter_var(AppBootstrap::env('MAIL_VERIFY_SSL', true), FILTER_VALIDATE_BOOL)
];
