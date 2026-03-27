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
  redirect('/library_betonio/index.php');
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
    // Store email and user_id in session for OTP verification
    $_SESSION['verify_email'] = $email;
    $_SESSION['verify_user_id'] = $result['user_id'];

    // Get the actual OTP from database that was generated during registration
    $query = "SELECT otp_code FROM otp_codes WHERE user_id = :user_id AND is_used = 0 ORDER BY created_at DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':user_id' => $result['user_id']]);
    $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($otp_record) {
      // Send actual OTP via email
      sendOTPEmail($email, $otp_record['otp_code'], $first_name);
    }

    setFlash('success', $result['message']);
    redirect('/library_betonio/verify-otp.php?email=' . urlencode($email));
  } else {
    $error = $result['error'];
  }
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
</body>

</html>