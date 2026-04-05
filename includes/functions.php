<?php

/**
 * Helper Functions
 * Utility functions for the application
 */

/**
 * Redirect to a page
 */
function redirect($path, $status = 302)
{
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
}

/**
 * Check whether an admin session is authenticated.
 */
function isAdminAuthenticated()
{
  return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
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

  $identity = $_SESSION['admin_username'] ?? '';
  return isConfiguredSuperadminIdentity($identity);
}

/**
 * Validate that the current admin session is still active in registry.
 */
function isActiveAdminSession()
{
  if (!isAdminAuthenticated()) {
    return false;
  }

  if (!isset($_SESSION['admin_username']) || !is_string($_SESSION['admin_username']) || $_SESSION['admin_username'] === '') {
    return false;
  }

  global $db;
  if (!isset($db)) {
    return false;
  }

  $sessionId = session_id();
  if (!is_string($sessionId) || $sessionId === '') {
    return false;
  }

  try {
    $stmt = $db->prepare(
      'SELECT id
       FROM admin_session_registry
       WHERE session_id_hash = :session_id_hash
         AND admin_identity = :admin_identity
         AND invalidated_at IS NULL
       LIMIT 1'
    );
    $stmt->execute([
      ':session_id_hash' => hash('sha256', $sessionId),
      ':admin_identity' => $_SESSION['admin_username'],
    ]);
    return (bool)$stmt->fetchColumn();
  } catch (Exception $e) {
    error_log('isActiveAdminSession error: ' . $e->getMessage());
    return false;
  }
}

/**
 * Enforce admin authentication for protected admin pages.
 */
function requireAdminAuth($redirectPath = 'admin-login.php')
{
  if (!isAdminAuthenticated() || !isActiveAdminSession()) {
    unset($_SESSION['admin_authenticated']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_last_login']);
    unset($_SESSION['admin_auth_mode']);
    unset($_SESSION['admin_credential_id']);
    unset($_SESSION['show_admin_welcome']);
    unset($_SESSION['admin_profile']);
    unset($_SESSION['admin_password_changed_at']);
    unset($_SESSION['admin_is_superadmin']);
    clearAdminCsrfToken();
    redirect($redirectPath);
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
 * Sanitize user input
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

  require_once __DIR__ . '/../backend/vendor/autoload.php';
  require_once __DIR__ . '/../backend/mail/MailHandler.php';

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
  } catch (Exception $e) {
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
  } catch (Exception $e) {
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
  if (isset($_SESSION['login_time'])) {
    $elapsed = time() - $_SESSION['login_time'];
    if ($elapsed > SESSION_TIMEOUT) {
      session_destroy();
      redirect(appPath('login.php', ['timeout' => 1]));
    }
  }
}

/**
 * Render shared SweetAlert script assets.
 */
function renderSweetAlertScripts()
{
  $config_path = htmlspecialchars(appPath('public/js/sweetalert-config.js'), ENT_QUOTES, 'UTF-8');
  $page_alerts_path = htmlspecialchars(appPath('public/js/page-alerts.js'), ENT_QUOTES, 'UTF-8');

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
