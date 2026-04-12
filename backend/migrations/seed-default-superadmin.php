<?php

require_once __DIR__ . '/../../includes/config.php';

$hash = '$2y$12$zMvTVCopOtJa/KGE15xWG.wuGEndjpdR48k3zWCv56mlsB501OXba';

$beforeStmt = $db->query("SELECT id,email,role,is_superadmin,is_active,is_verified FROM users WHERE email='admin@local.admin' LIMIT 1");
$before = $beforeStmt->fetch(PDO::FETCH_ASSOC);

echo 'BEFORE=' . ($before ? json_encode($before) : 'NOT_FOUND') . PHP_EOL;

$db->beginTransaction();

$stmt = $db->prepare(
  "INSERT INTO users (
    first_name,last_name,email,password_hash,is_verified,verification_token,verification_token_expires,
    reset_token,reset_token_expires,created_at,updated_at,last_login,is_active,role,is_superadmin
  ) VALUES (
    'Super','Admin','admin@local.admin',:hash,1,NULL,NULL,NULL,NULL,NOW(),NOW(),NULL,1,'admin',1
  ) ON DUPLICATE KEY UPDATE
    first_name=VALUES(first_name),
    last_name=VALUES(last_name),
    password_hash=VALUES(password_hash),
    is_verified=1,
    is_active=1,
    role='admin',
    is_superadmin=1,
    updated_at=NOW()"
);
$stmt->execute([':hash' => $hash]);

$db->exec("UPDATE users
  SET is_superadmin = CASE WHEN email='admin@local.admin' THEN 1 ELSE 0 END,
      role = CASE WHEN email='admin@local.admin' THEN 'admin' ELSE role END,
      updated_at = NOW()
  WHERE is_superadmin = 1 OR email='admin@local.admin'");

$db->exec("INSERT INTO role_profiles (user_id, role, role_information, created_at, updated_at)
  SELECT u.id, 'admin', 'System superadmin account', NOW(), NOW()
  FROM users u WHERE u.email='admin@local.admin' LIMIT 1
  ON DUPLICATE KEY UPDATE
    role=VALUES(role),
    role_information=VALUES(role_information),
    updated_at=NOW()");

$db->exec("INSERT INTO admin_profiles (
    admin_username, full_name, email, phone, address, appointment_date, access_level, created_at, updated_at
  ) VALUES (
    'admin@local.admin','System Administrator','admin@local.admin','(555) 123-4567','Administrator Office',CURDATE(),'Full Access - Super Administrator',NOW(),NOW()
  ) ON DUPLICATE KEY UPDATE
    full_name=VALUES(full_name),
    email=VALUES(email),
    phone=VALUES(phone),
    address=VALUES(address),
    appointment_date=VALUES(appointment_date),
    access_level=VALUES(access_level),
    updated_at=NOW()");

$db->commit();

$afterStmt = $db->query("SELECT u.id,u.email,u.role,u.is_superadmin,u.is_active,u.is_verified,rp.role AS role_profile_role FROM users u LEFT JOIN role_profiles rp ON rp.user_id=u.id WHERE u.email='admin@local.admin' LIMIT 1");
$after = $afterStmt->fetch(PDO::FETCH_ASSOC);
$passwordHash = (string)$db->query("SELECT password_hash FROM users WHERE email='admin@local.admin' LIMIT 1")->fetchColumn();

echo 'AFTER=' . json_encode($after) . PHP_EOL;
echo 'PASSWORD_CHECK=' . (password_verify('admin123', $passwordHash) ? 'OK' : 'FAIL') . PHP_EOL;
