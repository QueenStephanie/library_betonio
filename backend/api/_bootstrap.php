<?php

/**
 * Shared API bootstrap helpers.
 */

function apiHandleCorsAndMethod($allowedMethod = 'POST')
{
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'null'));
  header('Access-Control-Allow-Methods: ' . $allowedMethod . ', OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Allow-Credentials: true');
  header('Vary: Origin');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
  }

  if ($_SERVER['REQUEST_METHOD'] !== $allowedMethod) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
  }
}

function apiReadJsonInput()
{
  $rawInput = file_get_contents('php://input');
  if ($rawInput === false || trim($rawInput) === '') {
    return null;
  }

  $decoded = json_decode($rawInput, true);
  if (!is_array($decoded)) {
    return null;
  }

  return $decoded;
}

function apiGetDatabaseConnection()
{
  require_once __DIR__ . '/../config/Database.php';

  $database = new DatabaseConnection();
  return $database->connect();
}

function apiEnsureSessionStarted()
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
}
