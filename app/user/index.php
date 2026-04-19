<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

/**
 * Main Dashboard / Homepage
 * Serves as both login redirect and logged-in dashboard
 */

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/CirculationRepository.php';

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

$circulationOverview = [
  'current_loans' => 0,
  'due_soon' => 0,
  'active_reservations' => 0,
  'outstanding_fines' => 0.0,
  'loan_history_count' => 0,
];
$circulationDataAvailable = false;
$circulationUnavailableMessage = 'Circulation widgets are temporarily unavailable. Please try again later.';

if ($is_logged_in && $user && isset($user['id'])) {
  try {
    $circulationOverview = CirculationRepository::getBorrowerOverview($db, (int)$user['id']);
    $circulationDataAvailable = true;
  } catch (Exception $e) {
    error_log('user dashboard circulation summary error: ' . $e->getMessage());

    $errorMessage = strtolower($e->getMessage());
    if (strpos($errorMessage, 'doesn\'t exist') !== false || strpos($errorMessage, 'unknown table') !== false) {
      $circulationUnavailableMessage = 'Circulation widgets are integrated, but circulation tables are not available yet. Run the circulation migration to activate live borrower metrics.';
    }
  }
}
$flash = getFlash();
$currentPage = 'dashboard';
$borrowerFullName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
if ($borrowerFullName === '') {
  $borrowerFullName = 'Borrower User';
}
$accountStatusLabel = !empty($user['is_verified']) ? 'Verified Account' : 'Verification Pending';
$accountStatusClass = !empty($user['is_verified']) ? 'is-verified' : 'is-pending';

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
  <?php if ($is_logged_in): ?>
    <link rel="stylesheet" href="public/css/admin.css">
    <link rel="stylesheet" href="public/css/borrower.css">
  <?php endif; ?>
</head>

