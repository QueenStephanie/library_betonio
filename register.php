<?php

/**
 * Registration Page & Handler
 * Handles both GET (display form) and POST (process registration)
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
  redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = sanitize(getPost('first_name'));
  $last_name = sanitize(getPost('last_name'));
  $email = sanitize(getPost('email'));
  $password = getPost('password');
  $password_confirm = getPost('password_confirm');

  $auth = new AuthManager($db);
  $result = $auth->register($first_name, $last_name, $email, $password, $password_confirm);

  if ($result['success']) {
    $mail_result = sendVerificationEmail(
      $email,
      $first_name,
      $result['verification_token']
    );

    if (empty($mail_result['success'])) {
      try {
        $rollback = $db->prepare('DELETE FROM users WHERE id = :id AND is_verified = 0');
        $rollback->execute([':id' => $result['user_id']]);
      } catch (Exception $rollback_error) {
        error_log('Registration rollback failed: ' . $rollback_error->getMessage());
      }

      $error = $mail_result['error'] ?? 'Registration failed because verification email could not be sent.';
      $error .= ' Please update MAIL_USER / MAIL_PASS in .env and try again.';
      $page_alerts = [];
    } else {
      // Store email in session for verification page
      $_SESSION['verify_email'] = $email;
      $_SESSION['verify_user_id'] = $result['user_id'];

      // Set flag to show SweetAlert on page load
      $_SESSION['show_registration_alert'] = true;
      redirect(appPath('register.php', ['success' => 1]));
    }
  } else {
    $error = $result['error'];
  }
}

// Check if registration was successful
$show_success_alert = isset($_GET['success']) && $_GET['success'] === '1' && isset($_SESSION['show_registration_alert']);
if ($show_success_alert) {
  unset($_SESSION['show_registration_alert']);
}

$page_alerts = [];
if ($show_success_alert) {
  $verify_email = $_SESSION['verify_email'] ?? '';
  $page_alerts[] = [
    'method' => 'registrationSuccess',
    'redirect' => appPath('verify-otp.php', ['email' => $verify_email])
  ];
}

if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Registration Failed',
    'message' => $error
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Register</title>
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
        <p class="visual-quote">"Reading is to the mind what exercise is to the body."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card">
        <h1>Create your account</h1>
        <p class="subtitle">Join QueenLib and start exploring your library.</p>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="register.php">
          <label for="first_name">First Name</label>
          <input id="first_name" name="first_name" type="text" placeholder="Jane" autocomplete="given-name"
            required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">

          <label for="last_name">Last Name</label>
          <input id="last_name" name="last_name" type="text" placeholder="Austen" autocomplete="family-name"
            required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">

          <label for="email">Email Address</label>
          <input id="email" name="email" type="email" placeholder="jane@example.com" autocomplete="email"
            required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" placeholder="At least 8 characters"
              autocomplete="new-password" required>
            <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
              </svg>
            </button>
          </div>

          <label for="password_confirm">Confirm Password</label>
          <div class="password-field">
            <input id="password_confirm" name="password_confirm" type="password" placeholder="Repeat your password"
              autocomplete="new-password" required>
            <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
              </svg>
            </button>
          </div>

          <button class="submit-button" type="submit">Create Account</button>
        </form>

        <div class="auth-links">
          <p>Already have an account? <a class="login-link" href="login.php">Log in →</a></p>
        </div>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>