<?php

/**
 * Shared user data access helpers for auth flows.
 */
class UserRepository
{
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
}
