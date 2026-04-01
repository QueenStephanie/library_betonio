<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
  redirect('admin-login.php');
}

$page_alerts = [];
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
        <a class="admin-nav-item is-active" href="admin-users.php">
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
        <a class="admin-nav-item" href="admin-change-password.php">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 10V8C6 5.79 7.79 4 10 4H14C16.21 4 18 5.79 18 8V10" stroke="currentColor" stroke-width="1.6" />
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6" />
          </svg>
          <span>Change Password</span>
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
        <h1>User Management</h1>
        <p>Manage system users, roles, and permissions</p>
      </header>

      <section class="admin-card">
        <div class="admin-stats-row">
          <article class="admin-stat-tile">
            <strong>5</strong>
            <span>Total Users</span>
          </article>
          <article class="admin-stat-tile">
            <strong>4</strong>
            <span>Active Users</span>
          </article>
          <article class="admin-stat-tile">
            <strong>1</strong>
            <span>Librarians</span>
          </article>
          <article class="admin-stat-tile">
            <strong>1</strong>
            <span>Administrators</span>
          </article>
        </div>

        <div class="admin-toolbar">
          <label class="admin-search" aria-label="Search users">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.6" />
              <path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            </svg>
            <input type="search" placeholder="Search users by name, email, or ID...">
          </label>
          <select class="admin-select" aria-label="Filter roles">
            <option>All Roles</option>
            <option>User</option>
            <option>Librarian</option>
            <option>Admin</option>
          </select>
          <button class="admin-button admin-button-primary" type="button" data-open-modal="#addUserModal">
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
            <tbody>
              <tr>
                <td>USR-001</td>
                <td>John Smith</td>
                <td>john.smith@email.com</td>
                <td><span class="admin-badge is-user">User</span></td>
                <td><span class="admin-badge is-active">Active</span></td>
                <td>Mar 28, 2024</td>
                <td>
                  <div class="admin-actions">
                    <button class="admin-action-btn" type="button" data-open-modal="#editUserModal" aria-label="Edit user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-danger" type="button" aria-label="Delete user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                        <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>USR-002</td>
                <td>Sarah Johnson</td>
                <td>sarah.j@email.com</td>
                <td><span class="admin-badge is-user">User</span></td>
                <td><span class="admin-badge is-active">Active</span></td>
                <td>Mar 30, 2024</td>
                <td>
                  <div class="admin-actions">
                    <button class="admin-action-btn" type="button" data-open-modal="#editUserModal" aria-label="Edit user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-danger" type="button" aria-label="Delete user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                        <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>LIB-001</td>
                <td>Head Librarian</td>
                <td>librarian@libris.com</td>
                <td><span class="admin-badge is-librarian">Librarian</span></td>
                <td><span class="admin-badge is-active">Active</span></td>
                <td>Apr 1, 2024</td>
                <td>
                  <div class="admin-actions">
                    <button class="admin-action-btn" type="button" data-open-modal="#editUserModal" aria-label="Edit user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-danger" type="button" aria-label="Delete user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                        <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>ADM-001</td>
                <td>System Administrator</td>
                <td>admin@libris.com</td>
                <td><span class="admin-badge is-admin">Admin</span></td>
                <td><span class="admin-badge is-active">Active</span></td>
                <td>Apr 1, 2024</td>
                <td>
                  <div class="admin-actions">
                    <button class="admin-action-btn" type="button" data-open-modal="#editUserModal" aria-label="Edit user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-danger" type="button" aria-label="Delete user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                        <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr>
                <td>USR-003</td>
                <td>Michael Brown</td>
                <td>m.brown@email.com</td>
                <td><span class="admin-badge is-user">User</span></td>
                <td><span class="admin-badge is-inactive">Inactive</span></td>
                <td>Jan 5, 2024</td>
                <td>
                  <div class="admin-actions">
                    <button class="admin-action-btn" type="button" data-open-modal="#editUserModal" aria-label="Edit user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 20H8L19 9L15 5L4 16V20Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                      </svg>
                    </button>
                    <button class="admin-action-btn admin-action-danger" type="button" aria-label="Delete user">
                      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5 7H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                        <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="1.6" />
                        <path d="M7 7L8 19H16L17 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                      </svg>
                    </button>
                  </div>
                </td>
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
      <form class="admin-form-grid">
        <div class="admin-form-field">
          <label for="add_name">Full Name</label>
          <input id="add_name" type="text" placeholder="Enter full name">
        </div>
        <div class="admin-form-field">
          <label for="add_email">Email Address</label>
          <input id="add_email" type="email" placeholder="Enter email address">
        </div>
        <div class="admin-form-field">
          <label for="add_role">Role</label>
          <select id="add_role">
            <option>User</option>
            <option>Librarian</option>
            <option>Admin</option>
          </select>
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary" type="submit">Add User</button>
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
      <form class="admin-form-grid">
        <div class="admin-form-field">
          <label for="edit_id">User ID (Read-only)</label>
          <input id="edit_id" type="text" value="USR-001" readonly>
        </div>
        <div class="admin-form-field">
          <label for="edit_name">Full Name</label>
          <input id="edit_name" type="text" value="John Smith">
        </div>
        <div class="admin-form-field">
          <label for="edit_email">Email Address</label>
          <input id="edit_email" type="email" value="john.smith@email.com">
        </div>
        <div class="admin-form-field">
          <label for="edit_role">Role</label>
          <select id="edit_role">
            <option>User</option>
            <option>Librarian</option>
            <option>Admin</option>
          </select>
        </div>
        <div class="admin-form-field">
          <label for="edit_status">Status</label>
          <select id="edit_status">
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>
        <div class="admin-modal-actions">
          <button class="admin-button admin-button-ghost" type="button" data-close-modal>Cancel</button>
          <button class="admin-button admin-button-primary" type="submit">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <button class="admin-help-fab" type="button" aria-label="Help">?</button>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>

  <script>
    document.querySelectorAll('[data-open-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        var target = document.querySelector(button.getAttribute('data-open-modal'));
        if (target) {
          target.classList.add('is-open');
          target.setAttribute('aria-hidden', 'false');
        }
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(button) {
      button.addEventListener('click', function() {
        var modal = button.closest('.admin-modal-backdrop');
        if (modal) {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
        }
      });
    });

    document.querySelectorAll('.admin-modal-backdrop').forEach(function(backdrop) {
      backdrop.addEventListener('click', function(event) {
        if (event.target === backdrop) {
          backdrop.classList.remove('is-open');
          backdrop.setAttribute('aria-hidden', 'true');
        }
      });
    });
  </script>
</body>

</html>