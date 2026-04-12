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
    <link rel="stylesheet" href="public/css/dashboard.css">
  <?php endif; ?>
  <style>
    <?php if ($is_logged_in): ?>

    /* Dashboard Specific Styles */
    .dashboard-header {
      background: linear-gradient(135deg, #f5f0e6 0%, #fff9f5 100%);
      border-bottom: 1px solid var(--line);
      padding: 40px 0;
      margin-bottom: 32px;
    }

    .dashboard-header h1 {
      font-size: 2.8rem;
      margin-bottom: 8px;
    }

    .dashboard-header p {
      font-size: 1.1rem;
      color: var(--muted);
    }

    .dashboard-main {
      padding: 0 32px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: white;
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--accent);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(43, 28, 16, 0.08);
      border-color: var(--accent);
    }

    .stat-card:hover::before {
      transform: scaleX(1);
    }

    .stat-icon {
      font-size: 2rem;
      margin-bottom: 12px;
    }

    .stat-card strong {
      display: block;
      font-size: 1.3rem;
      margin-bottom: 4px;
      color: var(--text);
    }

    .stat-card span {
      display: block;
      font-size: 0.9rem;
      color: var(--muted);
    }

    .panel {
      background: white;
      border: 1px solid var(--line);
      border-radius: 16px;
      margin-bottom: 24px;
      overflow: hidden;
      transition: all 0.3s ease;
    }

    .panel:hover {
      box-shadow: 0 8px 24px rgba(43, 28, 16, 0.08);
      border-color: var(--accent);
    }

    .panel-heading {
      padding: 28px 32px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(to right, rgba(242, 240, 236, 0.5), transparent);
    }

    .panel-heading h2 {
      font-size: 1.6rem;
      color: var(--text);
      margin: 0;
    }

    .history-link {
      color: var(--accent);
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .history-link:hover {
      gap: 10px;
    }

    .panel-content {
      padding: 32px;
    }

    .profile-item {
      display: flex;
      justify-content: space-between;
      padding: 16px 0;
      border-bottom: 1px solid var(--line);
    }

    .profile-item:last-child {
      border-bottom: none;
    }

    .profile-label {
      font-weight: 600;
      color: var(--text);
    }

    .profile-value {
      color: var(--muted);
    }

    .profile-value.verified {
      color: #5d8049;
      font-weight: 600;
    }

    .profile-value.pending {
      color: #ca8616;
      font-weight: 600;
    }

    .action-buttons {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
    }

    .action-btn {
      padding: 14px 24px;
      border: none;
      border-radius: 12px;
      font: inherit;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .action-btn.primary {
      background: linear-gradient(135deg, var(--accent), #b83d14);
      color: white;
      box-shadow: 0 4px 12px rgba(210, 71, 24, 0.3);
    }

    .action-btn.primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(210, 71, 24, 0.4);
    }

    .action-btn.secondary {
      background: var(--neutral-bg);
      color: var(--text);
      border: 1.5px solid var(--line);
    }

    .action-btn.secondary:hover {
      background: white;
      border-color: var(--accent);
      color: var(--accent);
    }

    .action-btn.danger {
      background: #fff2ef;
      color: #a62f0d;
      border: 1.5px solid #f0b7a7;
    }

    .action-btn.danger:hover {
      background: #a62f0d;
      color: white;
    }

    .action-btn.is-disabled {
      opacity: 0.6;
      cursor: not-allowed;
      pointer-events: none;
    }

    .overview-note {
      margin-top: 20px;
      font-size: 0.92rem;
      color: var(--muted);
      padding: 12px 14px;
      border: 1px dashed var(--line);
      border-radius: 10px;
      background: #fcfbf8;
    }

    @media (max-width: 1024px) {
      .dashboard-main {
        padding: 0 24px;
      }

      .panel-heading {
        padding: 20px 24px;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }

      .panel-content {
        padding: 24px;
      }
    }

    @media (max-width: 768px) {
      .dashboard-header {
        padding: 24px 0;
        margin-bottom: 24px;
      }

      .dashboard-header h1 {
        font-size: 2rem;
      }

      .dashboard-main {
        padding: 0 16px;
      }

      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
      }

      .stat-card {
        padding: 16px;
      }

      .stat-card strong {
        font-size: 1.1rem;
      }

      .action-buttons {
        grid-template-columns: 1fr;
      }
    }

    <?php endif; ?>
  </style>
</head>

<body>

  <?php if ($is_logged_in && $user): ?>
    <!-- LOGGED IN - DASHBOARD VIEW -->
    <div class="dashboard-layout">
      <!-- SIDEBAR -->
      <aside class="sidebar" role="navigation" aria-label="Main navigation">
        <a class="sidebar-brand" href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>">QueenLib</a>

        <div class="sidebar-user" id="userProfile">
          <div class="avatar"><?php echo strtoupper(substr($user['first_name'], 0, 1)); ?></div>
          <p class="user-greeting">Hi, <?php echo htmlspecialchars($user['first_name']); ?></p>
        </div>

        <nav class="sidebar-nav">
          <a class="nav-item is-active" href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="nav-icon">⌂</span>
            <span>My Account</span>
          </a>
          <a class="nav-item" href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="nav-icon">⚙</span>
            <span>Settings</span>
          </a>
          <a class="nav-item" href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="nav-icon">↪</span>
            <span>Log Out</span>
          </a>
        </nav>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="dashboard-main">
        <!-- Header -->
        <div class="dashboard-header">
          <h1>My Account</h1>
          <p>Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        </div>

        <!-- Flash Messages -->
        <?php if ($flash): ?>
          <div style="padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; background: var(--success-bg); color: var(--success-text); border-left: 4px solid #5d8049; display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 1.3rem;">✓</span>
            <?php echo htmlspecialchars($flash['message']); ?>
          </div>
        <?php endif; ?>

        <!-- STATS SECTION -->
        <section class="stats-grid" aria-label="Account statistics">
          <!-- Current Loans -->
          <article class="stat-card">
            <div class="stat-icon">📚</div>
            <strong><?php echo (int)$circulationOverview['current_loans']; ?></strong>
            <span>Current Loans</span>
          </article>

          <!-- Due Soon -->
          <article class="stat-card">
            <div class="stat-icon">⏰</div>
            <strong><?php echo (int)$circulationOverview['due_soon']; ?></strong>
            <span>Due in 3 Days</span>
          </article>

          <!-- Active Reservations -->
          <article class="stat-card">
            <div class="stat-icon">🏷</div>
            <strong><?php echo (int)$circulationOverview['active_reservations']; ?></strong>
            <span>Active Reservations</span>
          </article>

          <!-- Outstanding Fines -->
          <article class="stat-card">
            <div class="stat-icon">💳</div>
            <strong>₱<?php echo number_format((float)$circulationOverview['outstanding_fines'], 2); ?></strong>
            <span>Active Loan Fines</span>
          </article>
        </section>

        <!-- PROFILE INFO SECTION -->
        <section class="panel">
          <div class="panel-heading">
            <h2>Profile Information</h2>
            <a href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>" class="history-link">
              <span>Edit Profile</span>
              <span>→</span>
            </a>
          </div>
          <div class="panel-content">
            <div class="profile-item">
              <span class="profile-label">First Name</span>
              <span class="profile-value"><?php echo htmlspecialchars($user['first_name']); ?></span>
            </div>
            <div class="profile-item">
              <span class="profile-label">Last Name</span>
              <span class="profile-value"><?php echo htmlspecialchars($user['last_name']); ?></span>
            </div>
            <div class="profile-item">
              <span class="profile-label">Email Address</span>
              <span class="profile-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="profile-item">
              <span class="profile-label">Account Status</span>
              <span class="profile-value <?php echo $user['is_verified'] ? 'verified' : 'pending'; ?>">
                <?php echo $user['is_verified'] ? '✓ Verified' : '⚠ Not Verified'; ?>
              </span>
            </div>
          </div>
        </section>

        <!-- ACCOUNT ACTIONS SECTION -->
        <section class="panel">
          <div class="panel-heading">
            <h2>Quick Actions</h2>
          </div>
          <div class="panel-content">
            <div class="action-buttons">
              <span class="action-btn secondary is-disabled" aria-disabled="true" title="Reservation workflow coming soon">
                <span>📌</span>
                <span>Reserve Book (Soon)</span>
              </span>
              <span class="action-btn secondary is-disabled" aria-disabled="true" title="Renewal workflow coming soon">
                <span>♻</span>
                <span>Renew Loan (Soon)</span>
              </span>
              <span class="action-btn secondary is-disabled" aria-disabled="true" title="Borrowing history detail page coming soon">
                <span>🕘</span>
                <span>View History (Soon)</span>
              </span>
              <a href="<?php echo htmlspecialchars(appPath('account.php'), ENT_QUOTES, 'UTF-8'); ?>" class="action-btn primary">
                <span>📝</span>
                <span>Edit Profile</span>
              </a>
              <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="action-btn danger">
                <span>🚪</span>
                <span>Logout</span>
              </a>
            </div>
            <?php if (!$circulationDataAvailable): ?>
              <p class="overview-note"><?php echo htmlspecialchars($circulationUnavailableMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </div>
        </section>
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
          <a href="#">Browse Catalog</a>
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