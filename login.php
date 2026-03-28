<?php

/**
 * Login Page & Handler
 * Handles both GET (display form) and POST (process login)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
  redirect('/library_betonio/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = sanitize(getPost('email'));
  $password = getPost('password');

  $auth = new AuthManager($db);
  $result = $auth->login($email, $password);

  if ($result['success']) {
    setFlash('success', $result['message']);
    redirect('/library_betonio/index.php');
  } else {
    if (isset($result['unverified']) && $result['unverified']) {
      redirect('/library_betonio/verify-otp.php?email=' . urlencode($result['email']));
    }
    $error = $result['error'];
  }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
  $_SESSION['show_timeout_alert'] = true;
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

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="login.php">
          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" placeholder="jane@example.com"
            autocomplete="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="Your password"
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
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <!-- SweetAlert Configuration -->
  <script src="/library_betonio/public/js/sweetalert-config.js"></script>

  <script>
    // Show error alert if there's an error
    <?php if ($error): ?>
      SweetAlerts.error('Login Failed', '<?php echo addslashes($error); ?>');
    <?php endif; ?>

    // Show timeout alert if session expired
    <?php if (isset($_SESSION['show_timeout_alert'])): ?>
      <?php unset($_SESSION['show_timeout_alert']); ?>
      SweetAlerts.warning(
        'Session Expired',
        'Your session has expired. Please log in again.',
        'OK',
        null,
        function() {}
      );
    <?php endif; ?>
  </script>
</body>

</html>