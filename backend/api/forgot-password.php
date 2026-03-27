<?php

/**
 * API: Forgot Password Endpoint
 * POST /backend/api/forgot-password.php
 * Initiates password reset by sending reset link to email
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
  require_once __DIR__ . '/../mail/MailHandler.php';

  // Get POST data
  $input = json_decode(file_get_contents('php://input'), true);

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
  $database = new Database();
  $db = $database->connect();

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
