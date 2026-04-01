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
  redirect('register.php');
}

$auth = new AuthManager($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
  try {
    $resend_email = sanitize(getPost('email'));

    if (empty($resend_email) || !filter_var($resend_email, FILTER_VALIDATE_EMAIL)) {
      $error = 'A valid email is required.';
    } else {
      $stmt = $db->prepare('SELECT id, first_name, is_verified, verification_token FROM users WHERE email = :email LIMIT 1');
      $stmt->execute([':email' => $resend_email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$user) {
        $error = 'Email not found.';
      } elseif (!empty($user['is_verified'])) {
        $success = 'This email is already verified. You can log in now.';
      } else {
        $verificationToken = $user['verification_token'];

        if (empty($verificationToken)) {
          $verificationToken = bin2hex(random_bytes(32));
          $tokenExpiry = date('Y-m-d H:i:s', time() + 86400);
          $update = $db->prepare('UPDATE users SET verification_token = :token, verification_token_expires = :expiry WHERE id = :id');
          $update->execute([
            ':token' => $verificationToken,
            ':expiry' => $tokenExpiry,
            ':id' => $user['id']
          ]);
        }

        $mailResult = sendVerificationEmail($resend_email, $user['first_name'] ?: 'User', $verificationToken);

        if (!empty($mailResult['success'])) {
          $success = 'Verification email resent. Please check Inbox/Spam/Promotions.';
        } else {
          $error = $mailResult['error'] ?? 'Failed to resend verification email.';
        }
      }
    }
  } catch (Exception $e) {
    $error = 'Failed to resend verification email. Please try again.';
    error_log('Resend verification failed: ' . $e->getMessage());
  }
}

// If token is provided in URL, verify it automatically
if (!empty($token)) {
  $token_result = $auth->verifyEmailByToken($email, $token);

  if ($token_result['success']) {
    setFlash('success', 'Email verified successfully. You can now log in.');
    redirect(appPath('login.php'));
  }

  $error = $token_result['error'] ?? 'Verification failed. Please try again.';
}

$page_alerts = [];

if ($success) {
  $page_alerts[] = [
    'type' => 'success',
    'title' => 'Email Sent',
    'message' => $success
  ];
}

if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Verification Failed',
    'message' => $error,
    'redirect' => appPath('register.php')
  ];
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
            <a href="<?php echo appPath('register.php'); ?>" class="submit-button" style="display: inline-block; margin-top: 10px;">← Back to Registration</a>
          </p>
        <?php else: ?>
          <div style="text-align: center; padding: 40px 0;">
            <p style="font-size: 16px; color: #666;">
              Processing your verification...
            </p>
            <form method="POST" action="<?php echo appPath('verify-otp.php', ['email' => $email]); ?>" style="margin: 20px 0;">
              <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
              <button type="submit" name="resend_verification" class="submit-button" style="display: inline-block;">Resend Verification Email</button>
            </form>
            <p style="color: #999; margin-top: 20px;">
              <a href="<?php echo appPath('login.php'); ?>">Go to Login</a> | <a href="<?php echo appPath('register.php'); ?>">Back to Registration</a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>