<?php

/**
 * SQL Security Helpers
 * Safely quote identifier names (columns, tables) for safe interpolation
 * @param PDO $db
 * @param string $identifier
 * @return string Quoted safe identifier
 */
function quoteIdentifier(PDO $db, string $identifier): string {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('Invalid SQL identifier: ' . $identifier);
    }
    return '`' . str_replace('`', '``', $identifier) . '`';
}

/**
 * Helper Functions
 * Utility functions for the application
 */

/**
 * Redirect to a page
 */
function redirect($path, $status = 302)
{
    // Prevent browser from caching the redirect itself
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

  if (strpos($path, 'http://') !== 0 && strpos($path, 'https://') !== 0) {
    if ($path === '') {
      $path = '/';
    }

    if ($path[0] !== '/') {
      $path = '/' . $path;
    }

    $basePath = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
    if ($basePath !== '' && strpos($path, $basePath . '/') !== 0 && $path !== $basePath) {
      $path = $basePath . $path;
    }
  }

  header("Location: $path", true, $status);
  exit();
}

/**
 * Build an application path with optional query string
 */
function appPath($path = '', array $query = [])
{
  $basePath = defined('APP_BASE_PATH') ? APP_BASE_PATH : '';
  $normalizedPath = '/' . ltrim($path, '/');

  if ($normalizedPath === '/') {
    $fullPath = $basePath === '' ? '/' : $basePath . '/';
  } else {
    $fullPath = $basePath . $normalizedPath;
  }

  if (!empty($query)) {
    $queryString = http_build_query($query);
    if ($queryString !== '') {
      $fullPath .= '?' . $queryString;
    }
  }

  return $fullPath;
}

/**
 * Build an absolute application URL
 */
function appUrl($path = '', array $query = [])
{
  $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
  return $appUrl . appPath($path, $query);
}

/**
 * Check if user is logged in, redirect if not
 */
function requireLogin()
{
  if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
  }

  enforceAuthenticatedSessionTimeout('login.php');
}

/**
 * Resolve authenticated landing page based on active session role.
 */
function resolveAuthenticatedHomePath()
{
  $role = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
  $isSuperadmin = !empty($_SESSION['is_superadmin']);

  if ($role === 'admin' || $isSuperadmin) {
    return 'admin-dashboard.php#about-me';
  }

  if ($role === 'librarian') {
    return 'librarian-dashboard.php';
  }

  return 'index.php';
}

/**
 * Check whether an authenticated user has admin role.
 */
function isAdminAuthenticated()
{
  if (!isset($_SESSION['user_id'])) {
    return false;
  }

  $role = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
  if (in_array($role, ['admin', 'librarian'], true)) {
    return true;
  }

  return !empty($_SESSION['is_superadmin']);
}

/**
 * Resolve configured superadmin username safely.
 */
function getConfiguredSuperadminUsername()
{
  if (defined('SUPERADMIN_USERNAME')) {
    return strtolower(trim((string)SUPERADMIN_USERNAME));
  }

  if (defined('ADMIN_USERNAME')) {
    return strtolower(trim((string)ADMIN_USERNAME));
  }

  return '';
}

/**
 * Check whether an identity matches configured superadmin username.
 */
function isConfiguredSuperadminIdentity($identity)
{
  $identity = strtolower(trim((string)$identity));
  $superadmin = getConfiguredSuperadminUsername();

  if ($identity === '' || $superadmin === '') {
    return false;
  }

  return hash_equals($superadmin, $identity);
}

/**
 * Check whether current authenticated admin is configured superadmin.
 */
