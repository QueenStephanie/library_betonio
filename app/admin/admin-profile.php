<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/AdminProfileRepository.php';

requireAdminAuth();
redirect('admin-dashboard.php#about-me');

$mainCssFile = APP_ROOT . '/public/css/main.css';
$adminCssFile = APP_ROOT . '/public/css/admin.css';
$mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
$adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
$mainCssHref = htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8');
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8');

$page_alerts = [];
$adminUsername = $_SESSION['admin_username'] ?? ADMIN_USERNAME;
$is_editing = isset($_GET['edit']);
$csrf_token = getAdminCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_editing) {
  $submittedToken = $_POST['csrf_token'] ?? '';
  $payload = [
    'full_name' => trim((string)($_POST['name'] ?? '')),
    'email' => trim((string)($_POST['email'] ?? '')),
    'phone' => trim((string)($_POST['phone'] ?? '')),
    'address' => trim((string)($_POST['address'] ?? '')),
    'appointment_date' => trim((string)($_POST['appointment_date'] ?? '')),
    'access_level' => trim((string)($_POST['access_level'] ?? 'Full Access - Super Administrator')),
  ];

  $errorMessage = null;
  if (!validateAdminCsrfToken($submittedToken)) {
    $errorMessage = 'Invalid or missing security token. Please refresh and try again.';
  } elseif ($payload['full_name'] === '') {
    $errorMessage = 'Name is required.';
  } elseif ($payload['email'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
    $errorMessage = 'Valid email is required.';
  } elseif ($payload['phone'] === '') {
    $errorMessage = 'Phone number is required.';
  } elseif ($payload['address'] === '') {
    $errorMessage = 'Address is required.';
  } elseif ($payload['appointment_date'] === '') {
    $errorMessage = 'Appointment date is required.';
  }

  if ($errorMessage !== null) {
    $page_alerts[] = [
      'type' => 'error',
      'title' => 'Update Failed',
      'message' => $errorMessage,
    ];
  } else {
    try {
      AdminProfileRepository::upsertByUsername($db, $adminUsername, $payload);
      $page_alerts[] = [
        'type' => 'success',
        'title' => 'Profile Updated',
        'message' => 'Your profile information has been updated successfully.',
      ];
      $is_editing = false;
    } catch (Exception $e) {
      error_log('admin-profile update error: ' . $e->getMessage());
      $page_alerts[] = [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => 'Unable to update profile at this time. Please try again.',
      ];
    }
  }
}

$admin_profile = [
  'name' => 'System Administrator',
  'email' => strtolower((string)$adminUsername) . '@queenlib.com',
  'phone' => '(555) 123-4567',
  'admin_id' => 'ADM-BOOTSTRAP',
  'address' => '456 Admin Boulevard, Central City',
  'appointment_date' => date('F j, Y'),
  'appointment_date_value' => date('Y-m-d'),
  'access_level' => 'Full Access - Super Administrator',
];

try {
  $admin_profile = AdminProfileRepository::getOrCreate($db, $adminUsername);
} catch (Exception $e) {
  error_log('admin-profile load error: ' . $e->getMessage());
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'Profile Load Warning',
    'message' => 'Using fallback profile data until database profile tables are available.',
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Administrator Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-brand-wrap">
        <div class="admin-brand">QueenLib</div>
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
          <div class="admin-sidebar-name"><?php echo htmlspecialchars($admin_profile['name'], ENT_QUOTES, 'UTF-8'); ?></div>
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
        <a class="admin-nav-item is-active" href="admin-profile.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.6" />
            <path d="M4.5 20C5.4 17.3 8.1 15.5 12 15.5C15.9 15.5 18.6 17.3 19.5 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
          </svg>
          <span>Profile</span>
        </a>
        <a class="admin-nav-item" href="admin-change-password.php">
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
        <h1>Administrator Profile</h1>
        <p>Manage your administrative information and settings.</p>
      </header>

      <section class="admin-card admin-profile-card">
        <div class="admin-profile-header">
          <div class="admin-profile-title">
            <span class="admin-profile-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 3L20 6V11C20 16.55 16.16 19.74 12 21C7.84 19.74 4 16.55 4 11V6L12 3Z" stroke="currentColor" stroke-width="1.6" />
              </svg>
            </span>
            <div>
              <h2><?php echo htmlspecialchars($admin_profile['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
              <p>System Administration</p>
            </div>
          </div>
          <?php if ($is_editing): ?>
            <a class="admin-button admin-button-ghost" href="admin-profile.php">View Mode</a>
          <?php else: ?>
            <a class="admin-button admin-button-ghost" href="admin-profile.php?edit=1">Edit Profile</a>
          <?php endif; ?>
        </div>

        <form class="admin-profile-form" method="POST" action="admin-profile.php?edit=1">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="access_level" value="<?php echo htmlspecialchars($admin_profile['access_level'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="admin-profile-grid">
            <div class="admin-form-field">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_profile['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_editing ? '' : 'readonly'; ?> required>
            </div>
            <div class="admin-form-field">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_profile['email'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_editing ? '' : 'readonly'; ?> required>
            </div>
            <div class="admin-form-field">
              <label for="phone">Phone Number</label>
              <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($admin_profile['phone'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_editing ? '' : 'readonly'; ?> required>
            </div>
            <div class="admin-form-field">
              <label for="admin_id">Administrator ID</label>
              <input type="text" id="admin_id" value="<?php echo htmlspecialchars($admin_profile['admin_id'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="admin-form-field admin-span-2">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($admin_profile['address'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_editing ? '' : 'readonly'; ?> required>
            </div>
            <div class="admin-form-field">
              <label for="appointment_date">Appointment Date</label>
              <input type="date" id="appointment_date" name="appointment_date" value="<?php echo htmlspecialchars($admin_profile['appointment_date_value'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $is_editing ? '' : 'readonly'; ?> required>
            </div>
            <div class="admin-form-field">
              <label for="access_level">Access Level</label>
              <input type="text" id="access_level" value="<?php echo htmlspecialchars($admin_profile['access_level'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
          </div>

          <?php if ($is_editing): ?>
            <div class="admin-form-actions">
              <a class="admin-button admin-button-ghost" href="admin-profile.php">Cancel</a>
              <button class="admin-button admin-button-primary" type="submit">Save Changes</button>
            </div>
          <?php endif; ?>
        </form>
      </section>
    </main>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
</body>

</html>