<body<?php if ($is_logged_in): ?> class="admin-portal-body portal-role-borrower"<?php endif; ?>>

  <?php if ($is_logged_in && $user): ?>
    <div class="admin-shell">
      <?php
      $portalRole = 'borrower';
      $portalCurrentPage = 'dashboard';
      $portalIdentityName = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
      if ($portalIdentityName === '') {
        $portalIdentityName = 'Borrower User';
      }
      $portalIdentityMeta = (string)($user['email'] ?? '');
      require APP_ROOT . '/app/shared/portal-sidebar.php';
      ?>

      <main class="admin-main borrower-main">
        <div class="borrower-page">
          <div class="borrower-shell borrower-dashboard-shell">
            <section class="borrower-hero borrower-dashboard-hero">
              <div class="borrower-hero-copy">
                <span class="borrower-eyebrow">Borrower dashboard</span>
                <h1>Manage your library account</h1>
                <p class="borrower-page-subtitle">Welcome back, <?php echo htmlspecialchars($borrowerFullName, ENT_QUOTES, 'UTF-8'); ?>.</p>
                <div class="borrower-hero-actions">
                  <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-btn borrower-btn-primary">Browse Catalog</a>
                  <a href="<?php echo htmlspecialchars(appPath('history.php') . '#active-loans', ENT_QUOTES, 'UTF-8'); ?>" class="borrower-btn borrower-btn-secondary">Manage Loans</a>
                </div>
              </div>
              <aside class="borrower-hero-card">
                <span class="borrower-hero-card-label">Account snapshot</span>
                <strong><?php echo htmlspecialchars($accountStatusLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
              </aside>
            </section>

            <?php if ($flash): ?>
              <div class="borrower-alert <?php echo (($flash['type'] ?? '') === 'success') ? 'borrower-alert-success' : 'borrower-alert-error'; ?>" role="status" aria-live="polite">
                <?php echo htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php endif; ?>

            <section class="borrower-dashboard-stats borrower-stat-grid" aria-label="Borrower statistics">
              <article class="borrower-card borrower-stat-card">
                <p class="borrower-stat-label">Current Loans</p>
                <p class="borrower-stat-value"><?php echo (int)$circulationOverview['current_loans']; ?></p>
              </article>
              <article class="borrower-card borrower-stat-card">
                <p class="borrower-stat-label">Due in 3 Days</p>
                <p class="borrower-stat-value"><?php echo (int)$circulationOverview['due_soon']; ?></p>
              </article>
              <article class="borrower-card borrower-stat-card">
                <p class="borrower-stat-label">Active Reservations</p>
                <p class="borrower-stat-value"><?php echo (int)$circulationOverview['active_reservations']; ?></p>
              </article>
              <article class="borrower-card borrower-stat-card">
                <p class="borrower-stat-label">Active Loan Fines</p>
                <p class="borrower-stat-value">₱<?php echo number_format((float)$circulationOverview['outstanding_fines'], 2); ?></p>
              </article>
            </section>

            <div class="borrower-dashboard-grid">
              <section class="borrower-card borrower-surface-card borrower-dashboard-panel">
                <div class="borrower-panel-heading">
                  <div>
                    <span class="borrower-section-kicker">Profile</span>
                    <h2>Borrower identity</h2>
                  </div>
                  <a href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-inline-link">Edit profile</a>
                </div>
                <div class="borrower-panel-content">
                  <div class="borrower-profile-list">
                    <div class="borrower-profile-row">
                      <span class="borrower-profile-label">First Name</span>
                      <span class="borrower-profile-value"><?php echo htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="borrower-profile-row">
                      <span class="borrower-profile-label">Last Name</span>
                      <span class="borrower-profile-value"><?php echo htmlspecialchars((string)$user['last_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="borrower-profile-row">
                      <span class="borrower-profile-label">Email Address</span>
                      <span class="borrower-profile-value"><?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="borrower-profile-row">
                      <span class="borrower-profile-label">Account Status</span>
                      <span class="borrower-profile-value <?php echo htmlspecialchars($accountStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($accountStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </div>
                  </div>
                </div>
              </section>

              <section class="borrower-card borrower-surface-card borrower-dashboard-panel">
                <div class="borrower-panel-heading">
                  <div>
                    <span class="borrower-section-kicker">Actions</span>
                    <h2>Next steps</h2>
                  </div>
                </div>
                <div class="borrower-panel-content">
                  <div class="borrower-action-grid borrower-action-cards">
                    <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card">
                      <strong>Browse Catalog</strong>
                      <span>Find and reserve books.</span>
                    </a>
                    <a href="<?php echo htmlspecialchars(appPath('reservations.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card">
                      <strong>Manage Reservations</strong>
                      <span>Track and cancel requests.</span>
                    </a>
                    <a href="<?php echo htmlspecialchars(appPath('history.php') . '#active-loans', ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card">
                      <strong>Renew Active Loans</strong>
                      <span>Renew eligible loans.</span>
                    </a>
                    <a href="<?php echo htmlspecialchars(appPath('history.php') . '#borrowing-history', ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card">
                      <strong>Review History</strong>
                      <span>See returns and fines.</span>
                    </a>
                    <a href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card">
                      <strong>Update Settings</strong>
                      <span>Edit profile and password.</span>
                    </a>
                    <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-action-card is-danger">
                      <strong>Log Out</strong>
                      <span>Sign out securely.</span>
                    </a>
                  </div>

                  <?php if (!$circulationDataAvailable): ?>
                    <p class="borrower-note"><?php echo htmlspecialchars($circulationUnavailableMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php endif; ?>
                </div>
              </section>
            </div>
          </div>
        </div>
      </main>
    </div>

  <?php else: ?>
    <!-- NOT LOGGED IN - LANDING PAGE VIEW -->
    <header class="site-header">
      <div class="container nav-wrap">
        <a class="brand" href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>">QueenLib</a>
        <nav class="nav-actions">
          <a class="nav-link" href="<?php echo htmlspecialchars(appPath('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log In</a>
          <a class="button button-primary button-small" href="<?php echo htmlspecialchars(appPath('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Get Started</a>
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
            <a class="button button-primary" href="<?php echo htmlspecialchars(appPath('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Create a Free Account</a>
            <a class="button button-secondary" href="<?php echo htmlspecialchars(appPath('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log In</a>
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
          <a class="brand footer-brand" href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>">QueenLib</a>
          <p class="footer-copy">A modern library, at your fingertips.</p>
        </div>

        <nav class="footer-nav">
          <a href="<?php echo htmlspecialchars(appPath('catalog.php'), ENT_QUOTES, 'UTF-8'); ?>">Browse Catalog</a>
          <a href="<?php echo htmlspecialchars(appPath('login.php'), ENT_QUOTES, 'UTF-8'); ?>">Log In</a>
          <a href="<?php echo htmlspecialchars(appPath('register.php'), ENT_QUOTES, 'UTF-8'); ?>">Register</a>
        </nav>

        <p class="footer-meta">© 2026 QueenLib</p>
      </div>
    </footer>

  <?php endif; ?>

  <script src="public/js/main.js"></script>
</body>

</html>