function isCurrentAdminSuperadmin()
{
  if (!isAdminAuthenticated()) {
    return false;
  }

  if (isset($_SESSION['is_superadmin'])) {
    return (bool)$_SESSION['is_superadmin'];
  }

  global $db;
  if (!isset($db)) {
    return false;
  }

  $userId = (int)($_SESSION['user_id'] ?? 0);
  if ($userId <= 0) {
    return false;
  }

  try {
    $stmt = $db->prepare('SELECT is_superadmin FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $isSuperadmin = (int)$stmt->fetchColumn() === 1;
    $_SESSION['is_superadmin'] = $isSuperadmin;
    return $isSuperadmin;
  } catch (Exception $e) {
    error_log('isCurrentAdminSuperadmin lookup error: ' . $e->getMessage());
    return false;
  }
}

/**
 * Validate that the current role session is active and allowed.
 */
function isActiveAdminSession()
{
  return isAdminAuthenticated();
}

/**
 * Enforce admin authentication for protected admin pages.
 */
function requireAdminAuth($redirectPath = 'login.php')
{
  if (isset($_SESSION['user_id'])) {
    enforceAuthenticatedSessionTimeout($redirectPath);
  }

  if (!isAdminAuthenticated() || !isActiveAdminSession()) {
    unset($_SESSION['show_admin_welcome']);
    unset($_SESSION['admin_profile']);
    setFlashPageAlert('warning', 'Access Denied', 'Staff access requires a librarian or admin account.');
    redirect(appPath($redirectPath, ['force' => 1]));
  }
}

/**
 * Ensure a reusable session-scoped admin CSRF token exists.
 */
function ensureAdminCsrfToken()
{
  if (
    !isset($_SESSION['admin_csrf_token']) ||
    !is_string($_SESSION['admin_csrf_token']) ||
    $_SESSION['admin_csrf_token'] === ''
  ) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['admin_csrf_issued_at'] = time();
  }

  return $_SESSION['admin_csrf_token'];
}

/**
 * Get the active admin CSRF token.
 */
function getAdminCsrfToken()
{
  return ensureAdminCsrfToken();
}

/**
 * Validate a submitted admin CSRF token against current session token.
 */
function validateAdminCsrfToken($submittedToken)
{
  if (!is_string($submittedToken) || $submittedToken === '') {
    return false;
  }

  $sessionToken = $_SESSION['admin_csrf_token'] ?? null;
  if (!is_string($sessionToken) || $sessionToken === '') {
    return false;
  }

  return hash_equals($sessionToken, $submittedToken);
}

/**
 * Clear admin CSRF token state.
 */
function clearAdminCsrfToken()
{
  unset($_SESSION['admin_csrf_token'], $_SESSION['admin_csrf_issued_at']);
}

/**
 * Resolve a safe key for public CSRF token scopes.
 */
function normalizePublicCsrfScope($scope)
{
  $normalized = strtolower(trim((string)$scope));
  if ($normalized === '') {
    return 'default';
  }

  return preg_replace('/[^a-z0-9_\-]/', '_', $normalized);
}

/**
 * Ensure a reusable session-scoped public CSRF token exists for a scope.
 */
function ensurePublicCsrfToken($scope = 'default')
{
  $scopeKey = normalizePublicCsrfScope($scope);

  if (!isset($_SESSION['public_csrf_tokens']) || !is_array($_SESSION['public_csrf_tokens'])) {
    $_SESSION['public_csrf_tokens'] = [];
  }

  if (!isset($_SESSION['public_csrf_issued_at']) || !is_array($_SESSION['public_csrf_issued_at'])) {
    $_SESSION['public_csrf_issued_at'] = [];
  }

  $token = $_SESSION['public_csrf_tokens'][$scopeKey] ?? '';
  if (!is_string($token) || $token === '') {
    $_SESSION['public_csrf_tokens'][$scopeKey] = bin2hex(random_bytes(32));
    $_SESSION['public_csrf_issued_at'][$scopeKey] = time();
  }

  return $_SESSION['public_csrf_tokens'][$scopeKey];
}

/**
 * Get active public CSRF token for a scope.
 */
function getPublicCsrfToken($scope = 'default')
{
  return ensurePublicCsrfToken($scope);
}

