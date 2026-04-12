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

// Require login
requireLogin();
checkSessionTimeout();

$auth = new AuthManager($db);
$user = $auth->getCurrentUser();
$error = '';
$success = '';
$accountCsrfScope = 'account_settings';
$accountCsrfToken = getPublicCsrfToken($accountCsrfScope);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $submittedToken = getPost('csrf_token');
  if (!validatePublicCsrfToken($submittedToken, $accountCsrfScope)) {
    clearPublicCsrfToken($accountCsrfScope);
    $accountCsrfToken = getPublicCsrfToken($accountCsrfScope);
    $error = 'Invalid or missing security token. Please refresh the page and try again.';
  } else {
    $action = getPost('action');

    if ($action === 'update_profile') {
      $first_name = sanitize(getPost('first_name'));
      $last_name = sanitize(getPost('last_name'));

      try {
        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([
          ':first_name' => $first_name,
          ':last_name' => $last_name,
          ':id' => $_SESSION['user_id']
        ]);

        // Update session
        $_SESSION['user_name'] = $first_name . ' ' . $last_name;

        $_SESSION['show_profile_success'] = true;
        $auth = new AuthManager($db);
        $user = $auth->getCurrentUser();
      } catch (Exception $e) {
        $error = 'Failed to update profile';
      }
    }

    if ($action === 'change_password') {
      $current_password = getPost('current_password');
      $new_password = getPost('new_password');
      $new_password_confirm = getPost('new_password_confirm');

      if (empty($current_password) || empty($new_password)) {
        $error = 'All password fields are required';
      } elseif ($new_password !== $new_password_confirm) {
        $error = 'New passwords do not match';
      } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters';
      } else {
        try {
          // Get current password hash
          $query = "SELECT password_hash FROM users WHERE id = :id";
          $stmt = $db->prepare($query);
          $stmt->execute([':id' => $_SESSION['user_id']]);
          $result = $stmt->fetch(PDO::FETCH_ASSOC);

          // Verify current password
          if (!password_verify($current_password, $result['password_hash'])) {
            $error = 'Current password is incorrect';
          } else {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
            $query = "UPDATE users SET password_hash = :password WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
              ':password' => $new_hash,
              ':id' => $_SESSION['user_id']
            ]);

            $_SESSION['show_password_success'] = true;
          }
        } catch (Exception $e) {
          $error = 'Failed to change password';
        }
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
  <link rel="stylesheet" href="public/css/dashboard.css">
  <style>
    /* Account Page Specific Styles */
    .account-header {
      background: linear-gradient(135deg, #f5f0e6 0%, #fff9f5 100%);
      padding: 40px 0;
      border-bottom: 1px solid var(--line);
      margin-bottom: 40px;
    }

    .account-header h1 {
      font-size: 2.8rem;
      margin-bottom: 8px;
      color: var(--text);
    }

    .account-header p {
      font-size: 1.1rem;
      color: var(--muted);
    }

    .account-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 24px;
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 32px;
      margin-bottom: 48px;
    }

    .settings-card {
      background: white;
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 2px 8px rgba(43, 28, 16, 0.04);
      transition: all 0.3s ease;
    }

    .settings-card:hover {
      box-shadow: 0 8px 24px rgba(43, 28, 16, 0.08);
      border-color: var(--accent);
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

    .btn {
      display: inline-block;
      padding: 12px 28px;
      border: none;
      border-radius: 12px;
      font: inherit;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-align: center;
      width: 100%;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent), #b83d14);
      color: white;
      box-shadow: 0 4px 12px rgba(210, 71, 24, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(210, 71, 24, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .back-link-section {
      text-align: center;
      padding: 40px 0;
      border-top: 1px solid var(--line);
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

    .alert {
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      border-left: 4px solid;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-success {
      background: var(--success-bg);
      color: var(--success-text);
      border-left-color: #5d8049;
    }

    .alert-error {
      background: #fff2ef;
      color: #a62f0d;
      border-left-color: #d24718;
    }

    .alert::before {
      font-size: 1.3rem;
    }

    .alert-success::before {
      content: '✓';
    }

    .alert-error::before {
      content: '✕';
    }

    @media (max-width: 768px) {
      .account-header {
        padding: 24px 0;
        margin-bottom: 24px;
      }

      .account-header h1 {
        font-size: 2rem;
      }

      .settings-grid {
        grid-template-columns: 1fr;
        gap: 24px;
        margin-bottom: 24px;
      }

      .settings-card {
        padding: 24px;
      }

      .btn {
        padding: 14px 20px;
        font-size: 0.95rem;
      }
    }
  </style>
</head>

<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="navbar-brand">
      <a href="index.php" class="logo">QueenLib</a>
    </div>
    <div class="navbar-menu">
      <div class="user-menu">
        <span class="user-greeting">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</span>
        <a href="index.php" class="nav-link">Dashboard</a>
        <a href="logout.php" class="nav-link logout">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Page Header -->
  <div class="account-header">
    <div class="account-container">
      <h1>Account Settings</h1>
      <p>Manage your profile and security preferences</p>
    </div>
  </div>

  <!-- Main Content -->
  <main class="account-container">
    <!-- Flash Messages -->
    <?php if ($flash): ?>
      <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
        <?php echo htmlspecialchars($flash['message']); ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <!-- Settings Grid -->
    <div class="settings-grid">
      <!-- Profile Settings Card -->
      <section class="settings-card">
        <h2>Profile Information</h2>
        <form method="POST" action="account.php">
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

          <button type="submit" class="btn btn-primary" style="margin-top: 24px;">Save Changes</button>
        </form>
      </section>

      <!-- Change Password Card -->
      <section class="settings-card">
        <h2>Change Password</h2>
        <form method="POST" action="account.php">
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

          <button type="submit" class="btn btn-primary" style="margin-top: 24px;">Change Password</button>
        </form>
      </section>
    </div>

    <!-- Back Link -->
    <div class="back-link-section">
      <a href="index.php" class="back-link">
        <span>←</span>
        <span>Back to Dashboard</span>
      </a>
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