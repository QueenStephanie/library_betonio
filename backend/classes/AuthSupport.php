<?php

/**
 * Shared auth/session helpers used by frontend and API auth classes.
 */
class AuthSupport
{
  public static function ensureSessionStarted()
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }

  public static function setFrontendSession(array $user)
  {
    self::ensureSessionStarted();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = strtolower(trim((string)($user['role'] ?? 'borrower')));
    $_SESSION['is_superadmin'] = (int)($user['is_superadmin'] ?? 0) === 1;
    $_SESSION['login_time'] = time();
  }

  public static function setBackendSession(array $user)
  {
    self::ensureSessionStarted();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_role'] = strtolower(trim((string)($user['role'] ?? 'borrower')));
    $_SESSION['is_superadmin'] = (int)($user['is_superadmin'] ?? 0) === 1;
    $_SESSION['login_time'] = time();
  }

  private static function getRequestIp()
  {
    return $_SERVER['REMOTE_ADDR'] ?? null;
  }

  private static function getRequestUserAgent()
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if ($userAgent === null) {
      return null;
    }

    return substr((string)$userAgent, 0, 500);
  }

  private static function hashSessionId($sessionId)
  {
    if (!is_string($sessionId) || $sessionId === '') {
      return null;
    }

    return hash('sha256', $sessionId);
  }

  /**
   * Create or refresh the current admin session registry row.
   */
  public static function createAdminSessionRegistry(PDO $db, array $context = [])
  {
    self::ensureSessionStarted();

    $sessionIdHash = self::hashSessionId(session_id());
    if ($sessionIdHash === null) {
      return false;
    }

    $adminIdentity = (string)($context['admin_identity'] ?? ($_SESSION['admin_username'] ?? 'admin'));
    $authMode = (string)($context['auth_mode'] ?? ($_SESSION['admin_auth_mode'] ?? 'bootstrap_env'));
    $credentialId = $context['admin_credential_id'] ?? ($_SESSION['admin_credential_id'] ?? null);
    $ipAddress = $context['ip_address'] ?? self::getRequestIp();
    $userAgent = $context['user_agent'] ?? self::getRequestUserAgent();

    try {
      $stmt = $db->prepare(
        'INSERT INTO admin_session_registry
          (admin_identity, admin_credential_id, session_id_hash, auth_mode, ip_address, user_agent, created_at, last_seen_at, invalidated_at)
         VALUES
          (:admin_identity, :admin_credential_id, :session_id_hash, :auth_mode, :ip_address, :user_agent, NOW(), NOW(), NULL)
         ON DUPLICATE KEY UPDATE
          admin_identity = VALUES(admin_identity),
          admin_credential_id = VALUES(admin_credential_id),
          auth_mode = VALUES(auth_mode),
          ip_address = VALUES(ip_address),
          user_agent = VALUES(user_agent),
          last_seen_at = NOW(),
          invalidated_at = NULL'
      );

      $stmt->bindValue(':admin_identity', $adminIdentity, PDO::PARAM_STR);
      if ($credentialId === null || $credentialId === '') {
        $stmt->bindValue(':admin_credential_id', null, PDO::PARAM_NULL);
      } else {
        $stmt->bindValue(':admin_credential_id', (int)$credentialId, PDO::PARAM_INT);
      }
      $stmt->bindValue(':session_id_hash', $sessionIdHash, PDO::PARAM_STR);
      $stmt->bindValue(':auth_mode', $authMode, PDO::PARAM_STR);
      if ($ipAddress === null || $ipAddress === '') {
        $stmt->bindValue(':ip_address', null, PDO::PARAM_NULL);
      } else {
        $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
      }
      if ($userAgent === null || $userAgent === '') {
        $stmt->bindValue(':user_agent', null, PDO::PARAM_NULL);
      } else {
        $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);
      }

      return $stmt->execute();
    } catch (Exception $e) {
      error_log('AuthSupport::createAdminSessionRegistry error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Invalidate a single admin session by raw session ID.
   */
  public static function invalidateCurrentAdminSession(PDO $db, $sessionId = null)
  {
    self::ensureSessionStarted();

    $targetSessionId = is_string($sessionId) && $sessionId !== '' ? $sessionId : session_id();
    $sessionIdHash = self::hashSessionId($targetSessionId);
    if ($sessionIdHash === null) {
      return false;
    }

    try {
      $stmt = $db->prepare(
        'UPDATE admin_session_registry
         SET invalidated_at = NOW()
         WHERE session_id_hash = :session_id_hash
           AND invalidated_at IS NULL'
      );
      $stmt->execute([':session_id_hash' => $sessionIdHash]);
      return true;
    } catch (Exception $e) {
      error_log('AuthSupport::invalidateCurrentAdminSession error: ' . $e->getMessage());
      return false;
    }
  }

  /**
   * Invalidate all active sessions for an identity except the provided session.
   */
  public static function invalidateOtherAdminSessions(PDO $db, $adminIdentity, $currentSessionId = null)
  {
    $adminIdentity = trim((string)$adminIdentity);
    if ($adminIdentity === '') {
      return 0;
    }

    $currentHash = self::hashSessionId($currentSessionId);

    try {
      if ($currentHash === null) {
        $stmt = $db->prepare(
          'UPDATE admin_session_registry
           SET invalidated_at = NOW()
           WHERE admin_identity = :admin_identity
             AND invalidated_at IS NULL'
        );
        $stmt->execute([':admin_identity' => $adminIdentity]);
      } else {
        $stmt = $db->prepare(
          'UPDATE admin_session_registry
           SET invalidated_at = NOW()
           WHERE admin_identity = :admin_identity
             AND invalidated_at IS NULL
             AND session_id_hash <> :session_id_hash'
        );
        $stmt->execute([
          ':admin_identity' => $adminIdentity,
          ':session_id_hash' => $currentHash,
        ]);
      }

      return (int)$stmt->rowCount();
    } catch (Exception $e) {
      error_log('AuthSupport::invalidateOtherAdminSessions error: ' . $e->getMessage());
      return 0;
    }
  }

  /**
   * Replace previous session binding with the current regenerated session.
   */
  public static function refreshAdminSessionRegistry(PDO $db, array $context = [], $previousSessionId = null)
  {
    if (is_string($previousSessionId) && $previousSessionId !== '') {
      self::invalidateCurrentAdminSession($db, $previousSessionId);
    }

    return self::createAdminSessionRegistry($db, $context);
  }

  public static function clearSession()
  {
    self::ensureSessionStarted();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }

    session_destroy();
  }
}