/**
 * Validate submitted public CSRF token against scoped session token.
 */
function validatePublicCsrfToken($submittedToken, $scope = 'default', $maxAgeSeconds = 7200)
{
  if (!is_string($submittedToken) || $submittedToken === '') {
    return false;
  }

  $scopeKey = normalizePublicCsrfScope($scope);
  $sessionTokens = $_SESSION['public_csrf_tokens'] ?? [];
  $sessionIssued = $_SESSION['public_csrf_issued_at'] ?? [];
  $sessionToken = $sessionTokens[$scopeKey] ?? null;
  $issuedAt = (int)($sessionIssued[$scopeKey] ?? 0);

  if (!is_string($sessionToken) || $sessionToken === '') {
    return false;
  }

  if ($issuedAt > 0 && (time() - $issuedAt) > (int)$maxAgeSeconds) {
    return false;
  }

  return hash_equals($sessionToken, $submittedToken);
}

/**
 * Clear public CSRF tokens.
 */
function clearPublicCsrfToken($scope = null)
{
  if ($scope === null) {
    unset($_SESSION['public_csrf_tokens'], $_SESSION['public_csrf_issued_at']);
    return;
  }

  $scopeKey = normalizePublicCsrfScope($scope);
  unset($_SESSION['public_csrf_tokens'][$scopeKey], $_SESSION['public_csrf_issued_at'][$scopeKey]);
}

/**
 * Resolve normalized client IP address.
 */
function getClientIpAddress()
{
  $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
  return $ip !== '' ? $ip : '0.0.0.0';
}

/**
 * Normalize an origin-like URL into scheme://host:port format.
 */
function normalizeOriginUrl($url)
{
  $url = trim((string)$url);
  if ($url === '') {
    return null;
  }

  $parts = parse_url($url);
  if (!is_array($parts)) {
    return null;
  }

  $scheme = strtolower(trim((string)($parts['scheme'] ?? '')));
  $host = strtolower(trim((string)($parts['host'] ?? '')));

  if ($scheme === '' || $host === '') {
    return null;
  }

  if ($scheme !== 'http' && $scheme !== 'https') {
    return null;
  }

  $port = isset($parts['port']) ? (int)$parts['port'] : ($scheme === 'https' ? 443 : 80);
  if ($port <= 0) {
    $port = $scheme === 'https' ? 443 : 80;
  }

  return $scheme . '://' . $host . ':' . $port;
}

/**
 * Build allowed same-origin candidates based on app URL and request host.
 */
function getAllowedOriginCandidates()
{
  $allowed = [];

  $appendOriginCandidate = function ($url) use (&$allowed) {
    $normalized = normalizeOriginUrl((string)$url);
    if ($normalized !== null) {
      $allowed[$normalized] = true;
    }
  };

  if (defined('APP_URL')) {
    $appUrl = (string)APP_URL;
    $appendOriginCandidate($appUrl);

    $appParts = parse_url($appUrl);
    if (is_array($appParts)) {
      $scheme = strtolower(trim((string)($appParts['scheme'] ?? '')));
      $host = strtolower(trim((string)($appParts['host'] ?? '')));
      if (($scheme === 'http' || $scheme === 'https') && $host !== '') {
        if (strpos($host, 'www.') === 0) {
          $appendOriginCandidate($scheme . '://' . substr($host, 4));
        } else {
          $appendOriginCandidate($scheme . '://www.' . $host);
        }
      }
    }
  }

  $requestHost = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
  if ($requestHost !== '') {
    $isHttps = false;
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      $isHttps = true;
    } elseif ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
      $isHttps = true;
    } elseif (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
      $isHttps = true;
    }

    $scheme = $isHttps ? 'https' : 'http';
    $appendOriginCandidate($scheme . '://' . $requestHost);
  }

  $forwardedHost = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
  if ($forwardedHost !== '') {
    $forwardedParts = explode(',', $forwardedHost);
    $forwardedHost = trim((string)($forwardedParts[0] ?? ''));
    if ($forwardedHost !== '') {
      $isForwardedHttps = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
      $forwardedScheme = $isForwardedHttps ? 'https' : 'http';
      $appendOriginCandidate($forwardedScheme . '://' . $forwardedHost);
    }
  }

  return array_keys($allowed);
}

