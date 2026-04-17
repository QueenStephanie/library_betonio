<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Forgot Password Page & Handler
 * Initiates password reset process
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/includes/services/AuthService.php';

$error = '';
$success = '';
$csrf_scope = 'forgot_password';
$csrf_token = getPublicCsrfToken($csrf_scope);

// Handle forget password request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('forgot_password_post');
  $submittedToken = (string)($_POST['csrf_token'] ?? '');
   $email = getPost('email');

  if (!$originCheck['valid']) {
    logVerificationAttempt($email, 'csrf_reject', false);
    error_log('Blocked forgot-password POST due to origin validation: ' . json_encode($originCheck));
    $error = 'Security check failed. Please refresh and try again.';
  } elseif (!validatePublicCsrfToken($submittedToken, $csrf_scope)) {
    logVerificationAttempt($email, 'csrf_reject', false);
    $error = 'Security check failed. Please refresh and try again.';
  } else {
    $authService = new AuthService($db);
    $result = $authService->requestPasswordReset($email);

    if ($result['success']) {
      $_SESSION['show_password_reset_alert'] = true;
      redirect(appPath('forgot-password.php', ['success' => 1]));
    } else {
      $error = $result['error'];
    }
  }
}

// Check if password reset was successful
$show_success_alert = isset($_GET['success']) && $_GET['success'] === '1' && isset($_SESSION['show_password_reset_alert']);
if ($show_success_alert) {
  unset($_SESSION['show_password_reset_alert']);
}

$page_alerts = [];
if ($show_success_alert) {
  $page_alerts[] = [
    'method' => 'passwordResetSuccess',
    'redirect' => appPath('login.php')
  ];
}

if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Error',
    'message' => $error
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Forgot Password</title>
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
        <p class="visual-quote">"Where the mind is without fear, the head is held high."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <h1>Reset Your Password</h1>
        <p class="subtitle">Enter your email address and we'll send you instructions to reset your password.</p>

        <form class="auth-form" method="POST" action="forgot-password.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" placeholder="jane@example.com" autocomplete="email"
            required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <button class="submit-button" type="submit">Send Reset Instructions</button>
        </form>

        <div class="auth-links">
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
