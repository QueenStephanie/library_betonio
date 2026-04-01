<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true) {
  redirect('admin-dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = getPost('username');
  $password = getPost('password');

  if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_username'] = 'Administrator';
    $_SESSION['show_admin_welcome'] = true;

    redirect('admin-dashboard.php');
  }

  $error = 'Invalid admin credentials.';
}

$page_alerts = [];
if ($error) {
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Admin Login Failed',
    'message' => $error
  ];
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

        <?php if ($error): ?>
          <div class="alert alert-error" role="alert">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="admin-login.php">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" autocomplete="username" placeholder="admin" required>

          <label for="password">Password</label>
          <input id="password" name="password" type="password" autocomplete="current-password" placeholder="admin123" required>

          <button class="submit-button" type="submit">Log In as Admin</button>
        </form>

        <div class="auth-links">
          <p>Back to user login? <a class="login-link" href="login.php">User Login →</a></p>
        </div>
      </div>
    </section>
  </main>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>
