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
    attempt_type ENUM('password_reset', 'registration', 'login_attempt', 'password_reset_verify', 'otp_verify', 'otp_resend', 'csrf_reject', 'login_blocked') NOT NULL,
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

-- Applied SQL migration history
CREATE TABLE IF NOT EXISTS schema_migrations (
    migration_name VARCHAR(255) NOT NULL,
    checksum CHAR(64) NOT NULL,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (migration_name)
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

-- Fine Assessments (tracking fines assessed on overdue loans)
CREATE TABLE IF NOT EXISTS fine_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    borrower_user_id INT NULL,
    assessed_by_user_id INT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reason VARCHAR(255) NULL,
    status ENUM('pending', 'paid', 'waived', 'voided') NOT NULL DEFAULT 'pending',
    assessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    waived_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (borrower_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assessed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_fine_assessments_loan (loan_id),
    INDEX idx_fine_assessments_borrower (borrower_user_id),
    INDEX idx_fine_assessments_status (status),
    INDEX idx_fine_assessments_assessed_at (assessed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generic transaction receipts for circulation and financial events
CREATE TABLE IF NOT EXISTS transaction_receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    receipt_code VARCHAR(64) NOT NULL,
    transaction_type VARCHAR(64) NOT NULL,
    transaction_ref_id INT NOT NULL,
    borrower_user_id INT NULL,
    actor_user_id INT NULL,
    amount DECIMAL(10,2) NULL,
    payload_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_transaction_receipts_code (receipt_code),
    UNIQUE KEY uq_transaction_receipts_tx_ref (transaction_type, transaction_ref_id),
    INDEX idx_transaction_receipts_created_at (created_at),
    INDEX idx_transaction_receipts_borrower (borrower_user_id),
    INDEX idx_transaction_receipts_actor (actor_user_id),
    FOREIGN KEY (borrower_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Books Catalog
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(32) NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    published_year SMALLINT NULL,
    cover_image_url VARCHAR(1024) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_books_isbn (isbn),
    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Physical inventory copies
CREATE TABLE IF NOT EXISTS book_copies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    barcode VARCHAR(64) NOT NULL,
    status ENUM('available', 'reserved', 'loaned', 'lost', 'damaged', 'maintenance') NOT NULL DEFAULT 'available',
    acquired_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_book_copies_barcode (barcode),
    INDEX idx_book_copies_status (status),
    INDEX idx_book_copies_book_id (book_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Borrower reservation queue
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('pending', 'ready', 'fulfilled', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
    queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ready_until DATETIME NULL,
    picked_up_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reservations_user_status (user_id, status),
    INDEX idx_reservations_book_status (book_id, status),
    INDEX idx_reservations_queued_at (queued_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Active and historical loan records
CREATE TABLE IF NOT EXISTS loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_copy_id INT NOT NULL,
    reservation_id INT NULL,
    loan_status ENUM('active', 'returned', 'overdue', 'lost', 'void') NOT NULL DEFAULT 'active',
    checked_out_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    due_at DATETIME NOT NULL,
    returned_at DATETIME NULL,
    renewal_count INT NOT NULL DEFAULT 0,
    fine_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_loans_user_status (user_id, loan_status),
    INDEX idx_loans_due_status (due_at, loan_status),
    INDEX idx_loans_copy_status (book_copy_id, loan_status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_copy_id) REFERENCES book_copies(id) ON DELETE RESTRICT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable event history for loan lifecycle
CREATE TABLE IF NOT EXISTS loan_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    event_type ENUM('checkout', 'renewal', 'return', 'mark_overdue', 'mark_lost', 'void') NOT NULL,
    actor_user_id INT NULL,
    notes VARCHAR(255) NULL,
    event_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_loan_events_loan_time (loan_id, event_at),
    INDEX idx_loan_events_actor (actor_user_id),
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
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
