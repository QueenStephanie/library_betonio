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
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = strtolower(trim((string)($user['role'] ?? 'borrower')));
    $_SESSION['is_superadmin'] = (int)($user['is_superadmin'] ?? 0) === 1;
    $now = time();
    $_SESSION['login_time'] = $now;
    $_SESSION['session_started_at'] = $now;
    $_SESSION['last_activity_at'] = $now;
  }

  public static function setBackendSession(array $user)
  {
    self::ensureSessionStarted();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_role'] = strtolower(trim((string)($user['role'] ?? 'borrower')));
    $_SESSION['is_superadmin'] = (int)($user['is_superadmin'] ?? 0) === 1;
    $now = time();
    $_SESSION['login_time'] = $now;
    $_SESSION['session_started_at'] = $now;
    $_SESSION['last_activity_at'] = $now;
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
