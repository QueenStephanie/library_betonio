<?php

/**
 * API: Logout Endpoint
 * POST /backend/api/logout.php
 * Handles user logout and session destruction
 */

require_once __DIR__ . '/_bootstrap.php';

apiHandleCorsAndMethod('POST');

try {
  require_once __DIR__ . '/../classes/Auth.php';

  apiEnsureSessionStarted();

  // Initialize database
  $db = apiGetDatabaseConnection();

  // Logout user
  $auth = new Auth($db);
  $logout_result = $auth->logout();

  http_response_code(200);
  echo json_encode($logout_result);
} catch (Exception $e) {
  error_log("Logout error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Logout failed']);
}
