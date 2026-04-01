<?php

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../backend/classes/UserRepository.php';
require_once __DIR__ . '/../../../backend/classes/AdminProfileRepository.php';
require_once __DIR__ . '/../../../backend/classes/FineReporting.php';

$results = [
  'role_cleanup' => false,
  'profile_persistence' => false,
  'fines_mtd_report' => false,
  'errors' => [],
];

$tempUserId = null;
$tempReceipt = 'SMOKE-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
$tempEmail = 'smoke+' . strtolower(substr(bin2hex(random_bytes(4)), 0, 8)) . '@example.test';

try {
  $created = UserRepository::createManagedUser($db, [
    'first_name' => 'Smoke',
    'last_name' => 'Test',
    'email' => $tempEmail,
    'password_hash' => password_hash('SmokePass123!', PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS),
    'is_verified' => true,
    'is_active' => true,
    'role' => 'borrower',
    'role_information' => 'Borrower baseline',
  ]);

  $tempUserId = (int)$created['id'];

  UserRepository::updateManagedUser($db, $tempUserId, [
    'first_name' => 'Smoke',
    'last_name' => 'Test',
    'email' => $tempEmail,
    'role' => 'librarian',
    'is_active' => true,
    'role_information' => 'Desk shift A',
  ]);

  $roleStmt = $db->prepare('SELECT role, role_information FROM role_profiles WHERE user_id = :user_id');
  $roleStmt->execute([':user_id' => $tempUserId]);
  $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
  $results['role_cleanup'] = count($roles) === 1 && $roles[0]['role'] === 'librarian' && $roles[0]['role_information'] === 'Desk shift A';

  AdminProfileRepository::upsertByUsername($db, ADMIN_USERNAME, [
    'full_name' => 'System Administrator',
    'email' => 'admin@libris.com',
    'phone' => '(555) 000-1111',
    'address' => 'Smoke Test Office',
    'appointment_date' => date('Y-m-d'),
    'access_level' => 'Full Access - Super Administrator',
  ]);

  $profile = AdminProfileRepository::getOrCreate($db, ADMIN_USERNAME);
  $results['profile_persistence'] = isset($profile['phone']) && $profile['phone'] === '(555) 000-1111';

  $fineStmt = $db->prepare(
    'INSERT INTO fine_collections (borrower_user_id, collected_by_user_id, receipt_code, amount, status, notes, collected_at)
     VALUES (:borrower_user_id, :collected_by_user_id, :receipt_code, :amount, :status, :notes, NOW())'
  );
  $fineStmt->execute([
    ':borrower_user_id' => $tempUserId,
    ':collected_by_user_id' => $tempUserId,
    ':receipt_code' => $tempReceipt,
    ':amount' => 9.99,
    ':status' => 'collected',
    ':notes' => 'Smoke validation entry',
  ]);

  $report = FineReporting::getMonthToDateReport($db);
  $found = false;
  foreach ($report['items'] as $item) {
    if (($item['receipt_code'] ?? '') === $tempReceipt) {
      $found = true;
      break;
    }
  }
  $results['fines_mtd_report'] = $found;
} catch (Exception $e) {
  $results['errors'][] = $e->getMessage();
}

if ($tempUserId !== null) {
  try {
    $cleanupFine = $db->prepare('DELETE FROM fine_collections WHERE receipt_code = :receipt_code');
    $cleanupFine->execute([':receipt_code' => $tempReceipt]);
  } catch (Exception $e) {
    $results['errors'][] = 'fine_cleanup: ' . $e->getMessage();
  }

  try {
    $cleanupUser = $db->prepare('DELETE FROM users WHERE id = :id');
    $cleanupUser->execute([':id' => $tempUserId]);
  } catch (Exception $e) {
    $results['errors'][] = 'user_cleanup: ' . $e->getMessage();
  }
}

echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;
