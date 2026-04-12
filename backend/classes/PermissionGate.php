<?php

/**
 * PermissionGate
 *
 * Centralized role-permission matrix for the QueenLib library system.
 *
 * Role hierarchy (constitution Principle I):
 *   Superadmin > Admin > Librarian > Borrower
 *
 * Each role has a defined set of allowed actions. Higher roles inherit
 * lower-role permissions unless explicitly denied.
 */
class PermissionGate
{
  /** @var array<string, list<string>> */
  const ROLE_PERMISSIONS = [
    'borrower' => [
      'view_own_profile',
      'edit_own_profile',
      'search_books',
      'view_own_reservations',
      'request_reservation',
      'cancel_own_reservation',
      'view_own_borrowing_history',
      'view_own_fines',
      'view_notifications',
    ],
    'librarian' => [
      'view_own_profile',
      'edit_own_profile',
      'search_books',
      'view_own_reservations',
      'request_reservation',
      'cancel_own_reservation',
      'view_own_borrowing_history',
      'view_own_fines',
      'view_notifications',
      // Librarian-only (Principle II: Librarian-Centric Operations)
      'manage_books',
      'view_all_reservations',
      'approve_reservation',
      'reject_reservation',
      'process_checkout',
      'process_return',
      'view_all_borrowers',
      'collect_fines',
      'waive_fines',
      'view_fine_reports',
      'manage_book_inventory',
      'view_circulation_dashboard',
    ],
    'admin' => [
      'view_own_profile',
      'edit_own_profile',
      'search_books',
      'view_own_reservations',
      'request_reservation',
      'cancel_own_reservation',
      'view_own_borrowing_history',
      'view_own_fines',
      'view_notifications',
      // Admin-only
      'manage_books',
      'view_all_reservations',
      'approve_reservation',
      'reject_reservation',
      'process_checkout',
      'process_return',
      'view_all_borrowers',
      'collect_fines',
      'waive_fines',
      'view_fine_reports',
      'manage_book_inventory',
      'view_circulation_dashboard',
      'manage_users',
      'view_admin_dashboard',
      'change_admin_password',
      'view_admin_profile',
    ],
  ];

  /**
   * Superadmin inherits every permission defined for any role.
   */
  const SUPERADMIN_PERMISSIONS = [
    'view_own_profile',
    'edit_own_profile',
    'search_books',
    'view_own_reservations',
    'request_reservation',
    'cancel_own_reservation',
    'view_own_borrowing_history',
    'view_own_fines',
    'view_notifications',
    'manage_books',
    'view_all_reservations',
    'approve_reservation',
    'reject_reservation',
    'process_checkout',
    'process_return',
    'view_all_borrowers',
    'collect_fines',
    'waive_fines',
    'view_fine_reports',
    'manage_book_inventory',
    'view_circulation_dashboard',
    'manage_users',
    'view_admin_dashboard',
    'change_admin_password',
    'view_admin_profile',
    'manage_system_settings',
    'view_audit_logs',
  ];

  /**
   * Page-level access rules for admin portal pages.
   * Maps a page basename (without extension) to the minimum required role.
   */
  const PAGE_ACCESS = [
    'admin-dashboard' => 'admin',
    'admin-users' => 'admin',
    'admin-change-password' => 'admin',
    'admin-profile' => 'admin',
    'admin-fines' => 'admin',
    'librarian-dashboard' => 'librarian',
    'librarian-circulation' => 'librarian',
    'librarian-books' => 'librarian',
    'librarian-reservations' => 'librarian',
    'librarian-fines' => 'librarian',
    'borrower-dashboard' => 'borrower',
    'borrower-profile' => 'borrower',
    'borrower-reservations' => 'borrower',
    'borrower-history' => 'borrower',
    'borrower-fines' => 'borrower',
  ];

  /**
   * Role hierarchy rank (higher number = more authority).
   */
  const ROLE_RANK = [
    'borrower' => 1,
    'librarian' => 2,
    'admin' => 3,
  ];

  /**
   * Check if a role has a specific permission.
   */
  public static function hasPermission(string $role, string $permission): bool
  {
    $role = strtolower(trim($role));
    if ($role === 'superadmin') {
      return in_array($permission, self::SUPERADMIN_PERMISSIONS, true);
    }

    $permissions = self::ROLE_PERMISSIONS[$role] ?? [];
    return in_array($permission, $permissions, true);
  }

