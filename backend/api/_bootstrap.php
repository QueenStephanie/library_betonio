<?php

/**
 * Shared API bootstrap helpers.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

function apiNormalizeOriginUrl($url)
{
  $parts = parse_url(trim((string)$url));
  if (!is_array($parts)) {
    return null;
  }

  $scheme = strtolower(trim((string)($parts['scheme'] ?? '')));
  $host = strtolower(trim((string)($parts['host'] ?? '')));
  if ($scheme === '' || $host === '' || !in_array($scheme, ['http', 'https'], true)) {
    return null;
  }

  $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
  if ($port <= 0) {
    $port = $scheme === 'https' ? 443 : 80;
  }

  return $scheme . '://' . $host . ':' . $port;
}

function apiBuildAllowedOrigins()
{
  $allowed = [];

  if (defined('APP_URL')) {
    $appOrigin = apiNormalizeOriginUrl((string)constant('APP_URL'));
    if ($appOrigin !== null) {
      $allowed[$appOrigin] = true;
    }
  }

  $hostCandidates = [];

  $httpHost = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
  if ($httpHost !== '') {
    $hostCandidates[] = $httpHost;
  }

  $serverName = trim((string)($_SERVER['SERVER_NAME'] ?? ''));
  if ($serverName !== '') {
    $hostCandidates[] = $serverName;
  }

  $forwardedHost = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
  if ($forwardedHost !== '') {
    $forwardedParts = explode(',', $forwardedHost);
    if (isset($forwardedParts[0])) {
      $hostCandidates[] = trim((string)$forwardedParts[0]);
    }
  }

  $isHttps = false;
  if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $isHttps = true;
  } elseif ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
    $isHttps = true;
  } elseif (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
    $isHttps = true;
  }

  $requestScheme = $isHttps ? 'https' : 'http';

  foreach ($hostCandidates as $hostCandidate) {
    if ($hostCandidate === '') {
      continue;
    }

    $normalized = apiNormalizeOriginUrl($requestScheme . '://' . $hostCandidate);
    if ($normalized !== null) {
      $allowed[$normalized] = true;
    }
  }

  return array_keys($allowed);
}

function apiResolveRequestOriginCandidate()
{
  $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
  if ($origin !== '') {
    return [
      'source' => 'origin',
      'origin' => apiNormalizeOriginUrl($origin),
    ];
  }

  $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
  if ($referer !== '') {
    return [
      'source' => 'referer',
      'origin' => apiNormalizeOriginUrl($referer),
    ];
  }

  return [
    'source' => 'none',
    'origin' => null,
  ];
}

function apiValidateStateChangingRequestOrigin($context = 'api_post')
{
  $originCandidate = apiResolveRequestOriginCandidate();
  $candidateSource = (string)($originCandidate['source'] ?? 'none');
  $candidateOrigin = $originCandidate['origin'] ?? null;

  if ($candidateSource === 'none' || !is_string($candidateOrigin) || $candidateOrigin === '') {
    return [
      'valid' => false,
      'source' => $candidateSource,
      'origin' => null,
      'reason' => 'missing_or_invalid_origin',
      'context' => (string)$context,
    ];
  }

  $allowedOrigins = apiBuildAllowedOrigins();
  $isAllowed = in_array($candidateOrigin, $allowedOrigins, true);

  return [
    'valid' => $isAllowed,
    'source' => $candidateSource,
    'origin' => $candidateOrigin,
    'allowed' => $allowedOrigins,
    'reason' => $isAllowed ? '' : 'origin_mismatch',
    'context' => (string)$context,
  ];
}

function apiEnforceStateChangingRequestOrigin($context = 'api_post')
{
  $originValidator = 'validateStateChangingRequestOrigin';
  if (function_exists($originValidator)) {
    $originCheck = call_user_func($originValidator, $context);
  } else {
    $originCheck = apiValidateStateChangingRequestOrigin($context);
  }
  if (!empty($originCheck['valid'])) {
    return;
  }

  $origin = isset($originCheck['origin']) ? (string)$originCheck['origin'] : 'none';
  error_log('Blocked API request due to origin validation: ' . json_encode($originCheck));
  http_response_code(403);
  echo json_encode([
    'success' => false,
    'error' => 'Security check failed',
    'origin' => $origin,
  ]);
  exit();
}

function apiHandleCorsAndMethod($allowedMethod = 'POST')
{
  $originCheck = apiValidateStateChangingRequestOrigin('api_' . strtolower((string)$allowedMethod));

  header('Content-Type: application/json');
  header('Access-Control-Allow-Methods: ' . $allowedMethod . ', OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Vary: Origin');

  if (!empty($originCheck['valid']) && !empty($originCheck['origin'])) {
    header('Access-Control-Allow-Origin: ' . (string)$originCheck['origin']);
    header('Access-Control-Allow-Credentials: true');
  }

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
  }

  if ($_SERVER['REQUEST_METHOD'] !== $allowedMethod) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
  }

  if (!empty($originCheck['valid'])) {
    return;
  }

  apiEnforceStateChangingRequestOrigin('api_' . strtolower((string)$allowedMethod));
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
