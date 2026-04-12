-- Migration: create minimal circulation core tables for borrower/admin dashboard widgets.
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(32) NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    category VARCHAR(100) NULL,
    published_year SMALLINT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_books_isbn (isbn),
    INDEX idx_books_title (title),
    INDEX idx_books_author (author),
    INDEX idx_books_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('pending', 'ready_for_pickup', 'fulfilled', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
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

INSERT INTO books (isbn, title, author, category, is_active)
SELECT '9780141439600', 'Pride and Prejudice', 'Jane Austen', 'Classic', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE isbn = '9780141439600' LIMIT 1);

INSERT INTO books (isbn, title, author, category, is_active)
SELECT '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM books WHERE isbn = '9780743273565' LIMIT 1);

INSERT INTO book_copies (book_id, barcode, status, acquired_at)
SELECT b.id, 'QB-PP-001', 'available', CURDATE()
FROM books b
WHERE b.isbn = '9780141439600'
  AND NOT EXISTS (SELECT 1 FROM book_copies WHERE barcode = 'QB-PP-001' LIMIT 1)
LIMIT 1;

INSERT INTO book_copies (book_id, barcode, status, acquired_at)
SELECT b.id, 'QB-TGG-001', 'available', CURDATE()
FROM books b
WHERE b.isbn = '9780743273565'
  AND NOT EXISTS (SELECT 1 FROM book_copies WHERE barcode = 'QB-TGG-001' LIMIT 1)
LIMIT 1;
