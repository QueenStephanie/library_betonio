<?php

return static function (): array {
  require_once __DIR__ . '/../../includes/config.php';
  require_once __DIR__ . '/../../includes/functions.php';
  require_once __DIR__ . '/../../backend/classes/PermissionGate.php';

  $originalSession = $_SESSION ?? [];

  try {
    $_SESSION = [];

    $matrixChecks = [
      PermissionGate::hasPermission('borrower', 'view_own_profile') === true,
      PermissionGate::hasPermission('borrower', 'manage_users') === false,
      PermissionGate::hasPermission('librarian', 'manage_books') === true,
      PermissionGate::hasPermission('librarian', 'manage_users') === false,
      PermissionGate::hasPermission('admin', 'manage_users') === true,
      PermissionGate::hasPermission('superadmin', 'manage_system_settings') === true,
      PermissionGate::meetsMinimumRole('librarian', 'borrower') === true,
      PermissionGate::meetsMinimumRole('borrower', 'librarian') === false,
      PermissionGate::meetsMinimumRole('superadmin', 'admin') === true,
    ];

    $_SESSION['user_id'] = 1001;
    $_SESSION['user_role'] = 'librarian';
    $_SESSION['is_superadmin'] = false;

    $librarianRoleResolved = PermissionGate::resolveAdminRole() === 'librarian';
    $librarianCanLibrarianPage = PermissionGate::canAccessPage('librarian-dashboard') === true;
    $librarianCannotAdminPage = PermissionGate::canAccessPage('admin-dashboard') === false;

    $_SESSION['user_role'] = 'admin';
    $adminCanAdminPage = PermissionGate::canAccessPage('admin-dashboard') === true;

    $_SESSION['user_role'] = 'borrower';
    $borrowerCannotLibrarianPage = PermissionGate::canAccessPage('librarian-dashboard') === false;

    $pass = !in_array(false, $matrixChecks, true)
      && $librarianRoleResolved
      && $librarianCanLibrarianPage
      && $librarianCannotAdminPage
      && $adminCanAdminPage
      && $borrowerCannotLibrarianPage;

    return [
      'name' => 'permission_gate_matrix',
      'pass' => $pass,
      'details' => $pass
        ? 'Role hierarchy, permission matrix, and page gate checks passed.'
        : 'One or more role/permission/page gate checks failed.',
    ];
  } finally {
    $_SESSION = $originalSession;
  }
};
