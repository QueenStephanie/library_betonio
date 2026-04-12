-- Library Betonium Database Schema
-- MySQL 15.1 (MariaDB)
-- PHP 8.2.12
-- NOTE: Select your database in phpMyAdmin before importing this file

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    verification_token_expires DATETIME NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('admin', 'librarian', 'borrower') NOT NULL DEFAULT 'borrower',
    is_superadmin BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-specific profile store (one active role profile per user)
CREATE TABLE IF NOT EXISTS role_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role ENUM('admin', 'librarian', 'borrower') NOT NULL,
    role_information TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_role_profiles_user (user_id),
    INDEX idx_role_profiles_role (role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Verification Attempts Table (for rate limiting)
CREATE TABLE IF NOT EXISTS verification_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    attempt_type ENUM('password_reset') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    is_successful BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_type (email, attempt_type),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Login History Table (Security Audit)
CREATE TABLE IF NOT EXISTS login_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time DATETIME NULL,
    is_successful BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_login (user_id, login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Profile Store (separate from credentials)
CREATE TABLE IF NOT EXISTS admin_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_username VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    address VARCHAR(255) NOT NULL,
    appointment_date DATE NOT NULL,
    access_level VARCHAR(150) NOT NULL DEFAULT 'Full Access - Super Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admin_profiles_username (admin_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fine Collection Events (admin month-to-date reporting source)
CREATE TABLE IF NOT EXISTS fine_collections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    borrower_user_id INT NULL,
    collected_by_user_id INT NULL,
    receipt_code VARCHAR(64) NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('collected', 'voided') NOT NULL DEFAULT 'collected',
    notes VARCHAR(255) NULL,
    collected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (borrower_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (collected_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_fine_collections_collected_at (collected_at),
    INDEX idx_fine_collections_status (status),
    INDEX idx_fine_collections_collector (collected_by_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for Performance and Security
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_verification_token ON users(verification_token);
CREATE INDEX idx_users_reset_token ON users(reset_token);
CREATE INDEX idx_users_is_verified ON users(is_verified);
CREATE INDEX idx_users_role ON users(role);

-- ---------------------------------------------------------------------------
-- Superadmin seed (safe for first import)
-- ---------------------------------------------------------------------------
-- Seed password (user account): admin123
-- Change this password after first login if you use this account.
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
SELECT
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
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM users
    WHERE is_superadmin = 1
    LIMIT 1
)
ON DUPLICATE KEY UPDATE
    role = 'admin',
    is_verified = 1,
    is_active = 1,
    is_superadmin = 1,
    updated_at = NOW();

-- Ensure superadmin has a role profile for admin dashboard role governance.
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
WHERE u.is_superadmin = 1
ORDER BY u.id ASC
LIMIT 1
ON DUPLICATE KEY UPDATE
    role = VALUES(role),
    role_information = VALUES(role_information),
    updated_at = NOW();
