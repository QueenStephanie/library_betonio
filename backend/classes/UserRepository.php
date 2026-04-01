<?php

/**
 * Shared user data access helpers for auth flows.
 */
class UserRepository
{
  const MANAGED_ROLES = ['admin', 'librarian', 'borrower'];

  private static function assertActorCanManageRole($actorIsSuperadmin, $role)
  {
    // Backward compatibility: enforce only when caller explicitly provides actor context.
    if ($actorIsSuperadmin === null) {
      return;
    }

    $normalizedRole = self::normalizeRole($role);
    if (in_array($normalizedRole, self::MANAGED_ROLES, true) && $actorIsSuperadmin !== true) {
      throw new RuntimeException('Only superadmin can create or update borrower, librarian, and admin profiles.');
    }
  }

  public static function findByEmail(PDO $db, $table, $email, array $columns = ['*'], $onlyActive = false)
  {
    $column_sql = implode(', ', $columns);
    $query = "SELECT {$column_sql} FROM {$table} WHERE email = :email";

    if ($onlyActive) {
      $query .= " AND is_active = 1";
    }

    $query .= " LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);

    if ($stmt->rowCount() === 0) {
      return null;
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function findById(PDO $db, $table, $id, array $columns = ['*'])
  {
    $column_sql = implode(', ', $columns);
    $query = "SELECT {$column_sql} FROM {$table} WHERE id = :id LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
      return null;
    }

    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public static function normalizeRole($role)
  {
    $normalized = strtolower(trim((string)$role));
    if (!in_array($normalized, self::MANAGED_ROLES, true)) {
      return 'borrower';
    }

    return $normalized;
  }

  public static function listManagedUsers(PDO $db, $includeInactive = true)
  {
    $query =
      'SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.role,
        u.is_active,
        u.last_login,
        u.created_at,
        rp.role_information
      FROM users u
      LEFT JOIN role_profiles rp ON rp.user_id = u.id';

    if (!$includeInactive) {
      $query .= ' WHERE u.is_active = 1';
    }

    $query .= ' ORDER BY u.created_at DESC, u.id DESC';

    $stmt = $db->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getManagedUserById(PDO $db, $userId)
  {
    $stmt = $db->prepare(
      'SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.role,
        u.is_active,
        u.last_login,
        u.created_at,
        rp.role_information
      FROM users u
      LEFT JOIN role_profiles rp ON rp.user_id = u.id
      WHERE u.id = :id
      LIMIT 1'
    );
    $stmt->execute([':id' => (int)$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
  }

  public static function emailExistsForOtherUser(PDO $db, $email, $excludeUserId = null)
  {
    $email = strtolower(trim((string)$email));
    $params = [':email' => $email];
    $query = 'SELECT id FROM users WHERE email = :email';

    if ($excludeUserId !== null) {
      $query .= ' AND id <> :exclude_id';
      $params[':exclude_id'] = (int)$excludeUserId;
    }

    $query .= ' LIMIT 1';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return (bool)$stmt->fetchColumn();
  }

  public static function getSuperadminUser(PDO $db)
  {
    try {
      $stmt = $db->query(
        'SELECT
          u.id,
          u.first_name,
          u.last_name,
          u.email,
          u.role,
          u.is_active,
          u.is_superadmin,
          u.last_login,
          u.created_at,
          rp.role_information
        FROM users u
        LEFT JOIN role_profiles rp ON rp.user_id = u.id
        WHERE u.is_superadmin = 1
        ORDER BY u.id ASC
        LIMIT 1'
      );

      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      return $row ?: null;
    } catch (Exception $e) {
      error_log('UserRepository::getSuperadminUser error: ' . $e->getMessage());
      return null;
    }
  }

  public static function isSuperadminUser(PDO $db, $userId)
  {
    try {
      $stmt = $db->prepare(
        'SELECT is_superadmin
         FROM users
         WHERE id = :id
         LIMIT 1'
      );
      $stmt->execute([':id' => (int)$userId]);
      $value = $stmt->fetchColumn();
      return (int)$value === 1;
    } catch (Exception $e) {
      error_log('UserRepository::isSuperadminUser error: ' . $e->getMessage());
      return false;
    }
  }

  public static function createManagedUser(PDO $db, array $payload)
  {
    $role = self::normalizeRole($payload['role'] ?? 'borrower');
    $actorIsSuperadmin = array_key_exists('actor_is_superadmin', $payload) ? (bool)$payload['actor_is_superadmin'] : null;
    self::assertActorCanManageRole($actorIsSuperadmin, $role);
    $isActive = !empty($payload['is_active']) ? 1 : 0;
    $roleInformation = trim((string)($payload['role_information'] ?? ''));

    $stmt = $db->prepare(
      'INSERT INTO users (
        first_name,
        last_name,
        email,
        password_hash,
        is_verified,
        created_at,
        updated_at,
        is_active,
        role
      ) VALUES (
        :first_name,
        :last_name,
        :email,
        :password_hash,
        :is_verified,
        NOW(),
        NOW(),
        :is_active,
        :role
      )'
    );

    $db->beginTransaction();
    try {
      $stmt->execute([
        ':first_name' => trim((string)$payload['first_name']),
        ':last_name' => trim((string)$payload['last_name']),
        ':email' => strtolower(trim((string)$payload['email'])),
        ':password_hash' => (string)$payload['password_hash'],
        ':is_verified' => !empty($payload['is_verified']) ? 1 : 0,
        ':is_active' => $isActive,
        ':role' => $role,
      ]);

      $userId = (int)$db->lastInsertId();
      self::replaceRoleProfile($db, $userId, $role, $roleInformation);

      $db->commit();
      return self::getManagedUserById($db, $userId);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }
  }

  public static function updateManagedUser(PDO $db, $userId, array $payload)
  {
    $current = self::getManagedUserById($db, $userId);
    if (!$current) {
      return null;
    }

    $nextRole = self::normalizeRole($payload['role'] ?? $current['role']);
    $actorIsSuperadmin = array_key_exists('actor_is_superadmin', $payload) ? (bool)$payload['actor_is_superadmin'] : null;
    self::assertActorCanManageRole($actorIsSuperadmin, $nextRole);
    $isActive = !empty($payload['is_active']) ? 1 : 0;
    $roleInformation = trim((string)($payload['role_information'] ?? ''));
    $roleChanged = $nextRole !== $current['role'];

    $stmt = $db->prepare(
      'UPDATE users
       SET
        first_name = :first_name,
        last_name = :last_name,
        email = :email,
        role = :role,
        is_active = :is_active,
        updated_at = NOW()
       WHERE id = :id
       LIMIT 1'
    );

    $db->beginTransaction();
    try {
      $stmt->execute([
        ':first_name' => trim((string)$payload['first_name']),
        ':last_name' => trim((string)$payload['last_name']),
        ':email' => strtolower(trim((string)$payload['email'])),
        ':role' => $nextRole,
        ':is_active' => $isActive,
        ':id' => (int)$userId,
      ]);

      if ($roleChanged) {
        self::replaceRoleProfile($db, $userId, $nextRole, $roleInformation);
      } else {
        self::upsertRoleProfile($db, $userId, $nextRole, $roleInformation);
      }

      $db->commit();
      return self::getManagedUserById($db, $userId);
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }
  }

  public static function setUserActiveState(PDO $db, $userId, $isActive)
  {
    $userId = (int)$userId;
    if (!$isActive && self::isSuperadminUser($db, $userId)) {
      throw new RuntimeException('Superadmin account cannot be deactivated.');
    }

    $stmt = $db->prepare(
      'UPDATE users
       SET is_active = :is_active, updated_at = NOW()
       WHERE id = :id
       LIMIT 1'
    );
    $stmt->execute([
      ':is_active' => $isActive ? 1 : 0,
      ':id' => $userId,
    ]);

    return self::getManagedUserById($db, $userId);
  }

  public static function deleteManagedUser(PDO $db, $userId)
  {
    $userId = (int)$userId;
    $current = self::getManagedUserById($db, $userId);
    if (!$current) {
      return null;
    }

    if (self::isSuperadminUser($db, $userId)) {
      throw new RuntimeException('Superadmin account cannot be deleted.');
    }

    $db->beginTransaction();
    try {
      $deleteRole = $db->prepare('DELETE FROM role_profiles WHERE user_id = :user_id');
      $deleteRole->execute([':user_id' => $userId]);

      $deleteUser = $db->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
      $deleteUser->execute([':id' => $userId]);

      $db->commit();
      return $current;
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      throw $e;
    }
  }

  private static function replaceRoleProfile(PDO $db, $userId, $role, $roleInformation)
  {
    $deleteStmt = $db->prepare('DELETE FROM role_profiles WHERE user_id = :user_id');
    $deleteStmt->execute([':user_id' => (int)$userId]);

    self::upsertRoleProfile($db, $userId, $role, $roleInformation);
  }

  private static function upsertRoleProfile(PDO $db, $userId, $role, $roleInformation)
  {
    $stmt = $db->prepare(
      'INSERT INTO role_profiles (user_id, role, role_information, created_at, updated_at)
       VALUES (:user_id, :role, :role_information, NOW(), NOW())
       ON DUPLICATE KEY UPDATE
         role = VALUES(role),
         role_information = VALUES(role_information),
         updated_at = NOW()'
    );
    $stmt->execute([
      ':user_id' => (int)$userId,
      ':role' => self::normalizeRole($role),
      ':role_information' => $roleInformation,
    ]);
  }
}