/**
 * Resolve request origin from Origin header or Referer fallback.
 */
function getRequestOriginCandidate()
{
  $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
  if ($origin !== '') {
    return [
      'source' => 'origin',
      'origin' => normalizeOriginUrl($origin),
      'raw' => $origin,
    ];
  }

  $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
  if ($referer !== '') {
    return [
      'source' => 'referer',
      'origin' => normalizeOriginUrl($referer),
      'raw' => $referer,
    ];
  }

  return [
    'source' => 'none',
    'origin' => null,
    'raw' => '',
  ];
}

/**
 * Validate same-origin for state-changing requests.
 * On localhost, origin validation is relaxed since browsers often omit
 * Origin/Referer headers for same-origin POST requests.
 */
function validateStateChangingRequestOrigin($context = 'request')
{
  $candidate = getRequestOriginCandidate();
  $allowedOrigins = getAllowedOriginCandidates();

  // Relax origin validation for localhost requests — browsers often omit
  // Origin/Referer headers on same-origin POST requests in local dev.
  $hostOnly = strtolower(trim((string)($_SERVER['HTTP_HOST'] ?? '')));
  $hostOnly = preg_replace('/:\d+$/', '', $hostOnly);
  $isLocalHost = in_array($hostOnly, ['localhost', '127.0.0.1', '::1', '[::1]'], true);

  if ($candidate['source'] === 'none') {
    if ($isLocalHost) {
      return [
        'valid' => true,
        'reason' => 'localhost_bypass',
        'source' => 'none',
        'origin' => null,
        'allowed' => $allowedOrigins,
        'context' => (string)$context,
      ];
    }
    return [
      'valid' => false,
      'reason' => 'missing_origin_headers',
      'source' => 'none',
      'origin' => null,
      'allowed' => $allowedOrigins,
      'context' => (string)$context,
    ];
  }

  if (!is_string($candidate['origin']) || $candidate['origin'] === '') {
    if ($isLocalHost) {
      return [
        'valid' => true,
        'reason' => 'localhost_bypass',
        'source' => $candidate['source'],
        'origin' => null,
        'allowed' => $allowedOrigins,
        'context' => (string)$context,
      ];
    }
    return [
      'valid' => false,
      'reason' => 'invalid_origin_header',
      'source' => $candidate['source'],
      'origin' => null,
      'allowed' => $allowedOrigins,
      'context' => (string)$context,
    ];
  }

  $isAllowed = in_array($candidate['origin'], $allowedOrigins, true);

  return [
    'valid' => $isAllowed,
    'reason' => $isAllowed ? '' : 'origin_mismatch',
    'source' => $candidate['source'],
    'origin' => $candidate['origin'],
    'allowed' => $allowedOrigins,
    'context' => (string)$context,
  ];
}

/**
 * Insert verification/security attempt record for throttling and audits.
 */
function logVerificationAttempt($email, $attemptType, $isSuccessful)
{
  global $db;

  if (!isset($db)) {
    return;
  }

  try {
    $query = 'INSERT INTO verification_attempts (email, attempt_type, ip_address, is_successful) VALUES (:email, :attempt_type, :ip_address, :is_successful)';
    $stmt = $db->prepare($query);
    $stmt->execute([
      ':email' => strtolower(trim((string)$email)),
      ':attempt_type' => (string)$attemptType,
      ':ip_address' => getClientIpAddress(),
      ':is_successful' => $isSuccessful ? 1 : 0,
    ]);
  } catch (Exception $e) {
    error_log('logVerificationAttempt error: ' . $e->getMessage());
  }
}

