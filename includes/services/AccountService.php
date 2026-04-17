<?php

declare(strict_types=1);

class AccountService
{
  /** @var PDO */
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function updateProfile(int $userId, string $firstName, string $lastName): array
  {
    if ($userId <= 0) {
      return ['success' => false, 'error' => 'Failed to update profile'];
    }

    try {
      $query = 'UPDATE users SET first_name = :first_name, last_name = :last_name WHERE id = :id';
      $stmt = $this->db->prepare($query);
      $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':id' => $userId,
      ]);

      return ['success' => true];
    } catch (Exception $e) {
      error_log('AccountService::updateProfile error: ' . $e->getMessage());
      return ['success' => false, 'error' => 'Failed to update profile'];
    }
  }

  public function changePassword(int $userId, string $currentPassword, string $newPassword, string $newPasswordConfirm): array
  {
    if ($currentPassword === '' || $newPassword === '') {
      return ['success' => false, 'error' => 'All password fields are required'];
    }

    if ($newPassword !== $newPasswordConfirm) {
      return ['success' => false, 'error' => 'New passwords do not match'];
    }

    if (strlen($newPassword) < 8) {
      return ['success' => false, 'error' => 'New password must be at least 8 characters'];
    }

    if ($userId <= 0) {
      return ['success' => false, 'error' => 'Failed to change password'];
    }

    try {
      $query = 'SELECT password_hash FROM users WHERE id = :id';
      $stmt = $this->db->prepare($query);
      $stmt->execute([':id' => $userId]);
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!is_array($result) || !isset($result['password_hash']) || !password_verify($currentPassword, (string)$result['password_hash'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
      }

      $newHash = password_hash($newPassword, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
      $updateQuery = 'UPDATE users SET password_hash = :password WHERE id = :id';
      $updateStmt = $this->db->prepare($updateQuery);
      $updateStmt->execute([
        ':password' => $newHash,
        ':id' => $userId,
      ]);

      return ['success' => true];
    } catch (Exception $e) {
      error_log('AccountService::changePassword error: ' . $e->getMessage());
      return ['success' => false, 'error' => 'Failed to change password'];
    }
  }
}
