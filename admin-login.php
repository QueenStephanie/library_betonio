<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

if (isAdminAuthenticated()) {
  if (isActiveAdminSession()) {
    redirect('admin-users.php');
  }

  unset($_SESSION['admin_authenticated']);
  unset($_SESSION['admin_username']);
  unset($_SESSION['admin_last_login']);
  unset($_SESSION['admin_auth_mode']);
  unset($_SESSION['admin_credential_id']);
  unset($_SESSION['show_admin_welcome']);
  unset($_SESSION['admin_is_superadmin']);
  clearAdminCsrfToken();
}

$error = '';
$submitted_username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = getPost('username');
  $password = getPost('password');

  $result = resolveAdminLoginAttempt($username, $password);

  if (!empty($result['success'])) {
    $previousSessionId = session_id();
    session_regenerate_id(true);

    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = $result['admin_identity'];
    $_SESSION['admin_auth_mode'] = $result['auth_mode'];
    $_SESSION['admin_last_login'] = time();
    if (isset($result['admin_credential_id']) && $result['admin_credential_id'] !== null) {
      $_SESSION['admin_credential_id'] = (int)$result['admin_credential_id'];
    } else {
      unset($_SESSION['admin_credential_id']);
    }
    $_SESSION['admin_is_superadmin'] = isConfiguredSuperadminIdentity($_SESSION['admin_username']);
    $_SESSION['show_admin_welcome'] = true;

    clearAdminCsrfToken();
    AuthSupport::refreshAdminSessionRegistry($db, [
      'admin_identity' => $_SESSION['admin_username'],
      'admin_credential_id' => $_SESSION['admin_credential_id'] ?? null,
      'auth_mode' => $_SESSION['admin_auth_mode'],
    ], $previousSessionId);

    redirect('admin-users.php');
  }

  $errorCode = $result['error_code'] ?? '';
  if ($errorCode === 'missing_bootstrap_credentials') {
    $error = 'Admin login is blocked because bootstrap credentials are missing.';
  } elseif ($errorCode === 'unsafe_bootstrap_credentials') {
    $error = 'Admin login is blocked because bootstrap credentials are unsafe for this environment.';
  } elseif ($errorCode === 'missing_login_fields') {
    $error = 'Username and password are required.';
  } else {
    $error = $result['error'] ?? 'Invalid admin credentials.';
  }
}

$page_alerts = [];
if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Admin Login Failed',
    'message' => $error
  ];
}

$credential_state = getAdminCredentialSourceState();
$state_mode = $credential_state['mode'] ?? 'blocked';
$state_title = 'Credential state unavailable';
$state_message = 'Admin credential source could not be resolved.';
$state_class = 'status-blocked';

if ($state_mode === 'db') {
  $state_title = 'DB credentials active';
  $state_message = 'Admin login is currently validated against database credentials.';
  $state_class = 'status-db';
} elseif ($state_mode === 'bootstrap_env') {
  $state_title = 'Bootstrap mode active';
  $state_message = 'No active DB admin credential found. Environment credentials are currently accepted.';
  $state_class = 'status-bootstrap';
} else {
  $state_title = 'Login currently blocked';
  $state_message = $credential_state['error'] ?? 'Admin credential configuration is missing or unsafe.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Admin Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/auth.css">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body>
  <main class="auth-page">
    <section class="auth-visual">
      <div class="visual-overlay"></div>
      <div class="visual-content">
        <a class="visual-brand" href="index.php">QueenLib</a>
        <p class="visual-quote">"Administrative access for trusted library operations."</p>
      </div>
    </section>

    <section class="auth-panel">
      <div class="auth-card auth-card-compact">
        <h1>Admin access</h1>
        <p class="subtitle">Sign in with administrator credentials.</p>

        <div class="auth-status-panel <?php echo htmlspecialchars($state_class); ?>" role="status" aria-live="polite">
          <p class="auth-status-title"><?php echo htmlspecialchars($state_title); ?></p>
          <p class="auth-status-message"><?php echo htmlspecialchars($state_message); ?></p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="admin-login.php">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" autocomplete="username" placeholder="admin" required value="<?php echo htmlspecialchars($submitted_username); ?>" autofocus>

          <label for="password">Password</label>
          <div class="password-field">
            <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter admin password" required>
            <button class="toggle-password" type="button" aria-label="Show password" aria-pressed="false">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5c5.23 0 9.27 4.57 10 6c-.73 1.43-4.77 6-10 6S2.73 12.43 2 11c.73-1.43 4.77-6 10-6Zm0 2c-3.93 0-7.16 3.11-7.88 4c.72.89 3.95 4 7.88 4s7.16-3.11 7.88-4C19.16 10.11 15.93 7 12 7Zm0 1.5A3.5 3.5 0 1 1 8.5 12A3.5 3.5 0 0 1 12 8.5Zm0 2A1.5 1.5 0 1 0 13.5 12A1.5 1.5 0 0 0 12 10.5Z" />
              </svg>
            </button>
          </div>

          <button class="submit-button" type="submit">Log In as Admin</button>
        </form>

        <div class="auth-links">
          <p>Back to user login? <a class="login-link" href="login.php">User Login →</a></p>
        </div>
      </div>
    </section>
  </main>

  <script src="public/js/auth.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>