/**
 * Evaluate DB-backed throttling limits by email + IP.
 */
function evaluateAttemptThrottle($attemptType, $email, $windowSeconds, $emailLimit, $ipLimit, $successFilter = null)
{
  global $db;

  $response = [
    'limited' => false,
    'retry_after' => 0,
    'email_count' => 0,
    'ip_count' => 0,
  ];

  if (!isset($db)) {
    return $response;
  }

  $attemptType = trim((string)$attemptType);
  $email = strtolower(trim((string)$email));
  $windowSeconds = max(1, (int)$windowSeconds);
  $emailLimit = max(1, (int)$emailLimit);
  $ipLimit = max(1, (int)$ipLimit);
  $ipAddress = getClientIpAddress();
  $windowExpr = 'DATE_SUB(NOW(), INTERVAL ' . $windowSeconds . ' SECOND)';
  $statusClause = '';
  $statusParams = [];

  if ($successFilter !== null) {
    $statusClause = ' AND is_successful = :is_successful';
    $statusParams[':is_successful'] = $successFilter ? 1 : 0;
  }

  try {
    if ($email !== '') {
      $emailCountStmt = $db->prepare(
        'SELECT COUNT(*) FROM verification_attempts
         WHERE email = :email AND attempt_type = :attempt_type
           AND attempted_at >= ' . $windowExpr . $statusClause
      );
      $emailCountStmt->execute(array_merge([
        ':email' => $email,
        ':attempt_type' => $attemptType,
      ], $statusParams));
      $response['email_count'] = (int)$emailCountStmt->fetchColumn();
    }

    $ipCountStmt = $db->prepare(
      'SELECT COUNT(*) FROM verification_attempts
       WHERE ip_address = :ip_address AND attempt_type = :attempt_type
         AND attempted_at >= ' . $windowExpr . $statusClause
    );
    $ipCountStmt->execute(array_merge([
      ':ip_address' => $ipAddress,
      ':attempt_type' => $attemptType,
    ], $statusParams));
    $response['ip_count'] = (int)$ipCountStmt->fetchColumn();

    $emailLimited = $email !== '' && $response['email_count'] >= $emailLimit;
    $ipLimited = $response['ip_count'] >= $ipLimit;
    $response['limited'] = $emailLimited || $ipLimited;

    if ($response['limited']) {
      $retryAfter = 0;

      if ($emailLimited) {
        $emailRetryStmt = $db->prepare(
          'SELECT MIN(UNIX_TIMESTAMP(attempted_at)) FROM verification_attempts
           WHERE email = :email AND attempt_type = :attempt_type
             AND attempted_at >= ' . $windowExpr . $statusClause
        );
        $emailRetryStmt->execute(array_merge([
          ':email' => $email,
          ':attempt_type' => $attemptType,
        ], $statusParams));
        $emailMin = (int)$emailRetryStmt->fetchColumn();
        if ($emailMin > 0) {
          $retryAfter = max($retryAfter, max(1, $windowSeconds - (time() - $emailMin)));
        }
      }

      if ($ipLimited) {
        $ipRetryStmt = $db->prepare(
          'SELECT MIN(UNIX_TIMESTAMP(attempted_at)) FROM verification_attempts
           WHERE ip_address = :ip_address AND attempt_type = :attempt_type
             AND attempted_at >= ' . $windowExpr . $statusClause
        );
        $ipRetryStmt->execute(array_merge([
          ':ip_address' => $ipAddress,
          ':attempt_type' => $attemptType,
        ], $statusParams));
        $ipMin = (int)$ipRetryStmt->fetchColumn();
        if ($ipMin > 0) {
          $retryAfter = max($retryAfter, max(1, $windowSeconds - (time() - $ipMin)));
        }
      }

      $response['retry_after'] = $retryAfter;
    }
  } catch (Exception $e) {
    error_log('evaluateAttemptThrottle error: ' . $e->getMessage());
  }

  return $response;
}

