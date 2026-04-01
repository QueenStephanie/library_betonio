<?php

/**
 * API: User Registration Endpoint
 * POST /backend/api/register.php
 * Handles user registration with email verification
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  // Load required files
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/Auth.php';
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
  $first_name = isset($input['first_name']) ? trim($input['first_name']) : '';
  $last_name = isset($input['last_name']) ? trim($input['last_name']) : '';
  $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
  $password = isset($input['password']) ? $input['password'] : '';
  $password_confirm = isset($input['password_confirm']) ? $input['password_confirm'] : '';

  // Initialize database
  $db = apiGetDatabaseConnection();

  // Register user
  $auth = new Auth($db);
  $register_result = $auth->register($first_name, $last_name, $email, $password, $password_confirm);

  if (!$register_result['success']) {
    http_response_code(400);
    echo json_encode($register_result);
    exit();
  }

  // Send verification email with token
  $mail_handler = new MailHandler($db);
  $verification_token = $register_result['verification_token'] ?? '';
  $user_name = $first_name ?? 'User';

  $send_result = $mail_handler->sendVerificationEmail($email, $user_name, $verification_token);

  if (!$send_result['success']) {
    error_log("Failed to send verification email to $email: " . $send_result['error']);
    // Don't fail registration, just notify user
    $register_result['email_message'] = 'Registration successful but verification email could not be sent. Please check your email or contact support.';
  } else {
    $register_result['email_message'] = 'Verification email sent to your email address';
  }

  http_response_code(201);
  echo json_encode($register_result);
} catch (Exception $e) {
  error_log("Registration error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Registration failed']);
}
