<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Login Page & Handler
 * Handles both GET (display form) and POST (process login)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/includes/services/AuthService.php';

// Prevent Chrome from serving stale cached login page after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$error = '';
$success = '';
$csrf_scope = 'login';
$csrf_token = getPublicCsrfToken($csrf_scope);

$forceLogin = isset($_GET['force']) && $_GET['force'] === '1';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
  AuthSupport::clearSession();
  redirect('login.php');
}

// Check if already logged in
if (isset($_SESSION['user_id']) && !$forceLogin) {
  redirect(resolveAuthenticatedHomePath());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('login_post');
  $originReason = (string)($originCheck['reason'] ?? '');
  // Some clients/proxies omit Origin/Referer on same-site form POSTs; allow that case only when CSRF token validation succeeds.
  $allowMissingOriginFallback = !$originCheck['valid'] && $originReason === 'missing_origin_headers';
  $submittedToken = (string)($_POST['csrf_token'] ?? '');
  $email = getPost('email');

  if (!$originCheck['valid'] && !$allowMissingOriginFallback) {
    logVerificationAttempt($email, 'csrf_reject', false);
    error_log('Blocked login POST due to origin validation: ' . json_encode($originCheck));
    $error = 'Security check failed. Please refresh and try again.';
  } elseif (!validatePublicCsrfToken($submittedToken, $csrf_scope)) {
    logVerificationAttempt($email, 'csrf_reject', false);
    $error = 'Security check failed. Please refresh and try again.';
  } else {
    $password = getPost('password');

    $rateLimit = evaluateLoginRateLimit($email);
    if (!empty($rateLimit['limited'])) {
      logVerificationAttempt($email, 'login_blocked', false);
      $error = 'Too many failed login attempts. Please wait a few minutes before trying again.';
    } else {
      $authService = new AuthService($db);
      $result = $authService->login($email, $password);

      if ($result['success']) {
        logVerificationAttempt($email, 'login_attempt', true);
        setFlash('success', $result['message']);
        $postLoginRedirect = resolveAuthenticatedHomePath();
        if (strpos($postLoginRedirect, 'admin-dashboard.php') === 0) {
          $_SESSION['show_admin_welcome'] = true;
        } else {
          $_SESSION['page_alerts'][] = [
            'type' => 'success',
            'title' => 'Welcome Back!',
            'message' => $result['message']
          ];
        }
        redirect($postLoginRedirect);
      } else {
        logVerificationAttempt($email, 'login_attempt', false);
        if (isset($result['unverified']) && $result['unverified']) {
          redirect(appPath('verify-otp.php', ['email' => $result['email'], 'from_login' => '1']));
        }
        $error = $result['error'];
      }
    }
  }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
  $_SESSION['show_timeout_alert'] = true;
}

$page_alerts = [];
if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Login Failed',
    'message' => $error
  ];
}

if (isset($_SESSION['show_timeout_alert'])) {
  $timeoutReason = (string)($_SESSION['timeout_reason'] ?? 'idle');
  $timeoutRole = strtolower(trim((string)($_SESSION['timeout_role'] ?? 'borrower')));

  unset($_SESSION['show_timeout_alert']);
  unset($_SESSION['timeout_reason']);
  unset($_SESSION['timeout_role']);

  $sessionExpiredMessage = 'Your session has expired. Please log in again.';
  if ($timeoutReason === 'absolute') {
    $sessionExpiredMessage = 'Your secure session reached its maximum duration. Please log in again.';
  }

  if ($timeoutRole === 'admin') {
    $sessionExpiredMessage .= ' Admin tools require a fresh authenticated session.';
  }

  $page_alerts[] = [
    'type' => 'warning',
    'title' => 'Session Expired',
    'message' => $sessionExpiredMessage,
    'confirmText' => 'OK',
    'cancelText' => null
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Log In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="public/css/auth.css">
</head>

<body>
  <main class="auth-page">
    <section class="auth-visual">
      <div class="visual-overlay"></div>
      <div class="visual-content">
        <a class="visual-brand" href="index.php">QueenLib</a>
        <p class="visual-quote">"A reader lives a thousand lives before he dies."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to your QueenLib account.</p>

        <form class="auth-form" method="POST" action="login.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email"
            autocomplete="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password"
              autocomplete="current-password" required>
            <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
              </svg>
            </button>
          </div>

          <button class="submit-button" type="submit">Log In</button>
        </form>

        <div class="auth-links">
          <a class="forgot-link" href="forgot-password.php">Forgot your password?</a>
          <p>Don't have an account? <a class="register-link" href="register.php">Register →</a></p>
        </div>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
