-- ============================================================================
-- Clear Books & Related Data
-- ============================================================================
-- WARNING: This will delete ALL books, copies, reservations, loans, and
-- related events. Run only when you want to reset the book catalog.
-- ============================================================================
-- Order matters due to foreign key constraints:
--   1. loan_events     (FK → loans)
--   2. loans           (FK → book_copies, RESTRICT on delete)
--   3. fine_assessments (FK → loans)
--   4. fine_collections (FK → users, no book FK)
--   5. reservations    (FK → books)
--   6. book_copies     (FK → books, CASCADE)
--   7. books
-- ============================================================================

BEGIN;

-- Remove loan events first
DELETE FROM loan_events;

-- Remove fine assessments (FK → loans)
DELETE FROM fine_assessments;

-- Remove loans (FK → book_copies with RESTRICT)
DELETE FROM loans;

-- Remove reservations (FK → books with CASCADE)
DELETE FROM reservations;

-- Remove book copies (FK → books with CASCADE)
DELETE FROM book_copies;

-- Finally remove books
DELETE FROM books;

COMMIT;

-- Reset auto-increment counters (optional — comment out if not desired)
ALTER TABLE books AUTO_INCREMENT = 1;
ALTER TABLE book_copies AUTO_INCREMENT = 1;
ALTER TABLE reservations AUTO_INCREMENT = 1;
ALTER TABLE loans AUTO_INCREMENT = 1;
ALTER TABLE loan_events AUTO_INCREMENT = 1;
ALTER TABLE fine_assessments AUTO_INCREMENT = 1;
