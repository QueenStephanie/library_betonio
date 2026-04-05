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

-- Admin Credential Store (DB-primary admin authentication)
CREATE TABLE IF NOT EXISTS admin_credentials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    password_changed_at DATETIME NULL,
    INDEX idx_admin_credentials_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Session Registry (for cross-session invalidation)
CREATE TABLE IF NOT EXISTS admin_session_registry (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_identity VARCHAR(120) NOT NULL,
    admin_credential_id INT NULL,
    session_id_hash CHAR(64) NOT NULL UNIQUE,
    auth_mode ENUM('db', 'bootstrap_env') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NULL,
    invalidated_at DATETIME NULL,
    FOREIGN KEY (admin_credential_id) REFERENCES admin_credentials(id) ON DELETE SET NULL,
    INDEX idx_admin_session_identity (admin_identity),
    INDEX idx_admin_session_active (admin_identity, invalidated_at)
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
