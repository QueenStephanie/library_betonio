<?php

/**
 * API: User Login Endpoint
 * POST /backend/api/login.php
 * Handles user authentication and session creation
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
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/Auth.php';

  // Get POST data
  $input = json_decode(file_get_contents('php://input'), true);

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
  $database = new Database();
  $db = $database->connect();

  // Authenticate user
  $auth = new Auth($db);
  $login_result = $auth->login($email, $password);

  if (!$login_result['success']) {
    http_response_code(401);
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
