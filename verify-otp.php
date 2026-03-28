<?php

/**
 * Email Verification Page
 * Handles email verification via token link
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($email)) {
  redirect('/library_betonio/register.php');
}

$auth = new AuthManager($db);

// If token is provided in URL, verify it automatically
if (!empty($token)) {
  $token_result = $auth->verifyEmailByToken($email, $token);

  if ($token_result['success']) {
    $_SESSION['show_verification_success'] = true;
    redirect('/library_betonio/verify-otp.php?email=' . urlencode($email) . '&success=1');
  }

  $error = $token_result['error'] ?? 'Verification failed. Please try again.';
}

// Check if verification was successful
$show_success = isset($_GET['success']) && $_GET['success'] === '1' && isset($_SESSION['show_verification_success']);
if ($show_success) {
  unset($_SESSION['show_verification_success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Verify Email</title>
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
        <p class="visual-quote">"Books are the treasures of the world."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <h1>Email Verification</h1>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert" style="background-color: #fee; border-left: 4px solid #f44; padding: 12px; border-radius: 4px; margin: 20px 0;">
            <strong>✗ Error:</strong> <?php echo htmlspecialchars($error); ?>
          </div>
          <p style="color: #666; text-align: center; margin: 20px 0;">
            The verification link may have expired or is invalid.
          </p>
          <p style="text-align: center;">
            <a href="/library_betonio/register.php" class="submit-button" style="display: inline-block; margin-top: 10px;">← Back to Registration</a>
          </p>
        <?php else: ?>
          <div style="text-align: center; padding: 40px 0;">
            <p style="font-size: 16px; color: #666;">
              Processing your verification...
            </p>
            <p style="color: #999; margin-top: 20px;">
              <a href="/library_betonio/login.php">Go to Login</a> | <a href="/library_betonio/register.php">Back to Registration</a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <!-- SweetAlert Configuration -->
  <script src="/library_betonio/public/js/sweetalert-config.js"></script>

  <script>
    // Show verification success alert
    <?php if ($show_success): ?>
      SweetAlerts.verificationSuccess(function() {
        window.location.href = '/library_betonio/login.php';
      });
    <?php endif; ?>

    // Show error alert if there's an error
    <?php if ($error): ?>
      SweetAlerts.error('Verification Failed', '<?php echo addslashes($error); ?>', function() {
        window.location.href = '/library_betonio/register.php';
      });
    <?php endif; ?>
  </script>
</body>

</html>