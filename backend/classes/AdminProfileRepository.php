<?php

/**
 * Repository for admin profile persistence.
 */
class AdminProfileRepository
{
  public static function getOrCreate(PDO $db, $adminUsername)
  {
    $adminUsername = trim((string)$adminUsername);
    if ($adminUsername === '') {
      $adminUsername = 'admin';
    }

    $profile = self::findByUsername($db, $adminUsername);
    if ($profile) {
      return $profile;
    }

    $defaults = self::buildDefaultProfile($adminUsername);
    self::upsertByUsername($db, $adminUsername, $defaults);
    $profile = self::findByUsername($db, $adminUsername);

    return $profile ?: self::mapForView($defaults + [
      'admin_username' => $adminUsername,
      'admin_credential_id' => null,
      'credential_created_at' => null,
    ]);
  }

  public static function findByUsername(PDO $db, $adminUsername)
  {
    $stmt = $db->prepare(
      'SELECT
        ap.admin_username,
        ap.full_name,
        ap.email,
        ap.phone,
        ap.address,
        ap.appointment_date,
        ap.access_level,
        ac.id AS admin_credential_id,
        ac.created_at AS credential_created_at
      FROM admin_profiles ap
      LEFT JOIN admin_credentials ac ON ac.username = ap.admin_username
      WHERE ap.admin_username = :admin_username
      LIMIT 1'
    );
    $stmt->execute([':admin_username' => $adminUsername]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
      return null;
    }

    return self::mapForView($row);
  }

  public static function upsertByUsername(PDO $db, $adminUsername, array $payload)
  {
    $appointmentDate = trim((string)($payload['appointment_date'] ?? ''));
    if ($appointmentDate === '') {
      $appointmentDate = date('Y-m-d');
    }

    $stmt = $db->prepare(
      'INSERT INTO admin_profiles (
        admin_username,
        full_name,
        email,
        phone,
        address,
        appointment_date,
        access_level,
        created_at,
        updated_at
      ) VALUES (
        :admin_username,
        :full_name,
        :email,
        :phone,
        :address,
        :appointment_date,
        :access_level,
        NOW(),
        NOW()
      )
      ON DUPLICATE KEY UPDATE
        full_name = VALUES(full_name),
        email = VALUES(email),
        phone = VALUES(phone),
        address = VALUES(address),
        appointment_date = VALUES(appointment_date),
        access_level = VALUES(access_level),
        updated_at = NOW()'
    );

    $stmt->execute([
      ':admin_username' => $adminUsername,
      ':full_name' => trim((string)($payload['full_name'] ?? 'System Administrator')),
      ':email' => strtolower(trim((string)($payload['email'] ?? 'admin@libris.com'))),
      ':phone' => trim((string)($payload['phone'] ?? '(000) 000-0000')),
      ':address' => trim((string)($payload['address'] ?? 'Administrator Office')),
      ':appointment_date' => $appointmentDate,
      ':access_level' => trim((string)($payload['access_level'] ?? 'Full Access - Super Administrator')),
    ]);
  }

  private static function mapForView(array $row)
  {
    $appointmentDateRaw = (string)($row['appointment_date'] ?? date('Y-m-d'));
    $appointmentDateDisplay = $appointmentDateRaw;
    $timestamp = strtotime($appointmentDateRaw);
    if ($timestamp !== false) {
      $appointmentDateDisplay = date('F j, Y', $timestamp);
    }

    $credentialId = isset($row['admin_credential_id']) ? (int)$row['admin_credential_id'] : 0;
    $adminId = $credentialId > 0 ? sprintf('ADM-%04d', $credentialId) : 'ADM-BOOTSTRAP';

    return [
      'admin_username' => (string)($row['admin_username'] ?? 'admin'),
      'name' => (string)($row['full_name'] ?? 'System Administrator'),
      'email' => (string)($row['email'] ?? 'admin@libris.com'),
      'phone' => (string)($row['phone'] ?? '(000) 000-0000'),
      'address' => (string)($row['address'] ?? 'Administrator Office'),
      'appointment_date' => $appointmentDateDisplay,
      'appointment_date_value' => $appointmentDateRaw,
      'access_level' => (string)($row['access_level'] ?? 'Full Access - Super Administrator'),
      'admin_id' => $adminId,
      'credential_created_at' => $row['credential_created_at'] ?? null,
    ];
  }

  private static function buildDefaultProfile($adminUsername)
  {
    return [
      'full_name' => 'System Administrator',
      'email' => strtolower($adminUsername) . '@libris.com',
      'phone' => '(555) 123-4567',
      'address' => '456 Admin Boulevard, Central City',
      'appointment_date' => date('Y-m-d'),
      'access_level' => 'Full Access - Super Administrator',
    ];
  }
}
