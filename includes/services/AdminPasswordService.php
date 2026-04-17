<?php

declare(strict_types=1);

class AdminPasswordService
{
  /** @var PDO */
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function changePassword(int $currentUserId, string $currentPassword, string $newPassword, string $confirmPassword): array
  {
    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
      return ['success' => false, 'error' => 'All password fields are required.'];
    }

    if (strlen($newPassword) < 8) {
      return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
    }

    if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/\d/', $newPassword) || !preg_match('/[^a-zA-Z\d]/', $newPassword)) {
      return ['success' => false, 'error' => 'New password must include uppercase, lowercase, number, and special character.'];
    }

    if ($newPassword !== $confirmPassword) {
      return ['success' => false, 'error' => 'New password and confirmation do not match.'];
    }

    if (hash_equals($currentPassword, $newPassword)) {
      return ['success' => false, 'error' => 'New password must be different from the current password.'];
    }

    if ($currentUserId <= 0) {
      return ['success' => false, 'error' => 'Unable to resolve the current user session.'];
    }

    try {
      $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
      $stmt->execute([':id' => $currentUserId]);
      $currentHash = (string)$stmt->fetchColumn();

      if ($currentHash === '' || !password_verify($currentPassword, $currentHash)) {
        return ['success' => false, 'error' => 'Current password verification failed.'];
      }

      $newHash = password_hash($newPassword, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
      if ($newHash === false) {
        return ['success' => false, 'error' => 'Unable to update password at this time.'];
      }

      $update = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id LIMIT 1');
      $update->execute([
        ':password_hash' => $newHash,
        ':id' => $currentUserId,
      ]);

      return ['success' => true, 'message' => 'Password updated successfully.'];
    } catch (Exception $e) {
      error_log('AdminPasswordService::changePassword error: ' . $e->getMessage());
      return ['success' => false, 'error' => 'Unable to update password at this time.'];
    }
  }
}
