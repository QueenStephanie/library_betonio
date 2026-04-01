<?php

/**
 * Forgot Password Page & Handler
 * Initiates password reset process
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'backend/vendor/autoload.php';
require_once 'backend/classes/PasswordRecovery.php';
require_once 'backend/mail/MailHandler.php';

$error = '';
$success = '';

// Handle forget password request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize(getPost('email'));

  // Initialize PasswordRecovery with database and mail handler
  // $db is already initialized in includes/config.php
  $mail_handler = new MailHandler($db);
  $password_recovery = new PasswordRecovery($db, $mail_handler);
  
  $result = $password_recovery->requestPasswordReset($email);

  if ($result['success']) {
    $_SESSION['show_password_reset_alert'] = true;
    redirect(appPath('forgot-password.php', ['success' => 1]));
  } else {
    $error = $result['error'];
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

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="forgot-password.php">
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
