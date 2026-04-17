<?php

/**
 * API: Reset Password Endpoint
 * POST /backend/api/reset-password.php
 * Resets user password using reset token
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
  $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
  $reset_token = isset($input['reset_token']) ? trim($input['reset_token']) : '';
  $new_password = isset($input['new_password']) ? $input['new_password'] : '';
  $confirm_password = isset($input['confirm_password']) ? $input['confirm_password'] : '';

  // Validate input
  if (empty($email) || empty($reset_token) || empty($new_password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit();
  }

  // Initialize database
  $db = apiGetDatabaseConnection();

  $authServiceClass = 'AuthService';
  $authService = new $authServiceClass($db);
  $result = $authService->resetPassword($email, $reset_token, $new_password, $confirm_password);

  if (!$result['success']) {
    http_response_code(400);
  } else {
    http_response_code(200);
  }

  echo json_encode($result);
} catch (Exception $e) {
  error_log("Reset password error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Password reset failed']);
}
