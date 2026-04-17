<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Reset Password Page & Handler
 * Completes password reset with token
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/includes/services/AuthService.php';

$error = '';
$success = '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : ''; // Don't sanitize token - it's hex
$csrf_scope = 'reset_password';
$csrf_token = getPublicCsrfToken($csrf_scope);
$authService = new AuthService($db);

if (empty($email) || empty($token)) {
  $error = 'Invalid reset link';
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $token_check = $authService->verifyResetToken($email, $token);
  if (empty($token_check['success'])) {
    $error = 'Reset link is invalid or expired. Please request a new password reset link.';
  }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('reset_password_post');
  $submittedToken = (string)($_POST['csrf_token'] ?? '');
  $email = getPost('email');

  if (!$originCheck['valid']) {
    logVerificationAttempt($email, 'csrf_reject', false);
    error_log('Blocked reset-password POST due to origin validation: ' . json_encode($originCheck));
    $error = 'Security check failed. Please refresh and try again.';
  } elseif (!validatePublicCsrfToken($submittedToken, $csrf_scope)) {
    logVerificationAttempt($email, 'csrf_reject', false);
    $error = 'Security check failed. Please refresh and try again.';
  } else {
    $reset_token = trim(getPost('reset_token')); // Don't sanitize token - keep it as plain hex
    $password = getPost('password');
    $password_confirm = getPost('password_confirm');

    $result = $authService->resetPassword($email, $reset_token, $password, $password_confirm);

    if ($result['success']) {
      setFlash('success', 'Password reset successfully! You can now log in with your new password.');
      redirect('login.php');
    } else {
      $error = $result['error'];
    }
  }
}

$page_alerts = [];
if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Password Reset Failed',
    'message' => $error
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Reset Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="public/css/auth.css">
</head>

<body>
  <main class="auth-page">
    <section class="auth-visual">
      <div class="visual-overlay"></div>
      <div class="visual-content">
        <a class="visual-brand" href="index.php">QueenLib</a>
        <p class="visual-quote">"A new page turns with fresh hope."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <h1>Create New Password</h1>
        <p class="subtitle">Enter your new password to reset your account.</p>

        <?php if (!$error): ?>
          <form class="auth-form" method="POST" action="reset-password.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <input type="hidden" name="reset_token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

            <label for="password">New Password</label>
            <div class="password-field">
              <input id="password" name="password" type="password" placeholder="At least 8 characters"
                autocomplete="new-password" minlength="8" data-password-primary required>
              <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
                </svg>
              </button>
            </div>
            <div class="password-strength" data-password-strength aria-live="polite">
              <span class="password-strength-label">Password strength: <strong data-password-strength-text>Too weak</strong></span>
              <span class="password-strength-track"><span class="password-strength-fill" data-password-strength-fill></span></span>
              <span class="password-strength-hint">Use 8+ characters with uppercase, lowercase, number, and symbol.</span>
            </div>

            <label for="password_confirm">Confirm Password</label>
            <div class="password-field">
              <input id="password_confirm" name="password_confirm" type="password" placeholder="Repeat your password"
                autocomplete="new-password" minlength="8" data-password-confirm required>
              <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
                </svg>
              </button>
            </div>
            <p class="field-error" data-confirm-error hidden>Passwords do not match.</p>

            <button class="submit-button" type="submit">Reset Password</button>
          </form>
        <?php endif; ?>

        <div class="auth-links">
          <?php if ($error): ?>
            <p><a href="forgot-password.php">Request a new reset link</a></p>
          <?php endif; ?>
          <p><a href="login.php">← Back to Login</a></p>
        </div>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
