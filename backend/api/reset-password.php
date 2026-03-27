<?php

/**
 * API: Reset Password Endpoint
 * POST /backend/api/reset-password.php
 * Resets user password using reset token
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit();
}

try {
  // Load required files
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/PasswordRecovery.php';

  // Get POST data
  $input = json_decode(file_get_contents('php://input'), true);

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
  $database = new Database();
  $db = $database->connect();

  // Reset password
  $password_recovery = new PasswordRecovery($db);
  $result = $password_recovery->resetPassword($email, $reset_token, $new_password, $confirm_password);

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
