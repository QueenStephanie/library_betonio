<?php

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$results = [];
$allPass = true;

function addResult(array &$results, string $name, bool $pass, string $details): void
{
  $results[] = [
    'check' => $name,
    'pass' => $pass,
    'details' => $details,
  ];
}

try {
  $legacyCheck = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('admin_credentials', 'admin_session_registry')");
  $legacyRows = $legacyCheck->fetchAll(PDO::FETCH_COLUMN);
  $legacyMissing = count($legacyRows) === 0;
  addResult($results, 'legacy_tables_removed', $legacyMissing, $legacyMissing ? 'admin_credentials and admin_session_registry are absent.' : 'Still present: ' . implode(', ', $legacyRows));
  $allPass = $allPass && $legacyMissing;

  $superadminStmt = $db->query("SELECT id, email, role, is_superadmin, is_active, is_verified FROM users WHERE is_superadmin = 1 ORDER BY id ASC");
  $superadmins = $superadminStmt->fetchAll(PDO::FETCH_ASSOC);
  $hasSingleSuperadmin = count($superadmins) === 1;
  $superadminDetails = $hasSingleSuperadmin ? ('single superadmin: ' . $superadmins[0]['email']) : ('superadmin count=' . count($superadmins));
  addResult($results, 'single_superadmin_exists', $hasSingleSuperadmin, $superadminDetails);
  $allPass = $allPass && $hasSingleSuperadmin;

  $superadminRoleValid = $hasSingleSuperadmin
    && strtolower((string)$superadmins[0]['role']) === 'admin'
    && (int)$superadmins[0]['is_active'] === 1
    && (int)$superadmins[0]['is_verified'] === 1;
  addResult($results, 'superadmin_role_active_verified', $superadminRoleValid, $superadminRoleValid ? 'superadmin role/status is valid.' : 'superadmin role/status invalid.');
  $allPass = $allPass && $superadminRoleValid;

  $stamp = (string)time();
  $activeEmail = 'qa.active.' . $stamp . '@example.local';
  $inactiveEmail = 'qa.inactive.' . $stamp . '@example.local';
  $password = 'TempPass#123';
  $passwordHash = password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);

  $insert = $db->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash, is_verified, is_active, role, is_superadmin, created_at, updated_at)
     VALUES (:first_name, :last_name, :email, :password_hash, 1, :is_active, :role, 0, NOW(), NOW())'
  );

  $insert->execute([
    ':first_name' => 'QA',
    ':last_name' => 'Active',
    ':email' => $activeEmail,
    ':password_hash' => $passwordHash,
    ':is_active' => 1,
    ':role' => 'borrower',
  ]);

  $insert->execute([
    ':first_name' => 'QA',
    ':last_name' => 'Inactive',
    ':email' => $inactiveEmail,
    ':password_hash' => $passwordHash,
    ':is_active' => 0,
    ':role' => 'borrower',
  ]);

  $auth = new AuthManager($db);

  $activeLogin = $auth->login($activeEmail, $password);
  $activeLoginPass = !empty($activeLogin['success']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'borrower';
  addResult($results, 'active_user_login', $activeLoginPass, $activeLoginPass ? 'Active borrower login works and session role is borrower.' : 'Active borrower login failed.');
  $allPass = $allPass && $activeLoginPass;

  AuthSupport::clearSession();
  AuthSupport::ensureSessionStarted();

  $inactiveLogin = $auth->login($inactiveEmail, $password);
  $inactiveBlocked = empty($inactiveLogin['success']) && isset($inactiveLogin['error']) && stripos((string)$inactiveLogin['error'], 'inactive') !== false;
  addResult($results, 'inactive_user_blocked', $inactiveBlocked, $inactiveBlocked ? 'Inactive account login is blocked.' : 'Inactive account was not blocked correctly.');
  $allPass = $allPass && $inactiveBlocked;

  $_SESSION['user_id'] = 999999;
  $_SESSION['user_role'] = 'borrower';
  $_SESSION['is_superadmin'] = false;
  $borrowerDeniedAdmin = isAdminAuthenticated() === false;
  addResult($results, 'borrower_denied_admin_gate', $borrowerDeniedAdmin, $borrowerDeniedAdmin ? 'Borrower is denied admin auth.' : 'Borrower incorrectly passed admin auth.');
  $allPass = $allPass && $borrowerDeniedAdmin;

  $_SESSION['user_role'] = 'admin';
  $adminAllowed = isAdminAuthenticated() === true;
  addResult($results, 'admin_allowed_admin_gate', $adminAllowed, $adminAllowed ? 'Admin role passes admin auth.' : 'Admin role did not pass admin auth.');
  $allPass = $allPass && $adminAllowed;

  $cleanup = $db->prepare('DELETE FROM users WHERE email IN (:active_email, :inactive_email)');
  $cleanup->execute([
    ':active_email' => $activeEmail,
    ':inactive_email' => $inactiveEmail,
  ]);

  AuthSupport::clearSession();

  echo json_encode([
    'success' => $allPass,
    'checks' => $results,
  ], JSON_PRETTY_PRINT) . PHP_EOL;

  exit($allPass ? 0 : 1);
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'error' => $e->getMessage(),
    'checks' => $results,
  ], JSON_PRETTY_PRINT) . PHP_EOL;
  exit(1);
}