  /**
   * Check if a role has ALL of the given permissions.
   */
  public static function hasAllPermissions(string $role, array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if (!self::hasPermission($role, $permission)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Check if a role has ANY of the given permissions.
   */
  public static function hasAnyPermission(string $role, array $permissions): bool
  {
    foreach ($permissions as $permission) {
      if (self::hasPermission($role, $permission)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Get all permissions for a role.
   *
   * @return list<string>
   */
  public static function getPermissionsForRole(string $role): array
  {
    $role = strtolower(trim($role));
    if ($role === 'superadmin') {
      return self::SUPERADMIN_PERMISSIONS;
    }
    return self::ROLE_PERMISSIONS[$role] ?? [];
  }

  /**
   * Check if a role rank is at least the minimum required rank.
   */
  public static function meetsMinimumRole(string $role, string $minimumRole): bool
  {
    $role = strtolower(trim($role));
    $minimumRole = strtolower(trim($minimumRole));

    if ($role === 'superadmin') {
      return true;
    }

    $roleRank = self::ROLE_RANK[$role] ?? 0;
    $minRank = self::ROLE_RANK[$minimumRole] ?? 0;

    return $roleRank >= $minRank;
  }

  /**
   * Resolve the effective role for the current admin session.
   * Returns 'superadmin', 'admin', 'librarian', 'borrower', or 'guest'.
   */
  public static function resolveAdminRole(): string
  {
    if (!isAdminAuthenticated()) {
      return 'guest';
    }

    if (isCurrentAdminSuperadmin()) {
      return 'superadmin';
    }

    $sessionRole = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
    if (in_array($sessionRole, ['admin', 'librarian', 'borrower'], true)) {
      return $sessionRole;
    }

    return 'admin';
  }

  /**
   * Resolve the effective role for the current frontend (borrower) session.
   * Returns 'borrower', 'librarian', 'admin', 'superadmin', or 'guest'.
   */
  public static function resolveFrontendRole(?PDO $db = null): string
  {
    if (!isset($_SESSION['user_id'])) {
      return 'guest';
    }

    if ($db === null) {
      return 'borrower';
    }

    try {
      $stmt = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
      $stmt->execute([':id' => $_SESSION['user_id']]);
      $role = $stmt->fetchColumn();

      if ($role === false) {
        return 'guest';
      }

      $role = strtolower(trim((string)$role));
      if (in_array($role, ['borrower', 'librarian', 'admin'], true)) {
        return $role;
      }

      return 'borrower';
    } catch (Exception $e) {
      error_log('PermissionGate::resolveFrontendRole error: ' . $e->getMessage());
      return 'borrower';
    }
  }

  /**
   * Require that the current admin session meets the minimum role.
   * Redirects to login if not authenticated, or to dashboard if role is insufficient.
   */
  public static function requireAdminRole(string $minimumRole, string $redirectPath = 'admin-dashboard.php'): void
  {
    requireAdminAuth();

    $currentRole = self::resolveAdminRole();

    if (!self::meetsMinimumRole($currentRole, $minimumRole)) {
      setFlash('error', 'You do not have permission to access this page.');
      redirect($redirectPath);
    }
  }

  /**
   * Require that the current admin session has a specific permission.
   */
  public static function requireAdminPermission(string $permission, string $redirectPath = 'admin-dashboard.php'): void
  {
    requireAdminAuth();

    $currentRole = self::resolveAdminRole();

    if (!self::hasPermission($currentRole, $permission)) {
      setFlash('error', 'You do not have permission to perform this action.');
      redirect($redirectPath);
    }
  }

  /**
   * Require that the current frontend session meets the minimum role.
   */
  public static function requireFrontendRole(string $minimumRole, string $redirectPath = 'login.php'): void
  {
    requireLogin();

    global $db;
    $currentRole = self::resolveFrontendRole($db);

    if (!self::meetsMinimumRole($currentRole, $minimumRole)) {
      setFlash('error', 'You do not have permission to access this page.');
      redirect($redirectPath);
    }
  }

  /**
   * Check if the current admin session can access a specific page.
   */
  public static function canAccessPage(string $pageBasename): bool
  {
    if (!isAdminAuthenticated()) {
      return false;
    }

    $minimumRole = self::PAGE_ACCESS[$pageBasename] ?? null;
    if ($minimumRole === null) {
      return false;
    }

    $currentRole = self::resolveAdminRole();
    return self::meetsMinimumRole($currentRole, $minimumRole);
  }

  /**
   * Enforce page-level access for the current admin session.
   * Redirects to login if not authenticated, or to dashboard if access denied.
   */
  public static function requirePageAccess(string $pageBasename, string $fallbackRedirect = 'admin-dashboard.php'): void
  {
    requireAdminAuth();

    if (!self::canAccessPage($pageBasename)) {
      setFlash('error', 'You do not have permission to access this page.');
      redirect($fallbackRedirect);
    }
  }

  /**
   * Get the display label for a role.
   */
  public static function getRoleLabel(string $role): string
  {
    $labels = [
      'superadmin' => 'Superadmin',
      'admin' => 'Admin',
      'librarian' => 'Librarian',
      'borrower' => 'Borrower',
      'guest' => 'Guest',
    ];

    return $labels[strtolower(trim($role))] ?? ucfirst(strtolower(trim($role)));
  }

  /**
   * Get the CSS class suffix for a role badge.
   */
  public static function getRoleBadgeClass(string $role): string
  {
    $classes = [
      'superadmin' => 'is-superadmin',
      'admin' => 'is-admin',
      'librarian' => 'is-librarian',
      'borrower' => 'is-borrower',
    ];

    return $classes[strtolower(trim($role))] ?? 'is-borrower';
  }
}
