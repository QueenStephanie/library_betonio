<?php

/**
 * API: User Login Endpoint
 * POST /backend/api/login.php
 * Handles user authentication and session creation
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  // Load required files
  require_once __DIR__ . '/../classes/Auth.php';

  apiEnsureSessionStarted();

  // Get POST data
  $input = apiReadJsonInput();

  if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
  }

  // Extract and sanitize input
  $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
  $password = isset($input['password']) ? $input['password'] : '';

  // Validate input
  if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit();
  }

  // Initialize database
  $db = apiGetDatabaseConnection();

  // Authenticate user
  $auth = new Auth($db);
  $login_result = $auth->login($email, $password);

  if (!$login_result['success']) {
    http_response_code(401);

    // Check if it's specifically a verification issue
    if (isset($login_result['requires_verification']) && $login_result['requires_verification']) {
      http_response_code(403);
      $login_result['requires_verification'] = true;
    }
  } else {
    http_response_code(200);
  }

  // Remove sensitive data from response
  if ($login_result['success']) {
    unset($login_result['auth_token']); // Don't expose token in response for now
  }

  echo json_encode($login_result);
} catch (Exception $e) {
  error_log("Login error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Login failed']);
}
