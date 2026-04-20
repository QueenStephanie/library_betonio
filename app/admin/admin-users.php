<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once APP_ROOT . '/backend/classes/UserRepository.php';
require_once APP_ROOT . '/backend/classes/PermissionGate.php';
require_once APP_ROOT . '/includes/services/AdminUserManagementService.php';

PermissionGate::requirePageAccess('admin-users');

$mainCssFile = APP_ROOT . '/public/css/main.css';
$adminCssFile = APP_ROOT . '/public/css/admin.css';
$mainCssVersion = file_exists($mainCssFile) ? (string)filemtime($mainCssFile) : (string)time();
$adminCssVersion = file_exists($adminCssFile) ? (string)filemtime($adminCssFile) : (string)time();
$mainCssHref = htmlspecialchars(appPath('public/css/main.css', ['v' => $mainCssVersion]), ENT_QUOTES, 'UTF-8');
$adminCssHref = htmlspecialchars(appPath('public/css/admin.css', ['v' => $adminCssVersion]), ENT_QUOTES, 'UTF-8');

$page_alerts = [];
$csrf_token = getAdminCsrfToken();
$superadminUserId = null;
$isCurrentSuperadmin = isCurrentAdminSuperadmin();
$currentUserEmail = (string)($_SESSION['user_email'] ?? 'admin@local.admin');
$adminRoleLabel = $isCurrentSuperadmin ? 'Super Administrator' : 'Administrator';
$roleGovernanceDeniedMessage = 'Only superadmin can create or update borrower, librarian, and admin profiles.';
$adminUserManagementService = new AdminUserManagementService($db);
try {
  $superadminUser = UserRepository::getSuperadminUser($db);
  if (is_array($superadminUser) && isset($superadminUser['id'])) {
    $superadminUserId = (int)$superadminUser['id'];
  }
} catch (Exception $e) {
  error_log('admin-users superadmin lookup error: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $originCheck = validateStateChangingRequestOrigin('admin_users_post');
  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!$originCheck['valid']) {
    logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
    error_log('Blocked admin-users POST due to origin validation: ' . json_encode($originCheck));
    $page_alerts[] = [
      'type' => 'error',
      'title' => 'Security Validation Failed',
      'message' => 'Origin validation failed. Please refresh and try again.',
    ];
  } elseif (!validateAdminCsrfToken($submittedToken)) {
    logVerificationAttempt($currentUserEmail, 'csrf_reject', false);
    $page_alerts[] = [
      'type' => 'error',
      'title' => 'Security Validation Failed',
      'message' => 'Invalid or missing security token. Please refresh and try again.',
    ];
  } else {
    $action = trim((string)($_POST['action'] ?? ''));
    $superadminOnlyActions = ['create_user', 'update_user', 'toggle_status', 'delete_user', 'edit_password', 'reset_password'];

    if (!$isCurrentSuperadmin && in_array($action, $superadminOnlyActions, true)) {
      $page_alerts[] = [
        'type' => 'error',
        'title' => 'Permission Denied',
        'message' => $roleGovernanceDeniedMessage,
      ];
    } else {
      $alert = $adminUserManagementService->handleAction($action, $_POST, $isCurrentSuperadmin, $superadminUserId);
      if (is_array($alert)) {
        $page_alerts[] = $alert;
      }
    }
  }
}

$users = [];
try {
  $users = UserRepository::listManagedUsers($db, true);
} catch (Exception $e) {
  error_log('admin-users listManagedUsers error: ' . $e->getMessage());
  $page_alerts[] = [
    'type' => 'error',
    'title' => 'User Listing Warning',
    'message' => 'User data could not be loaded. Run database setup to initialize required tables.',
  ];
}

try {
  $superadminUser = UserRepository::getSuperadminUser($db);
  if (is_array($superadminUser) && isset($superadminUser['id'])) {
    $superadminUserId = (int)$superadminUser['id'];
  }
} catch (Exception $e) {
  error_log('admin-users superadmin refresh error: ' . $e->getMessage());
}

