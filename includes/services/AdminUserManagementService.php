<?php

declare(strict_types=1);

require_once APP_ROOT . '/backend/classes/UserRepository.php';

class AdminUserManagementService
{
  /** @var PDO */
  private $db;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function handleAction(string $action, array $input, bool $isCurrentSuperadmin, ?int $superadminUserId): ?array
  {
    $action = trim($action);
    if ($action === '') {
      return null;
    }

    if ($action === 'create_user') {
      return $this->handleCreateUser($input, $isCurrentSuperadmin);
    }

    if ($action === 'update_user') {
      return $this->handleUpdateUser($input, $isCurrentSuperadmin, $superadminUserId);
    }

    if ($action === 'toggle_status') {
      return $this->handleToggleStatus($input, $superadminUserId);
    }

    if ($action === 'delete_user') {
      return $this->handleDeleteUser($input, $superadminUserId);
    }

    if ($action === 'edit_password') {
      return $this->handleEditPassword($input, $superadminUserId);
    }

    if ($action === 'reset_password') {
      return $this->handleResetPassword($input, $superadminUserId);
    }

    return null;
  }

  private function handleCreateUser(array $input, bool $isCurrentSuperadmin): array
  {
    $allowedRoles = UserRepository::MANAGED_ROLES;
    $firstName = trim((string)($input['first_name'] ?? ''));
    $lastName = trim((string)($input['last_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $role = UserRepository::normalizeRole($input['role'] ?? 'borrower');
    $password = (string)($input['password'] ?? '');
    $roleInformation = trim((string)($input['role_information'] ?? ''));
    $status = trim((string)($input['status'] ?? 'active'));
    $isActive = $status !== 'inactive';

    $errorMessage = null;
    if ($firstName === '' || $lastName === '') {
      $errorMessage = 'First and last name are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errorMessage = 'A valid email address is required.';
    } elseif (strlen($password) < 8) {
      $errorMessage = 'Temporary password must be at least 8 characters long.';
    } elseif (!in_array($role, $allowedRoles, true)) {
      $errorMessage = 'Unsupported role selected.';
    } elseif (UserRepository::emailExistsForOtherUser($this->db, $email, null)) {
      $errorMessage = 'This email already exists. Use a different email.';
    }

    if ($errorMessage !== null) {
      return [
        'type' => 'error',
        'title' => 'Create User Failed',
        'message' => $errorMessage,
      ];
    }

    try {
      UserRepository::createManagedUser($this->db, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS),
        'is_verified' => true,
        'is_active' => $isActive,
        'role' => $role,
        'role_information' => $roleInformation,
        'actor_is_superadmin' => $isCurrentSuperadmin,
      ]);

      return [
        'type' => 'success',
        'title' => 'User Created',
        'message' => 'User account and role assignment were created successfully.',
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::create_user error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Create User Failed',
        'message' => 'Unable to create user at this time.',
      ];
    }
  }

  private function handleUpdateUser(array $input, bool $isCurrentSuperadmin, ?int $superadminUserId): array
  {
    $allowedRoles = UserRepository::MANAGED_ROLES;
    $userId = (int)($input['user_id'] ?? 0);
    $firstName = trim((string)($input['first_name'] ?? ''));
    $lastName = trim((string)($input['last_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $role = UserRepository::normalizeRole($input['role'] ?? 'borrower');
    $roleInformation = trim((string)($input['role_information'] ?? ''));
    $status = trim((string)($input['status'] ?? 'active'));
    $isActive = $status !== 'inactive';

    $errorMessage = null;
    if ($userId <= 0) {
      $errorMessage = 'Invalid user selected.';
    } elseif ($firstName === '' || $lastName === '') {
      $errorMessage = 'First and last name are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errorMessage = 'A valid email address is required.';
    } elseif (!in_array($role, $allowedRoles, true)) {
      $errorMessage = 'Unsupported role selected.';
    } elseif (UserRepository::emailExistsForOtherUser($this->db, $email, $userId)) {
      $errorMessage = 'This email is already assigned to another user.';
    } elseif ($superadminUserId !== null && $userId === $superadminUserId && (!$isActive || $role !== 'admin')) {
      $errorMessage = 'Superadmin account role and active status are protected.';
    }

    if ($errorMessage !== null) {
      return [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => $errorMessage,
      ];
    }

    try {
      $updated = UserRepository::updateManagedUser($this->db, $userId, [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'role' => $role,
        'is_active' => $isActive,
        'role_information' => $roleInformation,
        'actor_is_superadmin' => $isCurrentSuperadmin,
      ]);

      if ($updated === null) {
        return [
          'type' => 'error',
          'title' => 'Update Failed',
          'message' => 'User not found.',
        ];
      }

      return [
        'type' => 'success',
        'title' => 'User Updated',
        'message' => 'User details and role were updated successfully.',
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::update_user error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Update Failed',
        'message' => 'Unable to update user at this time.',
      ];
    }
  }

  private function handleToggleStatus(array $input, ?int $superadminUserId): array
  {
    $userId = (int)($input['user_id'] ?? 0);
    $status = trim((string)($input['status'] ?? 'active'));
    $isActive = $status !== 'inactive';

    if ($userId <= 0) {
      return [
        'type' => 'error',
        'title' => 'Status Update Failed',
        'message' => 'Invalid user selected.',
      ];
    }

    if ($superadminUserId !== null && $userId === $superadminUserId && !$isActive) {
      return [
        'type' => 'error',
        'title' => 'Status Update Blocked',
        'message' => 'Superadmin account cannot be deactivated.',
      ];
    }

    try {
      $updated = UserRepository::setUserActiveState($this->db, $userId, $isActive);
      if ($updated === null) {
        return [
          'type' => 'error',
          'title' => 'Status Update Failed',
          'message' => 'User not found.',
        ];
      }

      return [
        'type' => 'success',
        'title' => 'Status Updated',
        'message' => $isActive ? 'User has been activated.' : 'User has been set to inactive.',
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::toggle_status error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Status Update Failed',
        'message' => stripos($e->getMessage(), 'superadmin') !== false
          ? $e->getMessage()
          : 'Unable to update user status right now.',
      ];
    }
  }

  private function handleDeleteUser(array $input, ?int $superadminUserId): array
  {
    $userId = (int)($input['user_id'] ?? 0);

    if ($userId <= 0) {
      return [
        'type' => 'error',
        'title' => 'Delete Failed',
        'message' => 'Invalid user selected.',
      ];
    }

    if ($superadminUserId !== null && $userId === $superadminUserId) {
      return [
        'type' => 'error',
        'title' => 'Delete Blocked',
        'message' => 'Superadmin account cannot be deleted.',
      ];
    }

    try {
      $deleted = UserRepository::deleteManagedUser($this->db, $userId);
      if ($deleted === null) {
        return [
          'type' => 'error',
          'title' => 'Delete Failed',
          'message' => 'User not found.',
        ];
      }

      return [
        'type' => 'success',
        'title' => 'User Deleted',
        'message' => 'User account was deleted successfully.',
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::delete_user error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Delete Failed',
        'message' => stripos($e->getMessage(), 'superadmin') !== false
          ? $e->getMessage()
          : 'Unable to delete user right now.',
      ];
    }
  }

  private function handleEditPassword(array $input, ?int $superadminUserId): array
  {
    $userId = (int)($input['user_id'] ?? 0);
    $newPassword = (string)($input['new_password'] ?? '');
    $confirmPassword = (string)($input['confirm_password'] ?? '');

    if ($userId <= 0) {
      return [
        'type' => 'error',
        'title' => 'Password Update Failed',
        'message' => 'Invalid user selected.',
      ];
    }

    if ($superadminUserId !== null && $userId === $superadminUserId) {
      return [
        'type' => 'error',
        'title' => 'Password Update Failed',
        'message' => 'Superadmin password cannot be changed through this interface.',
      ];
    }

    $validationError = $this->validatePassword($newPassword);
    if ($validationError !== null) {
      return [
        'type' => 'error',
        'title' => 'Password Validation Failed',
        'message' => $validationError,
      ];
    }

    if ($newPassword !== $confirmPassword) {
      return [
        'type' => 'error',
        'title' => 'Password Update Failed',
        'message' => 'Password and confirmation do not match.',
      ];
    }

    try {
      $newHash = password_hash($newPassword, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
      if ($newHash === false) {
        return [
          'type' => 'error',
          'title' => 'Password Update Failed',
          'message' => 'Unable to process password at this time.',
        ];
      }

      $updated = UserRepository::updateUserPassword($this->db, $userId, $newHash);
      if (!$updated) {
        return [
          'type' => 'error',
          'title' => 'Password Update Failed',
          'message' => 'User not found or password could not be updated.',
        ];
      }

      return [
        'type' => 'success',
        'title' => 'Password Updated',
        'message' => 'User password has been updated successfully.',
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::edit_password error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Password Update Failed',
        'message' => stripos($e->getMessage(), 'superadmin') !== false
          ? $e->getMessage()
          : 'Unable to update password at this time.',
      ];
    }
  }

  private function handleResetPassword(array $input, ?int $superadminUserId): array
  {
    $userId = (int)($input['user_id'] ?? 0);

    if ($userId <= 0) {
      return [
        'type' => 'error',
        'title' => 'Password Reset Failed',
        'message' => 'Invalid user selected.',
      ];
    }

    if ($superadminUserId !== null && $userId === $superadminUserId) {
      return [
        'type' => 'error',
        'title' => 'Password Reset Failed',
        'message' => 'Superadmin password cannot be reset through this interface.',
      ];
    }

    $tempPassword = $this->generateSecureTempPassword();

    try {
      $newHash = password_hash($tempPassword, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
      if ($newHash === false) {
        return [
          'type' => 'error',
          'title' => 'Password Reset Failed',
          'message' => 'Unable to process password reset at this time.',
        ];
      }

      $updated = UserRepository::updateUserPassword($this->db, $userId, $newHash);
      if (!$updated) {
        return [
          'type' => 'error',
          'title' => 'Password Reset Failed',
          'message' => 'User not found or password could not be reset.',
        ];
      }

      return [
        'type' => 'success',
        'title' => 'Password Reset',
        'message' => 'User password has been reset. Temporary password: ' . $tempPassword . ' (Share this securely with the user)',
        'temp_password' => $tempPassword,
      ];
    } catch (Exception $e) {
      error_log('AdminUserManagementService::reset_password error: ' . $e->getMessage());
      return [
        'type' => 'error',
        'title' => 'Password Reset Failed',
        'message' => stripos($e->getMessage(), 'superadmin') !== false
          ? $e->getMessage()
          : 'Unable to reset password at this time.',
      ];
    }
  }

  private function generateSecureTempPassword(): string
  {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $charsLength = strlen($chars);

    $password .= chr(random_int(ord('A'), ord('Z')));
    $password .= chr(random_int(ord('a'), ord('z')));
    $password .= chr(random_int(ord('0'), ord('9')));
    $password .= '!';

    for ($i = 0; $i < 8; $i++) {
      $password .= $chars[random_int(0, $charsLength - 1)];
    }

    $passwordArray = str_split($password);
    shuffle($passwordArray);
    return implode('', $passwordArray);
  }

  private function validatePassword(string $password): ?string
  {
    if ($password === '') {
      return 'Password is required.';
    }

    if (strlen($password) < 8) {
      return 'Password must be at least 8 characters long.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
      return 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
      return 'Password must contain at least one lowercase letter.';
    }

    if (!preg_match('/\d/', $password)) {
      return 'Password must contain at least one number.';
    }

    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
      return 'Password must contain at least one special character.';
    }

    return null;
  }
}
