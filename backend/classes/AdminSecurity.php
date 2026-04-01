<?php

/**
 * Admin security service for hybrid credential resolution and persistence.
 */
class AdminSecurity
{
  private $db;

  public function __construct(PDO $database)
  {
    $this->db = $database;
  }

  /**
   * Determine whether DB-backed credentials are active.
   */
  public function hasActiveDbCredentials()
  {
    try {
      $stmt = $this->db->query('SELECT COUNT(*) FROM admin_credentials WHERE is_active = 1');
      return (int)$stmt->fetchColumn() > 0;
    } catch (Exception $e) {
      error_log('AdminSecurity::hasActiveDbCredentials error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Resolve the credential source state.
   */
  public function getCredentialSourceState()
  {
    if ($this->hasActiveDbCredentials()) {
      return [
        'mode' => 'db',
        'is_safe' => true,
      ];
    }

    if (!defined('ADMIN_BOOTSTRAP_CONFIGURED') || ADMIN_BOOTSTRAP_CONFIGURED !== true) {
      return [
        'mode' => 'blocked',
        'is_safe' => false,
        'error_code' => 'missing_bootstrap_credentials',
        'error' => 'Admin bootstrap credentials are missing. Configure ADMIN_USERNAME and ADMIN_PASSWORD.',
      ];
    }

    if (defined('ADMIN_BOOTSTRAP_UNSAFE') && ADMIN_BOOTSTRAP_UNSAFE === true) {
      return [
        'mode' => 'blocked',
        'is_safe' => false,
        'error_code' => 'unsafe_bootstrap_credentials',
        'error' => 'Admin bootstrap credentials are unsafe for the active environment.',
      ];
    }

    return [
      'mode' => 'bootstrap_env',
      'is_safe' => true,
    ];
  }

  /**
   * Verify admin login credentials using resolved source.
   */
  public function verifyLoginCredentials($username, $password)
  {
    $username = trim((string)$username);
    $password = (string)$password;

    if ($username === '' || $password === '') {
      return [
        'success' => false,
        'error_code' => 'missing_login_fields',
        'error' => 'Username and password are required.',
      ];
    }

    $state = $this->getCredentialSourceState();

    if ($state['mode'] === 'db') {
      $credential = $this->getActiveCredentialByUsername($username);
      if (!$credential || !password_verify($password, $credential['password_hash'])) {
        return [
          'success' => false,
          'error_code' => 'invalid_credentials',
          'error' => 'Invalid admin credentials.',
        ];
      }

      return [
        'success' => true,
        'auth_mode' => 'db',
        'admin_identity' => $credential['username'],
        'admin_credential_id' => (int)$credential['id'],
      ];
    }

    if ($state['mode'] === 'bootstrap_env') {
      $expectedUsername = (string)ADMIN_USERNAME;
      $expectedPassword = (string)ADMIN_PASSWORD;

      if (hash_equals($expectedUsername, $username) && hash_equals($expectedPassword, $password)) {
        return [
          'success' => true,
          'auth_mode' => 'bootstrap_env',
          'admin_identity' => $expectedUsername,
          'admin_credential_id' => null,
        ];
      }

      return [
        'success' => false,
        'error_code' => 'invalid_credentials',
        'error' => 'Invalid admin credentials.',
      ];
    }

    return [
      'success' => false,
      'error_code' => $state['error_code'] ?? 'credential_state_blocked',
      'error' => $state['error'] ?? 'Admin credential state is not safe for login.',
    ];
  }

  /**
   * Verify the current password against active credential mode.
   */
  public function verifyCurrentPassword($adminIdentity, $currentPassword, $sessionAuthMode = '')
  {
    $adminIdentity = trim((string)$adminIdentity);
    $currentPassword = (string)$currentPassword;

    if ($currentPassword === '') {
      return [
        'success' => false,
        'error_code' => 'missing_current_password',
        'error' => 'Current password is required.',
      ];
    }

    $state = $this->getCredentialSourceState();
    $activeMode = $state['mode'] === 'db' ? 'db' : ($sessionAuthMode === 'db' ? 'db' : $state['mode']);

    if ($activeMode === 'db') {
      $lookupIdentity = $adminIdentity !== '' ? $adminIdentity : (string)ADMIN_USERNAME;
      $credential = $this->getActiveCredentialByUsername($lookupIdentity);

      if (!$credential || !password_verify($currentPassword, $credential['password_hash'])) {
        return [
          'success' => false,
          'error_code' => 'current_password_invalid',
          'error' => 'Current password is incorrect.',
        ];
      }

      return [
        'success' => true,
        'auth_mode' => 'db',
        'admin_identity' => $credential['username'],
        'admin_credential_id' => (int)$credential['id'],
      ];
    }

    if ($activeMode === 'bootstrap_env') {
      $expectedUsername = (string)ADMIN_USERNAME;
      $expectedPassword = (string)ADMIN_PASSWORD;
      $identityMatches = $adminIdentity === '' || hash_equals($expectedUsername, $adminIdentity);

      if ($identityMatches && hash_equals($expectedPassword, $currentPassword)) {
        return [
          'success' => true,
          'auth_mode' => 'bootstrap_env',
          'admin_identity' => $expectedUsername,
          'admin_credential_id' => null,
        ];
      }

      return [
        'success' => false,
        'error_code' => 'current_password_invalid',
        'error' => 'Current password is incorrect.',
      ];
    }

    return [
      'success' => false,
      'error_code' => $state['error_code'] ?? 'credential_state_blocked',
      'error' => $state['error'] ?? 'Admin credential state is not safe for password updates.',
    ];
  }

  /**
   * Persist a new password hash to DB credentials.
   */
  public function persistPassword($adminIdentity, $newPassword)
  {
    $adminIdentity = trim((string)$adminIdentity);
    $newPassword = (string)$newPassword;

    if ($adminIdentity === '') {
      $adminIdentity = (string)ADMIN_USERNAME;
    }

    if ($newPassword === '') {
      return [
        'success' => false,
        'error_code' => 'missing_new_password',
        'error' => 'New password cannot be empty.',
      ];
    }

    $newHash = password_hash($newPassword, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
    if ($newHash === false) {
      return [
        'success' => false,
        'error_code' => 'password_hash_failed',
        'error' => 'Unable to secure the new password.',
      ];
    }

    try {
      $stmt = $this->db->prepare(
        'INSERT INTO admin_credentials (username, password_hash, is_active, password_changed_at, created_at, updated_at)
         VALUES (:username, :password_hash, 1, NOW(), NOW(), NOW())
         ON DUPLICATE KEY UPDATE
           password_hash = VALUES(password_hash),
           is_active = 1,
           password_changed_at = NOW(),
           updated_at = NOW()'
      );
      $stmt->execute([
        ':username' => $adminIdentity,
        ':password_hash' => $newHash,
      ]);

      $credential = $this->getActiveCredentialByUsername($adminIdentity);
      if (!$credential) {
        return [
          'success' => false,
          'error_code' => 'credential_persist_failed',
          'error' => 'Password updated, but credential metadata could not be loaded.',
        ];
      }

      return [
        'success' => true,
        'auth_mode' => 'db',
        'admin_identity' => $credential['username'],
        'admin_credential_id' => (int)$credential['id'],
      ];
    } catch (Exception $e) {
      error_log('AdminSecurity::persistPassword error: ' . $e->getMessage());
      return [
        'success' => false,
        'error_code' => 'credential_persist_failed',
        'error' => 'Failed to persist the admin password update.',
      ];
    }
  }

  /**
   * Fetch a credential row by active username.
   */
  public function getActiveCredentialByUsername($username)
  {
    try {
      $stmt = $this->db->prepare(
        'SELECT id, username, password_hash, is_active
         FROM admin_credentials
         WHERE username = :username AND is_active = 1
         LIMIT 1'
      );
      $stmt->execute([':username' => $username]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
    } catch (Exception $e) {
      error_log('AdminSecurity::getActiveCredentialByUsername error: ' . $e->getMessage());
      return null;
    }
  }
}
