<?php

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

$flash = getFlash();
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
</head>

<body>
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

  <main class="container">
    <div class="account-page">
      <h1>Account Settings</h1>

      <?php if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>" role="alert">
          <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success" role="alert">✅ <?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="settings-grid">
        <!-- Profile Settings -->
        <section class="settings-card">
          <h2>Profile Information</h2>
          <form method="POST" action="account.php">
            <input type="hidden" name="action" value="update_profile">

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
              <label for="email">Email (Cannot be changed)</label>
              <input type="email" id="email" name="email"
                value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
          </form>
        </section>

        <!-- Change Password -->
        <section class="settings-card">
          <h2>Change Password</h2>
          <form method="POST" action="account.php">
            <input type="hidden" name="action" value="change_password">

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

            <button type="submit" class="btn btn-primary">Change Password</button>
          </form>
        </section>
      </div>

      <div class="back-link">
        <a href="index.php">← Back to Dashboard</a>
      </div>
    </div>
  </main>

  <footer class="footer">
    <p>&copy; 2026 QueenLib. All rights reserved.</p>
  </footer>

  <script src="public/js/main.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <!-- SweetAlert Configuration -->
  <script src="/library_betonio/public/js/sweetalert-config.js"></script>

  <script>
    // Show error alert if there's an error
    <?php if ($error): ?>
      SweetAlerts.error('Error', '<?php echo addslashes($error); ?>');
    <?php endif; ?>

    // Show profile updated alert
    <?php if (isset($_SESSION['show_profile_success'])): ?>
      <?php unset($_SESSION['show_profile_success']); ?>
      SweetAlerts.profileUpdatedSuccess();
    <?php endif; ?>

    // Show password changed alert
    <?php if (isset($_SESSION['show_password_success'])): ?>
      <?php unset($_SESSION['show_password_success']); ?>
      SweetAlerts.passwordChangedSuccess();
    <?php endif; ?>
  </script>
</body>

</html>