<?php

/**
 * API: Forgot Password Endpoint
 * POST /backend/api/forgot-password.php
 * Initiates password reset by sending reset link to email
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  // Load required files
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/PasswordRecovery.php';
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

  // Request password reset
  $password_recovery = new PasswordRecovery($db, $mail_handler);
  $result = $password_recovery->requestPasswordReset($email);

  // Always return success message for security (don't reveal if email exists)
  http_response_code(200);
  echo json_encode($result);
} catch (Exception $e) {
  error_log("Forgot password error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Password reset request failed']);
}
