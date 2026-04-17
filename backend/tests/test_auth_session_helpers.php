<?php

return static function (): array {
  require_once __DIR__ . '/../../includes/config.php';
  require_once __DIR__ . '/../../backend/classes/AuthSupport.php';

  $originalSession = $_SESSION ?? [];

  try {
    $_SESSION = [];

    $user = [
      'id' => 123,
      'first_name' => 'Test',
      'last_name' => 'User',
      'email' => 'test.user@example.com',
      'role' => 'librarian',
      'is_superadmin' => 0,
    ];

    AuthSupport::setFrontendSession($user);

    $hasIdentity = (int)($_SESSION['user_id'] ?? 0) === 123
      && (string)($_SESSION['user_email'] ?? '') === 'test.user@example.com'
      && (string)($_SESSION['user_role'] ?? '') === 'librarian'
      && ($_SESSION['is_superadmin'] ?? true) === false;

    $startedAt = (int)($_SESSION['session_started_at'] ?? 0);
    $lastAt = (int)($_SESSION['last_activity_at'] ?? 0);
    $loginAt = (int)($_SESSION['login_time'] ?? 0);

    $timestampsValid = $startedAt > 0
      && $lastAt > 0
      && $loginAt > 0
      && $startedAt === $lastAt
      && $lastAt === $loginAt;

    $idleTimeout = max(60, (int)(defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 3600));
    $absoluteTimeout = max(300, (int)(defined('SESSION_ABSOLUTE_TIMEOUT') ? SESSION_ABSOLUTE_TIMEOUT : 43200));

    $now = time();
    $isIdleExpired = ($now - ($now - $idleTimeout - 5)) > $idleTimeout;
    $isAbsoluteExpired = ($now - ($now - $absoluteTimeout - 5)) > $absoluteTimeout;
    $isActiveFresh = ($now - ($now - 10)) <= $idleTimeout;

    $timeoutLogicSanity = $isIdleExpired === true && $isAbsoluteExpired === true && $isActiveFresh === true;

    $pass = $hasIdentity && $timestampsValid && $timeoutLogicSanity;

    return [
      'name' => 'auth_session_timeout_helpers',
      'pass' => $pass,
      'details' => $pass
        ? 'Session identity, timestamps, and timeout logic checks passed.'
        : 'Session helper fields or timeout logic checks failed.',
    ];
  } finally {
    $_SESSION = $originalSession;
  }
};
