  -- QueenLib Superadmin Seed
  -- Import this AFTER importing backend/config/schema.sql
    -- Unified login target:
    --   email: admin@local.admin
    --   password: admin123

  START TRANSACTION;

  -- 1) Upsert the protected superadmin user account (users table)
  INSERT INTO users (
      first_name,
      last_name,
      email,
      password_hash,
      is_verified,
      verification_token,
      verification_token_expires,
      reset_token,
      reset_token_expires,
      created_at,
      updated_at,
      last_login,
      is_active,
      role,
      is_superadmin
  )
  VALUES (
      'Super',
      'Admin',
      'admin@local.admin',
      '$2y$12$zMvTVCopOtJa/KGE15xWG.wuGEndjpdR48k3zWCv56mlsB501OXba',
      1,
      NULL,
      NULL,
      NULL,
      NULL,
      NOW(),
      NOW(),
      NULL,
      1,
      'admin',
      1
  )
  ON DUPLICATE KEY UPDATE
      first_name = VALUES(first_name),
      last_name = VALUES(last_name),
      password_hash = VALUES(password_hash),
      is_verified = 1,
      is_active = 1,
      role = 'admin',
      is_superadmin = 1,
      updated_at = NOW();

  -- 2) Keep only one protected superadmin identity
  UPDATE users
  SET is_superadmin = CASE
      WHEN email = 'admin@local.admin' THEN 1
      ELSE 0
  END,
  role = CASE
      WHEN email = 'admin@local.admin' THEN 'admin'
      ELSE role
  END,
  updated_at = NOW()
  WHERE is_superadmin = 1 OR email = 'admin@local.admin';

  -- 3) Ensure role profile exists for admin user management views
  INSERT INTO role_profiles (
      user_id,
      role,
      role_information,
      created_at,
      updated_at
  )
  SELECT
      u.id,
      'admin',
      'System superadmin account',
      NOW(),
      NOW()
  FROM users u
  WHERE u.email = 'admin@local.admin'
  LIMIT 1
  ON DUPLICATE KEY UPDATE
      role = VALUES(role),
      role_information = VALUES(role_information),
      updated_at = NOW();

  -- 4) Ensure admin profile exists
  INSERT INTO admin_profiles (
      admin_username,
      full_name,
      email,
      phone,
      address,
      appointment_date,
      access_level,
      created_at,
      updated_at
  )
  VALUES (
      'admin@local.admin',
      'System Administrator',
      'admin@local.admin',
      '(555) 123-4567',
      'Administrator Office',
      CURDATE(),
      'Full Access - Super Administrator',
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
      updated_at = NOW();

  COMMIT;
