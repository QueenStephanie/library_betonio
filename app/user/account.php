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

$currentPage = 'account';
$accountFullName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
if ($accountFullName === '') {
  $accountFullName = 'Borrower User';
}
$accountInitials = strtoupper(substr((string)($user['first_name'] ?? 'B'), 0, 1) . substr((string)($user['last_name'] ?? 'U'), 0, 1));
$accountStatusLabel = !empty($user['is_verified']) ? 'Verified account' : 'Verification pending';
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
  <link rel="stylesheet" href="public/css/admin.css">
  <link rel="stylesheet" href="public/css/borrower.css">
</head>

<body class="admin-portal-body portal-role-borrower">
  <div class="admin-shell">
    <?php
    $portalRole = 'borrower';
    $portalCurrentPage = 'account';
    $portalIdentityName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
    if ($portalIdentityName === '') {
      $portalIdentityName = 'Borrower User';
    }
    $portalIdentityMeta = (string)($user['email'] ?? '');
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main borrower-main">
      <div class="borrower-page">
        <div class="borrower-shell">
          <section class="borrower-hero borrower-page-hero">
            <div class="borrower-hero-copy">
              <span class="borrower-eyebrow">Account settings</span>
              <h1>Keep your borrower profile current and your password secure.</h1>
              <p class="borrower-page-subtitle">Manage your visible borrower details and update credentials from one screen.</p>
            </div>
            <aside class="borrower-hero-card borrower-account-summary">
              <span class="borrower-account-avatar" aria-hidden="true"><?php echo htmlspecialchars($accountInitials, ENT_QUOTES, 'UTF-8'); ?></span>
              <strong><?php echo htmlspecialchars($accountFullName, ENT_QUOTES, 'UTF-8'); ?></strong>
              <p><?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
              <ul class="borrower-hero-list">
                <li><?php echo htmlspecialchars($accountStatusLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Password changes apply to your next login session</li>
              </ul>
            </aside>
          </section>

          <?php if ($flash): ?>
            <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
              <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="borrower-alert borrower-alert-error" role="status" aria-live="polite">
              <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
          <?php endif; ?>

          <div class="settings-grid">
            <section class="settings-card borrower-card borrower-surface-card">
              <div class="borrower-panel-heading">
                <div>
                  <span class="borrower-section-kicker">Profile</span>
                  <h2>Profile information</h2>
                </div>
              </div>
              <div class="borrower-panel-content">
                <form method="POST" action="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="update_profile">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($accountCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars((string)$user['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
              <p class="settings-help-text">Contact support to change your email address.</p>
            </div>

                  <button type="submit" class="borrower-btn borrower-btn-primary settings-submit-btn">Save Changes</button>
                </form>
              </div>
            </section>

            <section class="settings-card borrower-card borrower-surface-card">
              <div class="borrower-panel-heading">
                <div>
                  <span class="borrower-section-kicker">Security</span>
                  <h2>Change password</h2>
                </div>
              </div>
              <div class="borrower-panel-content">
                <form method="POST" action="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($accountCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="form-group">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            </div>

            <div class="form-group">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" autocomplete="new-password" placeholder="At least 8 characters" required>
            </div>

            <div class="form-group">
              <label for="new_password_confirm">Confirm New Password</label>
              <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
            </div>

                  <button type="submit" class="borrower-btn borrower-btn-primary settings-submit-btn">Change Password</button>
                </form>
              </div>
            </section>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="public/js/main.js"></script>
  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
