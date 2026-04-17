<?php

/**
 * API: User Registration Endpoint
 * POST /backend/api/register.php
 * Handles user registration with email verification
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  require_once __DIR__ . '/../../includes/services/AuthService.php';

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

  $authServiceClass = 'AuthService';
  $authService = new $authServiceClass($db);
  $register_result = $authService->registerBorrower($first_name, $last_name, $email, $password, $password_confirm);

  if (!$register_result['success']) {
    http_response_code(400);
    echo json_encode($register_result);
    exit();
  }

  $register_result['email_message'] = 'Verification email sent to your email address';

  unset($register_result['verification_token']);

  http_response_code(201);
  echo json_encode($register_result);
} catch (Exception $e) {
  error_log("Registration error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Registration failed']);
}
