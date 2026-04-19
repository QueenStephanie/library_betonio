<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/includes/services/AdminPasswordService.php';

PermissionGate::requirePageAccess('admin-change-password');

$page_alerts = [];
$csrf_token = getAdminCsrfToken();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserEmail = (string)($_SESSION['user_email'] ?? '');
$isSuperadmin = isCurrentAdminSuperadmin();
$adminRoleLabel = $isSuperadmin ? 'Super Administrator' : 'Administrator';
$adminPasswordService = new AdminPasswordService($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('admin_change_password_post');
  $submittedToken = $_POST['csrf_token'] ?? '';
  $currentPassword = getPost('current_password');
  $newPassword = getPost('new_password');
  $confirmPassword = getPost('confirm_password');
  $errorMessage = null;

  if (!$originCheck['valid']) {
    logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
    error_log('Blocked admin-change-password POST due to origin validation: ' . json_encode($originCheck));
    $errorMessage = 'Origin validation failed. Please refresh and try again.';
  } elseif (!validateAdminCsrfToken($submittedToken)) {
    logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
    $errorMessage = 'Invalid or missing security token. Please refresh and try again.';
  } else {
    $result = $adminPasswordService->changePassword($currentUserId, $currentPassword, $newPassword, $confirmPassword);
    if (!empty($result['success'])) {
      session_regenerate_id(true);
      $_SESSION['login_time'] = time();

      clearAdminCsrfToken();
      $csrf_token = getAdminCsrfToken();

      $page_alerts[] = [
        'type' => 'success',
        'title' => 'Password Updated',
        'message' => (string)($result['message'] ?? 'Password updated successfully.')
      ];
    } else {
      $errorMessage = (string)($result['error'] ?? 'Unable to update password at this time.');
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
    <?php
    $portalRole = 'admin';
    $portalCurrentPage = 'change-password';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $adminRoleLabel;
    $portalBrandSub = 'Admin Portal';
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>Change Password</h1>
        <p>Update your account password</p>
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
