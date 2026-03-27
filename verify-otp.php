<?php

/**
 * OTP Verification Page & Handler
 * Verifies the OTP sent to user's email
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';

if (empty($email)) {
  redirect('/library_betonio/register.php');
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize(getPost('email'));
  $otp = sanitize(getPost('otp'));

  // Validate input
  if (empty($email)) {
    $error = 'Email is required';
  } elseif (empty($otp)) {
    $error = 'Verification code is required';
  } elseif (strlen($otp) !== 6 || !ctype_digit($otp)) {
    $error = 'Verification code must be 6 digits';
  } else {
    // Combine OTP boxes if they were submitted individually
    if (isset($_POST['otp_1'])) {
      $combined_otp = '';
      for ($i = 1; $i <= 6; $i++) {
        if (isset($_POST["otp_$i"])) {
          $combined_otp .= sanitize($_POST["otp_$i"]);
        }
      }
      if (!empty($combined_otp)) {
        $otp = $combined_otp;
      }
    }

    $auth = new AuthManager($db);
    $result = $auth->verifyOTP($email, $otp);

    if ($result['success']) {
      // Double-check that user is now verified
      $check_query = "SELECT is_verified FROM users WHERE email = :email";
      $check_stmt = $db->prepare($check_query);
      $check_stmt->execute([':email' => $email]);
      $check_user = $check_stmt->fetch(PDO::FETCH_ASSOC);

      if ($check_user && $check_user['is_verified']) {
        setFlash('success', 'Email verified! You can now log in.');
        redirect('/library_betonio/login.php');
      } else {
        $error = 'Verification process failed. Please try again.';
      }
    } else {
      $error = $result['error'];
    }
  }
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
  <link rel="stylesheet" href="public/css/auth.css">
  <style>
    .otp-input-group {
      display: flex;
      gap: 8px;
      justify-content: center;
      margin: 20px 0;
    }

    .otp-box {
      width: 50px;
      height: 50px;
      font-size: 24px;
      text-align: center;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-weight: bold;
    }

    .otp-box:focus {
      outline: none;
      border-color: #333;
      background-color: #f9f9f9;
    }
  </style>
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
        <h1>Verify Your Email</h1>
        <p class="subtitle">We sent a verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert" style="background-color: #fee; border-left: 4px solid #f44; padding: 12px;  border-radius: 4px; margin-bottom: 16px;">
            <strong>✗ Error:</strong> <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <?php
        $flash = getFlash();
        if ($flash):
        ?>
          <div class="alert alert-<?php echo $flash['type']; ?>" role="alert" style="background-color: #efe; border-left: 4px solid #484; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
            ✓ <?php echo htmlspecialchars($flash['message']); ?>
          </div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="verify-otp.php">
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

          <label for="otp">Verification Code</label>
          <p style="color: #666; font-size: 13px; margin: 6px 0 12px;">
            de Find the 6-digit code in your email and enter it below
          </p>
          <input id="otp" name="otp" type="text" placeholder="000000" maxlength="6"
            pattern="[0-9]{6}" required inputmode="numeric">

          <button class="submit-button" type="submit">Verify Email</button>
        </form>

        <div class="auth-links" style="text-align: center;">
          <p style="color: #999; font-size: 13px; margin-bottom: 12px;">
            Didn't get the email? Check your spam folder or <a href="register.php" style="color: #3498db;">register again</a>
          </p>
          <p><a href="register.php">← Back to Registration</a></p>
        </div>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
</body>

</html>