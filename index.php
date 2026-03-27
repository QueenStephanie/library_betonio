<?php

/**
 * Main Dashboard / Homepage
 * Serves as both login redirect and logged-in dashboard
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check session timeout
if (isset($_SESSION['user_id'])) {
  checkSessionTimeout();
}

$is_logged_in = isset($_SESSION['user_id']);
if ($is_logged_in) {
  $auth = new AuthManager($db);
  $user = $auth->getCurrentUser();
} else {
  $user = null;
}
$flash = getFlash();

// If logged in, show dashboard view, else show landing page
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $is_logged_in ? 'QueenLib | My Account' : 'QueenLib'; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/dashboard.css">
</head>

<body>

  <?php if ($is_logged_in && $user): ?>
    <!-- LOGGED IN - DASHBOARD VIEW -->
    <div class="dashboard-layout">
      <!-- ==================== SIDEBAR ==================== -->
      <aside class="sidebar" role="navigation" aria-label="Main navigation">
        <!-- Brand -->
        <a class="sidebar-brand" href="index.php">QueenLib</a>

        <!-- User Info -->
        <div class="sidebar-user" id="userProfile">
          <div class="avatar"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
          <p class="user-greeting">Hi, <?php echo htmlspecialchars($user['first_name']); ?></p>
        </div>

        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
          <a class="nav-item is-active" href="index.php">
            <span class="nav-icon">⌂</span>
            <span>My Account</span>
          </a>
          <a class="nav-item" href="account.php">
            <span class="nav-icon">⚙</span>
            <span>Settings</span>
          </a>
          <a class="nav-item" href="logout.php">
            <span class="nav-icon">↪</span>
            <span>Log Out</span>
          </a>
        </nav>
      </aside>

      <!-- ==================== MAIN CONTENT ==================== -->
      <main class="dashboard-main">
        <!-- Header -->
        <header class="dashboard-header">
          <h1>My Account</h1>
          <p>Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        </header>

        <?php if ($flash): ?>
          <div style="padding: 15px 20px; border-radius: 4px; margin-bottom: 20px; background-color: #e8f5e9; color: #2e7d32; border-left: 4px solid #388e3c;">
            <?php echo htmlspecialchars($flash['message']); ?>
          </div>
        <?php endif; ?>

        <!-- ==================== STATS SECTION ==================== -->
        <section class="stats-grid" id="statsGrid" aria-label="Account statistics">
          <!-- User Email -->
          <article class="stat-card">
            <div class="stat-icon coral">✉</div>
            <strong><?php echo htmlspecialchars($user['email']); ?></strong>
            <span>Email Address</span>
          </article>

          <!-- Verification Status -->
          <article class="stat-card">
            <div class="stat-icon green">✓</div>
            <strong><?php echo $user['is_verified'] ? 'Verified' : 'Pending'; ?></strong>
            <span>Account Status</span>
          </article>

          <!-- Member Since -->
          <article class="stat-card">
            <div class="stat-icon gold">⏰</div>
            <strong><?php echo date('M d, Y', strtotime($user['created_at'])); ?></strong>
            <span>Member Since</span>
          </article>

          <!-- Quick Access -->
          <article class="stat-card">
            <div class="stat-icon gray">◷</div>
            <a href="account.php" style="color: inherit; text-decoration: none;">
              <strong>Account</strong>
              <span>Settings →</span>
            </a>
          </article>
        </section>

        <!-- ==================== PROFILE INFO SECTION ==================== -->
        <section class="panel">
          <div class="panel-heading">
            <h2>Profile Information</h2>
            <a href="account.php" class="history-link">Edit Profile →</a>
          </div>
          <div style="padding: 20px;">
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($user['first_name']); ?></p>
            <p style="margin-top: 12px;"><strong>Last Name:</strong> <?php echo htmlspecialchars($user['last_name']); ?></p>
            <p style="margin-top: 12px;"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p style="margin-top: 12px;">
              <strong>Account Status:</strong>
              <span style="<?php echo $user['is_verified'] ? 'color: #2e7d32;' : 'color: #f57c00;'; ?>">
                <?php echo $user['is_verified'] ? '✓ Verified' : '⚠ Not Verified'; ?>
              </span>
            </p>
          </div>
        </section>

        <!-- ==================== ACCOUNT ACTIONS SECTION ==================== -->
        <section class="panel">
          <div class="panel-heading">
            <h2>Account Actions</h2>
          </div>
          <div style="padding: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="account.php" style="display: inline-block; padding: 12px 24px; background-color: #333; color: white; border-radius: 4px; text-decoration: none; cursor: pointer;">Edit Profile</a>
            <a href="account.php" style="display: inline-block; padding: 12px 24px; background-color: #8B7355; color: white; border-radius: 4px; text-decoration: none; cursor: pointer;">Change Password</a>
            <a href="logout.php" style="display: inline-block; padding: 12px 24px; background-color: #d32f2f; color: white; border-radius: 4px; text-decoration: none; cursor: pointer;">Logout</a>
          </div>
        </section>
      </main>
    </div>

  <?php else: ?>
    <!-- NOT LOGGED IN - LANDING PAGE VIEW -->
    <header class="site-header">
      <div class="container nav-wrap">
        <a class="brand" href="index.php">QueenLib</a>
        <nav class="nav-actions">
          <a class="nav-link" href="login.php">Log In</a>
          <a class="button button-primary button-small" href="register.php">Get Started</a>
        </nav>
      </div>
    </header>

    <main>
      <section class="hero">
        <div class="hero-slides" aria-hidden="true">
          <div class="hero-slide is-active"></div>
          <div class="hero-slide"></div>
          <div class="hero-slide"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
          <h1>Your next great read is waiting.</h1>
          <p>
            Browse thousands of titles, reserve books instantly, and track your
            loans, all from one place.
          </p>
          <div class="hero-actions">
            <a class="button button-primary" href="register.php">Create a Free Account</a>
            <a class="button button-secondary" href="login.php">Log In</a>
          </div>

          <div class="hero-indicator" aria-label="Hero slide indicators">
            <span class="indicator-line"></span>
            <button class="indicator-dot" type="button" aria-label="Show slide 1" data-slide="0"></button>
            <button class="indicator-dot is-active" type="button" aria-label="Show slide 2" data-slide="1"></button>
            <button class="indicator-dot" type="button" aria-label="Show slide 3" data-slide="2"></button>
            <span class="indicator-line"></span>
          </div>
        </div>
      </section>

      <section class="features">
        <div class="container feature-grid">
          <article class="card">
            <div class="icon-box">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M11 4a7 7 0 1 0 4.95 11.95l3.55 3.55 1.5-1.5-3.55-3.55A7 7 0 0 0 11 4Zm0 2a5 5 0 1 1 0 10a5 5 0 0 1 0-10Z" />
              </svg>
            </div>
            <h2>Browse the Catalog</h2>
            <p>Search by title or author across thousands of available titles.</p>
          </article>

          <article class="card">
            <div class="icon-box">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 3a2 2 0 0 0-2 2v15.5l7-3l7 3V5a2 2 0 0 0-2-2H7Zm0 2h10v12.47l-5-2.14l-5 2.14V5Z" />
              </svg>
            </div>
            <h2>Reserve Instantly</h2>
            <p>Join the queue with one click and get notified when your book is ready.</p>
          </article>

          <article class="card">
            <div class="icon-box">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 2a8 8 0 1 1-8 8a8 8 0 0 1 8-8Zm-1 3v6l5 3l1-1.73l-4-2.27V7Z" />
              </svg>
            </div>
            <h2>Track Your Loans</h2>
            <p>View due dates, loan history, and manage your reservations in one place.</p>
          </article>
        </div>
      </section>

      <section class="steps">
        <div class="container">
          <div class="section-heading">
            <h2>How It Works</h2>
            <p>Get started in three simple steps.</p>
          </div>

          <div class="step-grid">
            <article class="step-item">
              <div class="step-number">1</div>
              <h3>Register &amp; verify your email</h3>
              <p>Create your free account and confirm your email address to get started.</p>
            </article>

            <div class="step-arrow" aria-hidden="true">→</div>

            <article class="step-item">
              <div class="step-number">2</div>
              <h3>Browse &amp; reserve a book</h3>
              <p>Search the catalog and reserve your next great read instantly.</p>
            </article>

            <div class="step-arrow" aria-hidden="true">→</div>

            <article class="step-item">
              <div class="step-number">3</div>
              <h3>Pick up at the library counter</h3>
              <p>Visit your branch to collect your reserved book when it's ready.</p>
            </article>
          </div>
        </div>
      </section>
    </main>

    <footer class="site-footer">
      <div class="container footer-wrap">
        <div>
          <a class="brand footer-brand" href="index.php">QueenLib</a>
          <p class="footer-copy">A modern library, at your fingertips.</p>
        </div>

        <nav class="footer-nav">
          <a href="#">Browse Catalog</a>
          <a href="login.php">Log In</a>
          <a href="register.php">Register</a>
        </nav>

        <p class="footer-meta">© 2026 QueenLib</p>
      </div>
    </footer>

  <?php endif; ?>

  <script src="config/api.config.js"></script>
  <script src="public/js/main.js"></script>
</body>

</html>