/**
 * Evaluate login-specific rate limits.
 */
function evaluateLoginRateLimit($email)
{
  return evaluateAttemptThrottle('login_attempt', $email, 900, 5, 20, false);
}

/**
 * Evaluate verification token attempt rate limits.
 */
function evaluateOtpVerifyRateLimit($email)
{
  return evaluateAttemptThrottle('otp_verify', $email, 600, 8, 30, false);
}

/**
 * Evaluate verification resend rate limits.
 */
function evaluateOtpResendRateLimit($email)
{
  return evaluateAttemptThrottle('otp_resend', $email, 300, 3, 10, null);
}

/**
 * Clean input data for use (trim only). No HTML escaping - escaping should be done at output.
 */
function cleanInput($data)
{
  if (is_array($data)) {
    return array_map('cleanInput', $data);
  }
  return trim((string)$data);
}

/**
 * Sanitize user input - DEPRECATED for DB storage. Use cleanInput() for DB-bound values.
 * This function applies HTML escaping which should only be used for output contexts.
 */
function sanitize($data)
{
  if (is_array($data)) {
    return array_map('sanitize', $data);
  }
  return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isEmailValid($email)
{
  return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate a random string
 */
function generateRandomString($length = 32)
{
  return bin2hex(random_bytes($length / 2));
}

/**
 * Set flash message
 */
function setFlash($type, $message)
{
  $_SESSION['flash'] = [
    'type' => $type, // 'success', 'error', 'warning', 'info'
    'message' => $message
  ];
}

/**
 * Handle permission denial with a consistent flash + redirect flow.
 */
function denyWithFlashRedirect($redirectPath = 'index.php', $message = 'You do not have permission to access this page.')
{
  setFlashPageAlert('error', 'Access Denied', (string)$message);
  redirect($redirectPath);
}

/**
 * Get and clear flash message
 */
function getFlash()
{
  if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
  }
  return null;
}

/**
 * Display flash message as HTML
 */
function displayFlash()
{
  $flash = getFlash();
  if (!$flash) return '';

  $cssClass = 'alert-' . $flash['type'];
  return "<div class='alert {$cssClass}' role='alert'>{$flash['message']}</div>";
}

/**
 * Get form post value safely
 */
function getPost($key, $default = '')
{
  return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Get shared mail handler instance.
 */
function getMailHandler()
{
  static $mail_handler = null;

  if ($mail_handler !== null) {
    return $mail_handler;
  }

  global $db;

  $autoloadPath = __DIR__ . '/../backend/vendor/autoload.php';
  if (!file_exists($autoloadPath)) {
    throw new RuntimeException('Composer autoload not found at backend/vendor/autoload.php. Run composer install in backend/.');
  }

  $mailHandlerPath = __DIR__ . '/../backend/mail/MailHandler.php';
  if (!file_exists($mailHandlerPath)) {
    throw new RuntimeException('MailHandler class file not found at backend/mail/MailHandler.php.');
  }

  require_once $autoloadPath;
  require_once $mailHandlerPath;

  $mail_handler = new MailHandler($db ?? null);
  return $mail_handler;
}

/**
 * Send Verification Email using PHPMailer
 */
function sendVerificationEmail($email, $name = '', $verification_token = '')
{
  try {
    $result = getMailHandler()->sendVerificationEmail($email, $name, $verification_token);
    return $result;
  } catch (Throwable $e) {
    error_log("Error sending verification email: " . $e->getMessage());
    $message = 'Failed to send verification email';
    if (defined('APP_DEBUG') && APP_DEBUG) {
      $message .= ': ' . $e->getMessage();
    }
    return ['success' => false, 'error' => $message];
  }
}

/**
 * Send password reset email using PHPMailer
 */
function sendPasswordResetEmail($email, $reset_link, $name = '')
{
  try {
    $result = getMailHandler()->sendPasswordResetEmailByLink($email, $reset_link, $name);
    return !empty($result['success']);
  } catch (Throwable $e) {
    error_log("Email sending error: " . $e->getMessage());
    return false;
  }

  return false;
}

/**
 * Log activity for auditing
 */
function logActivity($user_id, $action, $details = '')
{
  global $db;
  try {
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (:user_id, :action, :details, :ip)";
    $stmt = $db->prepare($query);
    $stmt->execute([
      ':user_id' => $user_id,
      ':action' => $action,
      ':details' => $details,
      ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
  } catch (Exception $e) {
    error_log("Activity log error: " . $e->getMessage());
  }
}

/**
 * Check session timeout
 */
function checkSessionTimeout()
{
  enforceAuthenticatedSessionTimeout('login.php');
}

/**
 * Enforce idle and absolute timeout for authenticated sessions.
 */
function enforceAuthenticatedSessionTimeout($redirectPath = 'login.php')
{
  if (!isset($_SESSION['user_id'])) {
    return;
  }

  $now = time();
  $idleTimeout = max(60, (int)(defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600));
  $absoluteTimeout = max(300, (int)(defined('SESSION_ABSOLUTE_TIMEOUT') ? constant('SESSION_ABSOLUTE_TIMEOUT') : 43200));
  $sessionStartedAt = (int)($_SESSION['session_started_at'] ?? $_SESSION['login_time'] ?? $now);
  $lastActivityAt = (int)($_SESSION['last_activity_at'] ?? $_SESSION['login_time'] ?? $now);

  $isIdleExpired = ($now - $lastActivityAt) > $idleTimeout;
  $isAbsoluteExpired = ($now - $sessionStartedAt) > $absoluteTimeout;

  if ($isIdleExpired || $isAbsoluteExpired) {
    $timeoutReason = $isIdleExpired ? 'idle' : 'absolute';
    $timeoutRole = strtolower(trim((string)($_SESSION['user_role'] ?? 'borrower')));

    if (class_exists('AuthSupport')) {
      AuthSupport::clearSession();
      AuthSupport::ensureSessionStarted();
    } else {
      $_SESSION = [];
      session_destroy();
      if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
      }
    }

    $_SESSION['show_timeout_alert'] = true;
    $_SESSION['timeout_reason'] = $timeoutReason;
    $_SESSION['timeout_role'] = $timeoutRole;
    redirect(appPath($redirectPath, ['timeout' => 1]));
  }

  $_SESSION['last_activity_at'] = $now;
}

/**
 * Render shared SweetAlert script assets.
 */
function renderSweetAlertScripts()
{
  $config_path = htmlspecialchars(appPath('public/js/sweetalert-config.js'), ENT_QUOTES, 'UTF-8');
  $page_alerts_path = htmlspecialchars(appPath('public/js/page-alerts.js', ['v' => '3']), ENT_QUOTES, 'UTF-8');

  echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>' . PHP_EOL;
  echo '<script src="' . $config_path . '"></script>' . PHP_EOL;
  echo '<script src="' . $page_alerts_path . '"></script>' . PHP_EOL;
}

/**
 * Render queued page alerts.
 */
function renderPageAlerts(array $alerts)
{
  if (empty($alerts)) {
    return;
  }

  $payload = json_encode(array_values($alerts), JSON_UNESCAPED_SLASHES);
  if ($payload === false) {
    $payload = '[]';
  }

  echo '<script>PageAlerts.run(' . $payload . ');</script>' . PHP_EOL;
}

function appendReceiptAlertMeta(array &$alert, array $result): void
{
    if (empty($result['ok'])) {
        return;
    }

    $receiptId = (int)($result['receipt_id'] ?? 0);
    if ($receiptId <= 0) {
        return;
    }

    $receiptCode = trim((string)($result['receipt_code'] ?? ''));
    $receiptViewUrl = trim((string)($result['receipt_print_url'] ?? ''));
    if ($receiptViewUrl === '') {
        $receiptViewUrl = appPath('librarian-receipt.php', [
            'receipt_id' => $receiptId,
        ]);
    }

    $appendQuery = static function (string $url, string $query): string {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . $query;
    };

    $receiptPrintUrl = $appendQuery($receiptViewUrl, 'auto_print=1');
    $receiptDownloadUrl = $appendQuery($receiptViewUrl, 'download=1');
    $alert['onConfirmOpen'] = $receiptPrintUrl;
    $alert['receipt'] = [
        'id' => $receiptId,
        'code' => $receiptCode,
        'viewUrl' => $receiptViewUrl,
        'printUrl' => $receiptPrintUrl,
        'downloadUrl' => $receiptDownloadUrl,
        'mobileFileName' => ($receiptCode !== '' ? strtolower($receiptCode) : ('receipt-' . $receiptId)) . '.html',
    ];

    // Message is kept as is, receipt code is displayed in the UI separately.
}

function getLibrarianCssPaths(): array
{
    $mainCssFile = APP_ROOT . '/public/css/main.css';
    $adminCssFile = APP_ROOT . '/public/css/admin.css';
    $librarianCssFile = APP_ROOT . '/public/css/librarian.css';
    $mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
    $adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
    $librarianCssVersion = file_exists($librarianCssFile) ? (string)filemtime($librarianCssFile) : (string)time();
    return [
        'main' => htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8'),
        'admin' => htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8'),
        'librarian' => htmlspecialchars(appPath('public/css/librarian.css', ['v' => $librarianCssVersion]), ENT_QUOTES, 'UTF-8'),
    ];
}

function getFlashPageAlerts(): array
{
    $page_alerts = [];
    $flash = getFlash();
    if (is_array($flash) && isset($flash['type'], $flash['message'])) {
        $page_alerts[] = [
            'type' => (string)$flash['type'],
            'title' => 'Notice',
            'message' => (string)$flash['message'],
        ];
    }
    return $page_alerts;
}

/**
 * Store a page alert descriptor in the session for display after redirect.
 * Works with renderPageAlerts() on the next page load.
 */
function setFlashPageAlert(string $type, string $title, string $message, string $redirect = '', array $extra = []): void
{
    $alert = array_merge([
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'redirect' => $redirect,
    ], $extra);

    if (!isset($_SESSION['page_alerts'])) {
        $_SESSION['page_alerts'] = [];
    }
    $_SESSION['page_alerts'][] = $alert;
}

/**
 * Retrieve and clear stored page alerts from the session.
 */
function getStoredPageAlerts(): array
{
    $alerts = [];
    if (isset($_SESSION['page_alerts']) && is_array($_SESSION['page_alerts'])) {
        $alerts = $_SESSION['page_alerts'];
        unset($_SESSION['page_alerts']);
    }
    return $alerts;
}

function formatBorrowerName(string $firstName = '', string $lastName = '', string $email = ''): string
{
    $name = trim($firstName . ' ' . $lastName);
    $name = preg_replace('/\s+/', ' ', $name);
    return $name !== '' ? $name : $email;
}

function getBorrowerCssPaths(): array
{
    $mainCssFile = APP_ROOT . '/public/css/main.css';
    $borrowerCssFile = APP_ROOT . '/public/css/borrower.css';
    $mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
    $borrowerCssVersion = file_exists($borrowerCssFile) ? (string)filemtime($borrowerCssFile) : (string)time();
    return [
        'main' => htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8'),
        'borrower' => htmlspecialchars(appPath('public/css/borrower.css', ['v' => $borrowerCssVersion]), ENT_QUOTES, 'UTF-8'),
    ];
}