$stats = [
  'total' => count($users),
  'active' => 0,
  'borrower' => 0,
  'librarian' => 0,
  'admin' => 0,
];

foreach ($users as $user) {
  if (!empty($user['is_active'])) {
    $stats['active']++;
  }
  $role = UserRepository::normalizeRole($user['role'] ?? 'borrower');
  if (isset($stats[$role])) {
    $stats[$role]++;
  }
}

function roleBadgeClass($role)
{
  if ($role === 'admin') {
    return 'is-admin';
  }
  if ($role === 'librarian') {
    return 'is-librarian';
  }
  return 'is-borrower';
}

function roleLabel($role)
{
  if ($role === 'admin') {
    return 'Admin';
  }
  if ($role === 'librarian') {
    return 'Librarian';
  }
  return 'Borrower';
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | User Management</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo $mainCssHref; ?>">
  <link rel="stylesheet" href="<?php echo $adminCssHref; ?>">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="admin-portal-body">
  <div class="admin-shell">
    <?php
    $portalRole = 'admin';
    $portalCurrentPage = 'users';
    $portalIdentityName = $currentUserEmail;
    $portalIdentityMeta = $adminRoleLabel;
    $portalBrandSub = 'Admin Portal';
    require APP_ROOT . '/app/shared/portal-sidebar.php';
    ?>

    <main class="admin-main">
      <header class="admin-page-hero">
        <h1>User Management</h1>
        <p>Create users, assign roles, and keep role details up to date.</p>
      </header>

      <?php if (!$isCurrentSuperadmin): ?>
        <div class="admin-alert admin-alert-warning" role="status" aria-live="polite">
          <strong>Restricted Mode:</strong> <?php echo htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <section class="admin-card">
        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong><?php echo (int)$stats['total']; ?></strong>
            <span>Total Users</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$stats['active']; ?></strong>
            <span>Active Users</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$stats['librarian']; ?></strong>
            <span>Librarians</span>
          </article>
          <article class="admin-stat-tile">
            <strong><?php echo (int)$stats['admin']; ?></strong>
            <span>Administrators</span>
          </article>
        </div>

        <div class="admin-toolbar">
          <div class="admin-toolbar-meta">
            <span class="admin-count-pill" id="userCountPill">Showing <?php echo count($users); ?> users</span>
            <span class="admin-demo-note">Role changes replace prior role-specific data.</span>
          </div>
          <label class="admin-search" aria-label="Search users">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6" />
              <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            <input id="userSearchInput" type="search" placeholder="Search users by name, email, or ID...">
          </label>
          <select id="roleFilter" class="admin-select" aria-label="Filter roles">
            <option value="all">All Roles</option>
            <option value="borrower">Borrower</option>
            <option value="librarian">Librarian</option>
            <option value="admin">Admin</option>
          </select>
          <button class="admin-button admin-button-ghost" id="resetUserFilters" type="button">Reset</button>
          <button
            class="admin-button admin-button-primary"
            type="button"
            data-open-modal="#addUserModal"
            <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>
            title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : 'Add user'; ?>">
            + Add User
          </button>
        </div>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="usersTableBody">
              <?php foreach ($users as $user): ?>
                <?php
                $role = UserRepository::normalizeRole($user['role'] ?? 'borrower');
                $roleInfo = (string)($user['role_information'] ?? '');
                $isActive = !empty($user['is_active']);
                $isSuperadmin = $superadminUserId !== null && (int)$user['id'] === $superadminUserId;
                $fullName = trim(((string)$user['first_name']) . ' ' . ((string)$user['last_name']));
                $lastLogin = $user['last_login'] ? date('M j, Y g:i A', strtotime((string)$user['last_login'])) : 'Never';
                ?>
                <tr
                  data-user-id="<?php echo (int)$user['id']; ?>"
                  data-user-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                  data-first-name="<?php echo htmlspecialchars((string)$user['first_name'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-last-name="<?php echo htmlspecialchars((string)$user['last_name'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-user-email="<?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-user-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                  data-user-status="<?php echo $isActive ? 'active' : 'inactive'; ?>"
                  data-user-superadmin="<?php echo $isSuperadmin ? '1' : '0'; ?>"
                  data-can-change-password="<?php echo $isSuperadmin ? '0' : '1'; ?>"
                  data-role-information="<?php echo htmlspecialchars($roleInfo, ENT_QUOTES, 'UTF-8'); ?>">
                  <td>USR-<?php echo str_pad((string)$user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                  <td><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars((string)$user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><span class="admin-badge <?php echo roleBadgeClass($role); ?>"><?php echo roleLabel($role); ?></span></td>
                  <td><span class="admin-badge <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>"><?php echo $isActive ? 'Active' : 'Inactive'; ?></span></td>
                  <td><?php echo htmlspecialchars($lastLogin, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td>
                    <?php if ($isSuperadmin): ?>
                      <span class="admin-action-protected" title="Superadmin account is protected.">Protected</span>
                    <?php else: ?>
                      <div class="admin-actions">
                        <button
                          class="admin-action-btn"
                          type="button"
                          data-open-modal="#editUserModal"
                          data-edit-user
                          aria-label="Edit user"
                          <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>
                          title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : 'Edit user'; ?>">
                          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                          </svg>
                        </button>
                        <button
                          class="admin-action-btn"
                          type="button"
                          data-open-modal="#editPasswordModal"
                          data-edit-password
                          aria-label="Change password"
                          <?php echo !$isCurrentSuperadmin || $isSuperadmin ? 'disabled' : ''; ?>
                          title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : ($isSuperadmin ? 'Superadmin password cannot be changed' : 'Change user password'); ?>">
                          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="11" width="18" height="11" rx="2" stroke="currentColor" stroke-width="1.6" />
                            <path d="M7 11V7C7 4.23858 9.23858 2 12 2C14.7614 2 17 4.23858 17 7V11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                          </svg>
                        </button>
                        <button
                          class="admin-action-btn admin-action-reset"
                          type="button"
                          data-open-modal="#resetPasswordModal"
                          data-reset-password
                          aria-label="Reset password"
                          <?php echo !$isCurrentSuperadmin || $isSuperadmin ? 'disabled' : ''; ?>
                          title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : ($isSuperadmin ? 'Superadmin password cannot be reset' : 'Reset user password'); ?>">
                          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            <path d="M3 12L6 9M3 12L6 15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </button>
                        <form method="POST" class="admin-inline-form" data-requires-confirm="true">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                          <input type="hidden" name="status" value="<?php echo $isActive ? 'inactive' : 'active'; ?>">
                          <button
                            class="admin-action-btn admin-action-toggle <?php echo $isActive ? 'is-active' : 'is-inactive'; ?>"
                            type="submit"
                            <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>
                            aria-label="<?php echo $isActive ? 'Set user inactive' : 'Set user active'; ?>"
                            title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : ($isActive ? 'Set user inactive' : 'Set user active'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <rect x="3" y="7" width="18" height="10" rx="5" stroke="currentColor" stroke-width="1.6" />
                              <circle cx="<?php echo $isActive ? '16' : '8'; ?>" cy="12" r="3" fill="currentColor" />
                            </svg>
                          </button>
                        </form>
                        <form method="POST" class="admin-inline-form" data-requires-confirm="true">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="action" value="delete_user">
                          <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                          <button
                            class="admin-action-btn admin-action-danger"
                            type="submit"
                            <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>
                            aria-label="Delete user"
                            title="<?php echo !$isCurrentSuperadmin ? htmlspecialchars($roleGovernanceDeniedMessage, ENT_QUOTES, 'UTF-8') : 'Delete user permanently'; ?>">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                              <path d="M4 7H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                              <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                              <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                            </svg>
                          </button>
                        </form>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr id="noUserRows" class="admin-table-empty" hidden>
                <td colspan="7">No users match your current search/filter.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>

  <div id="addUserModal" class="admin-modal-backdrop" aria-hidden="true">
    <div class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="addUserTitle">
      <div class="admin-modal-header">
        <h2 id="addUserTitle">Add New User</h2>
        <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
      </div>
      <form class="admin-form-grid" method="POST" id="addUserForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="create_user">
        <div class="admin-form-field">
          <label for="add_first_name">First Name</label>
          <input id="add_first_name" name="first_name" type="text" placeholder="Enter first name" required>
        </div>
        <div class="admin-form-field">
          <label for="add_last_name">Last Name</label>
          <input id="add_last_name" name="last_name" type="text" placeholder="Enter last name" required>
        </div>
        <div class="admin-form-field">
          <label for="add_email">Email Address</label>
          <input id="add_email" name="email" type="email" placeholder="Enter email address" required>
        </div>
        <div class="admin-form-field">
          <label for="add_password">Temporary Password</label>
          <input id="add_password" name="password" type="password" placeholder="At least 8 characters" required>
        </div>
        <div class="admin-form-field">
          <label for="add_role">Role</label>
          <select id="add_role" name="role" required>
            <option value="borrower">Borrower</option>
            <option value="librarian">Librarian</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="admin-form-field">
          <label for="add_status">Status</label>
          <select id="add_status" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="admin-form-field admin-span-2">
          <label for="add_role_information">Role Information</label>
          <input id="add_role_information" name="role_information" type="text" placeholder="Role-specific notes or assignment details">
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary" type="submit" <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>>Add User</button>
        </div>
      </form>
    </div>
  </div>

  <div id="editUserModal" class="admin-modal-backdrop" aria-hidden="true">
    <div class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="editUserTitle">
      <div class="admin-modal-header">
        <h2 id="editUserTitle">Edit User</h2>
        <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
      </div>
      <form class="admin-form-grid" id="editUserForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="update_user">
        <input id="edit_user_id" name="user_id" type="hidden" value="">
        <div class="admin-form-field">
          <label for="edit_id_display">User ID (Read-only)</label>
          <input id="edit_id_display" type="text" readonly>
        </div>
        <div class="admin-form-field">
          <label for="edit_role">Role</label>
          <select id="edit_role" name="role" required>
            <option value="borrower">Borrower</option>
            <option value="librarian">Librarian</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="admin-form-field">
          <label for="edit_first_name">First Name</label>
          <input id="edit_first_name" name="first_name" type="text" required>
        </div>
        <div class="admin-form-field">
          <label for="edit_last_name">Last Name</label>
          <input id="edit_last_name" name="last_name" type="text" required>
        </div>
        <div class="admin-form-field">
          <label for="edit_email">Email Address</label>
          <input id="edit_email" name="email" type="email" required>
        </div>
        <div class="admin-form-field">
          <label for="edit_status">Status</label>
          <select id="edit_status" name="status">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="admin-form-field admin-span-2">
          <label for="edit_role_information">Role Information</label>
          <input id="edit_role_information" name="role_information" type="text" placeholder="Role-specific notes or assignment details">
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary" type="submit" <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>>Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div id="editPasswordModal" class="admin-modal-backdrop" aria-hidden="true">
    <div class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="editPasswordTitle">
      <div class="admin-modal-header">
        <h2 id="editPasswordTitle">Change User Password</h2>
        <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
      </div>
      <form class="admin-form-grid" id="editPasswordForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="edit_password">
        <input id="edit_password_user_id" name="user_id" type="hidden" value="">
        <div class="admin-form-field admin-span-2">
          <div id="editPasswordUserInfo" class="admin-info-text"></div>
        </div>
        <div class="admin-form-field">
          <label for="edit_new_password">New Password</label>
          <input id="edit_new_password" name="new_password" type="password" placeholder="At least 8 characters" required>
          <small class="admin-field-hint">Min 8 chars with uppercase, lowercase, number, special char</small>
        </div>
        <div class="admin-form-field">
          <label for="edit_confirm_password">Confirm Password</label>
          <input id="edit_confirm_password" name="confirm_password" type="password" placeholder="Re-enter password" required>
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary" type="submit" <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>>Update Password</button>
        </div>
      </form>
    </div>
  </div>

  <div id="resetPasswordModal" class="admin-modal-backdrop" aria-hidden="true">
    <div class="admin-modal-card" role="dialog" aria-modal="true" aria-labelledby="resetPasswordTitle">
      <div class="admin-modal-header">
        <h2 id="resetPasswordTitle">Reset User Password</h2>
        <button class="admin-modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
      </div>
      <form class="admin-form-grid" id="resetPasswordForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="action" value="reset_password">
        <input id="reset_password_user_id" name="user_id" type="hidden" value="">
        <div class="admin-form-field admin-span-2">
          <div id="resetPasswordUserInfo" class="admin-info-text"></div>
        </div>
        <div class="admin-form-field admin-span-2">
          <div class="admin-alert admin-alert-warning">
            <strong>Note:</strong> A secure temporary password will be generated. You will need to securely share this password with the user.
          </div>
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary admin-button-danger" type="submit" <?php echo !$isCurrentSuperadmin ? 'disabled' : ''; ?>>Generate New Password</button>
        </div>
      </form>
    </div>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>

  <script>
    function openModal(target) {
      if (!target) {
        return;
      }

      target.classList.add('is-open');
      target.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
      if (!modal) {
        return;
      }

      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-open-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        var target = document.querySelector(button.getAttribute('data-open-modal'));
        openModal(target);
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        closeModal(button.closest('.admin-modal-backdrop'));
      });
    });

    document.querySelectorAll('.admin-modal-backdrop').forEach(function(backdrop) {
      backdrop.addEventListener('click', function(event) {
        if (event.target === backdrop) {
          closeModal(backdrop);
        }
      });
    });

    document.addEventListener('keydown', function(event) {
      if (event.key !== 'Escape') {
        return;
      }

      document.querySelectorAll('.admin-modal-backdrop.is-open').forEach(function(openBackdrop) {
        closeModal(openBackdrop);
      });
    });

    var searchInput = document.getElementById('userSearchInput');
    var roleFilter = document.getElementById('roleFilter');
    var resetFiltersButton = document.getElementById('resetUserFilters');
    var usersTableBody = document.getElementById('usersTableBody');
    var countPill = document.getElementById('userCountPill');
    var noRows = document.getElementById('noUserRows');
    var rows = Array.prototype.slice.call(usersTableBody.querySelectorAll('tr[data-user-id]'));

    function applyUserFilters() {
      var query = (searchInput.value || '').trim().toLowerCase();
      var selectedRole = roleFilter.value;
      var visibleCount = 0;

      rows.forEach(function(row) {
        var rowId = (row.dataset.userId || '').toLowerCase();
        var rowName = (row.dataset.userName || '').toLowerCase();
        var rowEmail = (row.dataset.userEmail || '').toLowerCase();
        var rowRole = (row.dataset.userRole || '').toLowerCase();

        var matchesQuery = query === '' || rowId.indexOf(query) !== -1 || rowName.indexOf(query) !== -1 || rowEmail.indexOf(query) !== -1;
        var matchesRole = selectedRole === 'all' || rowRole === selectedRole;
        var shouldShow = matchesQuery && matchesRole;

        row.hidden = !shouldShow;
        if (shouldShow) {
          visibleCount += 1;
        }
      });

      countPill.textContent = visibleCount === 1 ? 'Showing 1 user' : 'Showing ' + visibleCount + ' users';
      noRows.hidden = visibleCount !== 0;
    }

    searchInput.addEventListener('input', applyUserFilters);
    roleFilter.addEventListener('change', applyUserFilters);
    resetFiltersButton.addEventListener('click', function() {
      searchInput.value = '';
      roleFilter.value = 'all';
      applyUserFilters();
      searchInput.focus();
    });

    usersTableBody.querySelectorAll('[data-edit-user]').forEach(function(button) {
      button.addEventListener('click', function() {
        var row = button.closest('tr[data-user-id]');
        if (!row) {
          return;
        }

        document.getElementById('edit_user_id').value = row.dataset.userId || '';
        document.getElementById('edit_id_display').value = 'USR-' + String(row.dataset.userId || '').padStart(3, '0');
        document.getElementById('edit_first_name').value = row.dataset.firstName || '';
        document.getElementById('edit_last_name').value = row.dataset.lastName || '';
        document.getElementById('edit_email').value = row.dataset.userEmail || '';
        document.getElementById('edit_role').value = row.dataset.userRole || 'borrower';
        document.getElementById('edit_status').value = row.dataset.userStatus || 'active';
        document.getElementById('edit_role_information').value = row.dataset.roleInformation || '';
      });
    });

    document.querySelectorAll('[data-edit-password]').forEach(function(button) {
      button.addEventListener('click', function() {
        var row = button.closest('tr[data-user-id]');
        if (!row) {
          return;
        }

        document.getElementById('edit_password_user_id').value = row.dataset.userId || '';
        document.getElementById('editPasswordUserInfo').textContent = 'Changing password for: ' + (row.dataset.userName || row.dataset.userEmail || 'User');
        document.getElementById('edit_new_password').value = '';
        document.getElementById('edit_confirm_password').value = '';
      });
    });

    document.querySelectorAll('[data-reset-password]').forEach(function(button) {
      button.addEventListener('click', function() {
        var row = button.closest('tr[data-user-id]');
        if (!row) {
          return;
        }

        document.getElementById('reset_password_user_id').value = row.dataset.userId || '';
        document.getElementById('resetPasswordUserInfo').textContent = 'Resetting password for: ' + (row.dataset.userName || row.dataset.userEmail || 'User');
      });
    });

    document.getElementById('editPasswordForm').addEventListener('submit', function(event) {
      var newPassword = document.getElementById('edit_new_password').value;
      var confirmPassword = document.getElementById('edit_confirm_password').value;
      
      if (newPassword !== confirmPassword) {
        event.preventDefault();
        alert('Password and confirmation do not match.');
        return;
      }
    });

    document.querySelectorAll('form[data-requires-confirm="true"]').forEach(function(form) {
      form.addEventListener('submit', function(event) {
        var row = form.closest('tr[data-user-id]');
        var isSuperadmin = row && row.dataset.userSuperadmin === '1';
        var actionInput = form.querySelector('input[name="action"]');
        var action = actionInput ? actionInput.value : '';

        if (isSuperadmin && (action === 'toggle_status' || action === 'delete_user')) {
          event.preventDefault();
          window.alert('Superadmin account is protected and cannot be modified by this action.');
          return;
        }

        if (action === 'delete_user') {
          var deleteConfirmed = window.confirm('Delete this user permanently? This action cannot be undone.');
          if (!deleteConfirmed) {
            event.preventDefault();
          }
          return;
        }

        var targetStatusInput = form.querySelector('input[name="status"]');
        var nextStatus = targetStatusInput ? targetStatusInput.value : 'inactive';
        var confirmed = window.confirm(nextStatus === 'inactive' ? 'Set this user to inactive?' : 'Reactivate this user?');
        if (!confirmed) {
          event.preventDefault();
        }
      });
    });

    applyUserFilters();
  </script>
</body>

</html>
