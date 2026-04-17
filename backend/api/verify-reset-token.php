<?php

/**
 * API: Verify Reset Token Endpoint
 * POST /backend/api/verify-reset-token.php
 * Verifies if a password reset token is valid
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

  // Validate input
  if (empty($email) || empty($reset_token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and reset token are required']);
    exit();
  }

  // Initialize database
  $db = apiGetDatabaseConnection();

  $authServiceClass = 'AuthService';
  $authService = new $authServiceClass($db);
  $result = $authService->verifyResetToken($email, $reset_token);

  if (!$result['success']) {
    http_response_code(400);
  } else {
    http_response_code(200);
  }

  echo json_encode($result);
} catch (Exception $e) {
  error_log("Verify reset token error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Token verification failed']);
}
