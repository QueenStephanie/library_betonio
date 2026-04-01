<?php

/**
 * API: Request OTP Endpoint
 * POST /backend/api/request-otp.php
 * Generates and sends OTP for email verification
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  // Load required files
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/EmailVerification.php';
  require_once __DIR__ . '/../mail/MailHandler.php';

  // Get POST data
  $input = apiReadJsonInput();

  if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
  }

  // Extract and sanitize input
  $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';

  // Validate input
  if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit();
  }

  // Initialize database
  $db = apiGetDatabaseConnection();

  // Initialize mail handler
  $mail_handler = new MailHandler($db);

  // Request OTP
  $email_verification = new EmailVerification($db, $mail_handler);
  $result = $email_verification->requestOTP($email);

  if (!$result['success']) {
    http_response_code(400);
  } else {
    http_response_code(200);
  }

  echo json_encode($result);
} catch (Exception $e) {
  error_log("Request OTP error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Failed to request OTP']);
}
