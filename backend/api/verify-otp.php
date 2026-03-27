<?php

/**
 * API: Verify OTP Endpoint
 * POST /backend/api/verify-otp.php
 * Verifies OTP and marks email as verified
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

  // Get POST data
  $input = json_decode(file_get_contents('php://input'), true);

  if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit();
  }

  // Extract and sanitize input
  $email = isset($input['email']) ? strtolower(trim($input['email'])) : '';
  $otp_code = isset($input['otp_code']) ? trim($input['otp_code']) : '';

  // Validate input
  if (empty($email) || empty($otp_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email and OTP code are required']);
    exit();
  }

  // Validate OTP format (6 digits)
  if (!preg_match('/^\d{6}$/', $otp_code)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid OTP format']);
    exit();
  }

  // Initialize database
  $database = new Database();
  $db = $database->connect();

  // Verify OTP
  $email_verification = new EmailVerification($db);
  $result = $email_verification->verifyOTP($email, $otp_code);

  if (!$result['success']) {
    http_response_code(400);
  } else {
    http_response_code(200);
  }

  echo json_encode($result);
} catch (Exception $e) {
  error_log("Verify OTP error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'OTP verification failed']);
}
