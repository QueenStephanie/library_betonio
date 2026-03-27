<?php

/**
 * API: Logout Endpoint
 * POST /backend/api/logout.php
 * Handles user logout and session destruction
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
  require_once __DIR__ . '/../config/Database.php';
  require_once __DIR__ . '/../classes/Auth.php';

  // Initialize database
  $database = new Database();
  $db = $database->connect();

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
