<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireAdminAuth();

$page_alerts = [];
$csrf_token = getAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = $_POST['csrf_token'] ?? '';
  $currentPassword = getPost('current_password');
  $newPassword = getPost('new_password');
  $confirmPassword = getPost('confirm_password');
  $errorMessage = null;

  if (!validateAdminCsrfToken($submittedToken)) {
    $errorMessage = 'Invalid or missing security token. Please refresh and try again.';
  } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $errorMessage = 'All password fields are required.';
  } elseif (strlen($newPassword) < 8) {
    $errorMessage = 'New password must be at least 8 characters long.';
  } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/\d/', $newPassword) || !preg_match('/[^a-zA-Z\d]/', $newPassword)) {
    $errorMessage = 'New password must include uppercase, lowercase, number, and special character.';
  } elseif ($newPassword !== $confirmPassword) {
    $errorMessage = 'New password and confirmation do not match.';
  } elseif (hash_equals($currentPassword, $newPassword)) {
    $errorMessage = 'New password must be different from the current password.';
  } else {
    $adminIdentity = $_SESSION['admin_username'] ?? ADMIN_USERNAME;
    $sessionAuthMode = $_SESSION['admin_auth_mode'] ?? '';

    $verification = verifyAdminCurrentPassword($adminIdentity, $currentPassword, $sessionAuthMode);
    if (empty($verification['success'])) {
      $errorMessage = $verification['error'] ?? 'Current password verification failed.';
    } else {
      $persistResult = updateAdminPassword($adminIdentity, $newPassword);
      if (empty($persistResult['success'])) {
        $errorMessage = $persistResult['error'] ?? 'Unable to update password at this time.';
      } else {
        $oldSessionId = session_id();
        AuthSupport::invalidateOtherAdminSessions($db, $persistResult['admin_identity'], $oldSessionId);

        session_regenerate_id(true);

        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_username'] = $persistResult['admin_identity'];
        $_SESSION['admin_auth_mode'] = 'db';
        $_SESSION['admin_password_changed_at'] = time();
        if (isset($persistResult['admin_credential_id']) && $persistResult['admin_credential_id'] !== null) {
          $_SESSION['admin_credential_id'] = (int)$persistResult['admin_credential_id'];
        } else {
          unset($_SESSION['admin_credential_id']);
        }

        clearAdminCsrfToken();
        $csrf_token = getAdminCsrfToken();

        AuthSupport::refreshAdminSessionRegistry($db, [
          'admin_identity' => $_SESSION['admin_username'],
          'admin_credential_id' => $_SESSION['admin_credential_id'] ?? null,
          'auth_mode' => $_SESSION['admin_auth_mode'],
        ], $oldSessionId);

        $page_alerts[] = [
          'type' => 'success',
          'title' => 'Password Updated',
          'message' => 'Password updated successfully. Other active admin sessions were signed out.'
        ];
      }
    }
  }

  if ($errorMessage !== null) {
    $page_alerts[] = [
      'type' => 'error',
      'title' => 'Password Update Failed',
      'message' => $errorMessage
    ];
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Change Password</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/admin.css">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-brand-wrap">
        <div class="admin-brand">Libris</div>
        <div class="admin-brand-sub">Admin Portal</div>
      </div>

      <div class="admin-sidebar-profile">
        <span class="admin-sidebar-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <div class="admin-sidebar-name">Admin</div>
          <div class="admin-sidebar-role">System Administrator</div>
        </div>
      </div>

      <nav class="admin-nav">
        <a class="admin-nav-item" href="admin-dashboard.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 10.5L12 3L21 10.5V21H3V10.5Z" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Dashboard</span>
        </a>
        <a class="admin-nav-item" href="admin-users.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 13C18.21 13 20 11.21 20 9C20 6.79 18.21 5 16 5" stroke="currentColor" stroke-width="1.6" />
            <path d="M4 20C4.9 17.3 7.7 15.5 11 15.5C14.3 15.5 17.1 17.3 18 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M11 13C13.21 13 15 11.21 15 9C15 6.79 13.21 5 11 5C8.79 5 7 6.79 7 9C7 11.21 8.79 13 11 13Z" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>User Management</span>
        </a>
        <a class="admin-nav-item" href="admin-profile.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.6" />
            <path d="M4.5 20C5.4 17.3 8.1 15.5 12 15.5C15.9 15.5 18.6 17.3 19.5 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
          </svg>
          <span>Profile</span>
        </a>
        <a class="admin-nav-item is-active" href="admin-change-password.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 10V8C6 5.79 7.79 4 10 4H14C16.21 4 18 5.79 18 8V10" stroke="currentColor" stroke-width="1.6" />
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Change Password</span>
        </a>
        <a class="admin-nav-item" href="admin-fines.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 5H20V19H4V5Z" stroke="currentColor" stroke-width="1.6" />
            <path d="M8 14L11 11L13 13L16 10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          <span>Fines Report</span>
        </a>
        <a class="admin-nav-item admin-nav-logout" href="admin-logout.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M15 7L20 12L15 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M20 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M4 4H9V20H4" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Log Out</span>
        </a>
      </nav>
    </aside>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Change Password</h1>
        <p>Update your administrator account password</p>
      </header>

      <section class="admin-card admin-password-card">
        <form class="admin-form-grid" method="POST" action="admin-change-password.php">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
          <div class="admin-form-field">
            <label for="current_password">Current Password</label>
            <input id="current_password" name="current_password" type="password" placeholder="Enter current password" required>
          </div>
          <div class="admin-form-field">
            <label for="new_password">New Password</label>
            <input id="new_password" name="new_password" type="password" placeholder="Enter new password" required>
          </div>
          <div class="admin-form-field">
            <label for="confirm_password">Confirm New Password</label>
            <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm new password" required>
          </div>
          <button class="admin-button admin-button-primary" type="submit">Update Password</button>
        </form>

        <div class="admin-security-note">
          <strong>Security Notice:</strong> Choose a strong password that you do not use elsewhere. A password manager is recommended for secure storage.
        </div>
      </section>
    </main>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>