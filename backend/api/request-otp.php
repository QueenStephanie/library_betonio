<?php

/**
 * API: Request OTP Endpoint
 * POST /backend/api/request-otp.php
 * Generates and sends OTP for email verification
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
  require_once __DIR__ . '/../classes/EmailVerification.php';
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
