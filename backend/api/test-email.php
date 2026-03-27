<?php

/**
 * API: Test Email Endpoint
 * GET /backend/api/test-email.php
 * Sends a test email to verify PHPMailer configuration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

try {
  // Load required files
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../mail/MailHandler.php';
  require_once __DIR__ . '/../vendor/autoload.php';

  $recipient_email = isset($_GET['email']) ? trim($_GET['email']) : 'sordillamike1@gmail.com';

  // Validate email
  if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email address']);
    exit();
  }

  // Initialize mail handler
  $mail_handler = new MailHandler();

  // Test sending email
  $result = $mail_handler->sendTestEmail($recipient_email, 'Test User');

  if ($result['success']) {
    http_response_code(200);
    echo json_encode([
      'success' => true,
      'message' => 'Test email sent successfully!',
      'recipient' => $recipient_email,
      'details' => 'Check your inbox for the test email'
    ]);
  } else {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'error' => $result['error'],
      'recipient' => $recipient_email
    ]);
  }
} catch (Exception $e) {
  error_log("Test email error: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'error' => 'Failed to send test email: ' . $e->getMessage()
  ]);
}
