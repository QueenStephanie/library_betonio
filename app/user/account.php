<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Account Settings Page
 * Protected page - requires login
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/includes/services/AccountService.php';

// Require login
requireLogin();
PermissionGate::requireFrontendRole('borrower', 'index.php');
checkSessionTimeout();

$auth = new AuthManager($db);
$user = $auth->getCurrentUser();
$error = '';
$success = '';
$accountCsrfScope = 'account_settings';
$accountCsrfToken = getPublicCsrfToken($accountCsrfScope);
$accountService = new AccountService($db);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('account_settings_post');
  $submittedToken = getPost('csrf_token');
  if (!$originCheck['valid']) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    error_log('Blocked account settings POST due to origin validation: ' . json_encode($originCheck));
    clearPublicCsrfToken($accountCsrfScope);
    $accountCsrfToken = getPublicCsrfToken($accountCsrfScope);
    $error = 'Security check failed. Please refresh the page and try again.';
  } elseif (!validatePublicCsrfToken($submittedToken, $accountCsrfScope)) {
    logVerificationAttempt((string)($user['email'] ?? ''), 'csrf_reject', false);
    clearPublicCsrfToken($accountCsrfScope);
    $accountCsrfToken = getPublicCsrfToken($accountCsrfScope);
    $error = 'Invalid or missing security token. Please refresh the page and try again.';
  } else {
    $action = getPost('action');

    if ($action === 'update_profile') {
      $first_name = getPost('first_name');
      $last_name = getPost('last_name');

      $result = $accountService->updateProfile((int)($_SESSION['user_id'] ?? 0), $first_name, $last_name);
      if (!empty($result['success'])) {
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
        $_SESSION['show_profile_success'] = true;
        $auth = new AuthManager($db);
        $user = $auth->getCurrentUser();
      } else {
        $error = (string)($result['error'] ?? 'Failed to update profile');
      }
    }

    if ($action === 'change_password') {
      $current_password = getPost('current_password');
      $new_password = getPost('new_password');
      $new_password_confirm = getPost('new_password_confirm');

      $result = $accountService->changePassword(
        (int)($_SESSION['user_id'] ?? 0),
        $current_password,
        $new_password,
        $new_password_confirm
      );

      if (!empty($result['success'])) {
        $_SESSION['show_password_success'] = true;
      } else {
        $error = (string)($result['error'] ?? 'Failed to change password');
      }
    }
  }
}

$flash = getFlash();

$page_alerts = [];
if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Error',
    'message' => $error
  ];
}

if (isset($_SESSION['show_profile_success'])) {
  unset($_SESSION['show_profile_success']);
  $page_alerts[] = [
    'method' => 'profileUpdatedSuccess'
  ];
}

if (isset($_SESSION['show_password_success'])) {
  unset($_SESSION['show_password_success']);
  $page_alerts[] = [
    'method' => 'passwordChangedSuccess'
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Account Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/borrower.css">
  <style>
    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 20px;
    }

    .settings-card {
      padding: 24px;
    }

    .settings-card h2 {
      font-size: 1.5rem;
      margin-bottom: 24px;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .settings-card h2::before {
      content: '';
      width: 4px;
      height: 24px;
      background: var(--accent);
      border-radius: 2px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group:last-child {
      margin-bottom: 0;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 0.95rem;
      margin-bottom: 8px;
      color: var(--text);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid var(--line);
      border-radius: 12px;
      font-family: inherit;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #fafaf8;
    }

    .form-group input:hover {
      border-color: #ddd;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--accent);
      background: white;
      box-shadow: 0 0 0 3px rgba(210, 71, 24, 0.1);
    }

    .form-group input:disabled {
      background-color: #f5f5f5;
      color: var(--muted);
      cursor: not-allowed;
    }

    .back-link-section {
      text-align: center;
      padding: 28px 0 8px;
      border-top: 1px solid var(--line);
      margin-top: 24px;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      color: var(--accent);
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .back-link:hover {
      gap: 12px;
      transform: translateX(-4px);
    }

    @media (max-width: 768px) {
      .settings-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .settings-card {
        padding: 20px;
      }
    }
  </style>
</head>

<body>
  <nav class="borrower-navbar" aria-label="Borrower navigation">
    <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-brand">QueenLib</a>
    <div class="borrower-nav-right">
      <span class="borrower-greeting">Welcome, <?php echo htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8'); ?>!</span>
      <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link">Dashboard</a>
      <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link">Catalog</a>
      <a href="<?php echo htmlspecialchars(appPath('reservations.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link">Reservations</a>
      <a href="<?php echo htmlspecialchars(appPath('history.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link">Loan History</a>
      <a href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link is-active" aria-current="page">Settings</a>
      <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link is-logout">Logout</a>
    </div>
  </nav>

  <main class="borrower-page">
    <div class="borrower-shell">
      <header class="borrower-page-header">
      <h1>Account Settings</h1>
      <p class="borrower-page-subtitle">Manage your profile and security preferences</p>
      </header>

    <?php if ($flash): ?>
      <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
        <?php echo htmlspecialchars($flash['message']); ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div class="settings-grid">
      <section class="settings-card borrower-card">
        <h2>Profile Information</h2>
        <form method="POST" action="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="update_profile">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($accountCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name"
              value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name"
              value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
              value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            <p style="font-size: 0.85rem; color: var(--muted); margin-top: 6px; font-style: italic;">
              Contact support to change your email
            </p>
          </div>

          <button type="submit" class="borrower-btn borrower-btn-primary" style="margin-top: 24px; width: 100%;">Save Changes</button>
        </form>
      </section>

      <section class="settings-card borrower-card">
        <h2>Change Password</h2>
        <form method="POST" action="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="change_password">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($accountCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password"
              autocomplete="current-password" required>
          </div>

          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password"
              autocomplete="new-password" placeholder="At least 8 characters" required>
          </div>

          <div class="form-group">
            <label for="new_password_confirm">Confirm New Password</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm"
              autocomplete="new-password" required>
          </div>

          <button type="submit" class="borrower-btn borrower-btn-primary" style="margin-top: 24px; width: 100%;">Change Password</button>
        </form>
      </section>
    </div>

    <div class="back-link-section">
      <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="back-link">
        <span>←</span>
        <span>Back to Dashboard</span>
      </a>
    </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <p>&copy; 2026 QueenLib. All rights reserved.</p>
  </footer>

  <!-- Scripts -->
  <script src="public/js/main.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
