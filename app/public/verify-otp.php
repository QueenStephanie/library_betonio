<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Email Verification Page
 * Handles email verification via token link and resend for unverified accounts
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/includes/services/AuthService.php';

$error = '';
$success = '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$fromLogin = isset($_GET['from_login']) && $_GET['from_login'] === '1';
$csrf_scope = 'verify_resend';
$csrf_token = getPublicCsrfToken($csrf_scope);
$resendPublicMessage = 'If your account is eligible, a verification email will arrive shortly.';
$resendCooldownRemaining = 0;

if (empty($email)) {
  redirect('register.php');
}

$authService = new AuthService($db);

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $resendLimitCheck = evaluateOtpResendRateLimit($email);
  if (!empty($resendLimitCheck['limited'])) {
    $resendCooldownRemaining = (int)($resendLimitCheck['retry_after'] ?? 0);
  }
}

// Handle resend verification request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
  try {
    $originCheck = validateStateChangingRequestOrigin('verify_otp_resend_post');
    $submittedToken = (string)($_POST['csrf_token'] ?? '');
    $resend_email = getPost('email');
    $normalizedResendEmail = strtolower(trim((string)$resend_email));

    if (!$originCheck['valid']) {
      logVerificationAttempt($normalizedResendEmail, 'csrf_reject', false);
      error_log('Blocked verify-otp resend POST due to origin validation: ' . json_encode($originCheck));
      $error = 'Security check failed. Please refresh and try again.';
    } elseif (!validatePublicCsrfToken($submittedToken, $csrf_scope)) {
      logVerificationAttempt($normalizedResendEmail, 'csrf_reject', false);
      $error = 'Security check failed. Please refresh and try again.';
    } elseif (empty($resend_email) || !filter_var($resend_email, FILTER_VALIDATE_EMAIL)) {
      $error = 'A valid email is required.';
    } else {
      $resendLimitCheck = evaluateOtpResendRateLimit($normalizedResendEmail);
      if (!empty($resendLimitCheck['limited'])) {
        $resendCooldownRemaining = (int)($resendLimitCheck['retry_after'] ?? 0);
        logVerificationAttempt($normalizedResendEmail, 'otp_resend', false);
        $success = $resendPublicMessage;
      } else {
        $stmt = $db->prepare('SELECT id, first_name, is_verified, verification_token, verification_token_expires FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $normalizedResendEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $mailSent = false;
        if ($user && empty($user['is_verified'])) {
          $verificationToken = trim((string)($user['verification_token'] ?? ''));
          $tokenExpires = (string)($user['verification_token_expires'] ?? '');
          $hasActiveToken = $verificationToken !== '' && $tokenExpires !== '' && strtotime($tokenExpires) > time();

          if (!$hasActiveToken) {
            $verificationToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 86400);
            $update = $db->prepare('UPDATE users SET verification_token = :token, verification_token_expires = :expiry WHERE id = :id');
            $update->execute([
              ':token' => $verificationToken,
              ':expiry' => $tokenExpiry,
              ':id' => $user['id']
            ]);
          }

          $mailResult = sendVerificationEmail($normalizedResendEmail, $user['first_name'] ?: 'User', $verificationToken);
          $mailSent = !empty($mailResult['success']);
        }

        logVerificationAttempt($normalizedResendEmail, 'otp_resend', $mailSent);
        $success = $resendPublicMessage;
      }
    }
  } catch (Exception $e) {
    $error = 'Failed to process request. Please try again.';
    error_log('Resend verification failed: ' . $e->getMessage());
  }
}

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $resendLimitCheck = evaluateOtpResendRateLimit($email);
  if (!empty($resendLimitCheck['limited'])) {
    $resendCooldownRemaining = max($resendCooldownRemaining, (int)($resendLimitCheck['retry_after'] ?? 0));
  }
}

// If token is provided in URL, verify it automatically
if (!empty($token)) {
  if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    logVerificationAttempt($email, 'otp_verify', false);
    $error = 'Verification failed. Please request a new verification email.';
  } else {
    $verifyLimitCheck = evaluateOtpVerifyRateLimit($email);
    if (!empty($verifyLimitCheck['limited'])) {
      logVerificationAttempt($email, 'otp_verify', false);
      $error = 'Too many verification attempts. Please wait before trying again.';
    } else {
      $token_result = $authService->verifyEmailByToken($email, $token);

      if ($token_result['success']) {
        logVerificationAttempt($email, 'otp_verify', true);
        setFlashPageAlert('success', 'Email Verified', 'Email verified successfully. You can now log in.');
        redirect(appPath('login.php'));
      }

      logVerificationAttempt($email, 'otp_verify', false);
      $error = 'Verification failed. Please request a new verification email.';
    }
  }
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

if ($fromLogin) {
  $page_alerts[] = [
    'method' => 'unverifiedLoginAttempt',
    'email' => $email
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
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
          <p style="color: #666; text-align: center; margin: 20px 0;">
            The verification link may have expired or is invalid.
          </p>
          <p style="text-align: center;">
            <a href="<?php echo appPath('register.php'); ?>" class="submit-button" style="display: inline-block; margin-top: 10px;">← Back to Registration</a>
          </p>
        <?php else: ?>
          <div style="text-align: center; padding: 30px 0;">
            <svg style="width: 80px; height: 80px; margin: 0 auto 20px; display: block;" viewBox="0 0 24 24" fill="none" stroke="#3498db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2" />
              <polyline points="22,4 12,13 2,4" />
            </svg>
            <p style="font-size: 16px; color: #555; margin-bottom: 8px;">
              A verification email has been sent to
            </p>
            <p style="font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 20px;">
              <?php echo htmlspecialchars($email); ?>
            </p>
            <p style="font-size: 14px; color: #888; margin-bottom: 24px;">
              Click the link in the email to verify your account. The link expires in 24 hours.
            </p>

            <form id="resend-verification-form" method="POST" action="<?php echo appPath('verify-otp.php', ['email' => $email]); ?>" style="margin: 20px 0;">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
              <input id="verify-email-hidden" type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
              <button
                type="submit"
                id="resend-verification-button"
                name="resend_verification"
                class="submit-button"
                style="display: inline-block;"
                data-resend-button
                data-default-label="Resend Verification Email"
                data-cooldown-seconds="<?php echo (int)$resendCooldownRemaining; ?>"
                <?php echo $resendCooldownRemaining > 0 ? 'disabled' : ''; ?>>
                Resend Verification Email
              </button>
            </form>
            <p class="auth-helper-text" data-resend-status <?php echo $resendCooldownRemaining > 0 ? '' : 'hidden'; ?>></p>

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
