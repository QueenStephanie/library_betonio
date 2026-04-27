    -- ============================================================================
    -- Seed Books — 10 titles across different genres, each with 2 physical copies
    -- ============================================================================
    -- Run AFTER clear_books.sql (or on a fresh database).
    -- Run from phpMyAdmin or via: mysql -u root -p library_betonio < seed_books.sql
    -- ============================================================================

    -- ---------------------------------------------------------------------------
    -- 1. Insert books (one per genre)
    -- ---------------------------------------------------------------------------
    -- NOTE: cover_image_url is left empty ('') so the system falls back to the
    -- placeholder image (images/book-covers-big-2019101610.jpg).
    -- To add actual covers later, upload via the librarian dashboard or manually
    -- place files in public/uploads/book-covers/ and update this column.
    INSERT INTO books (isbn, title, author, category, publish_year, cover_image) VALUES
    ('9780544003415', 'The Hobbit',                          'J.R.R. Tolkien',       'Fantasy',          1937, ''),
    ('9780553382563', 'Dune',                                'Frank Herbert',        'Science Fiction',   1965, ''),
    ('9780316769488', 'The Catcher in the Rye',              'J.D. Salinger',        'Literary Fiction',  1951, ''),
    ('9780451524935', '1984',                                'George Orwell',        'Dystopian',         1949, ''),
    ('9780061120084', 'To Kill a Mockingbird',               'Harper Lee',           'Classic',           1960, ''),
    ('9780307474278', 'The Da Vinci Code',                   'Dan Brown',            'Thriller',          2003, ''),
    ('9780747532743', 'Harry Potter and the Philosopher''s Stone', 'J.K. Rowling',   'Young Adult',       1997, ''),
    ('9780140449266', 'The Odyssey',                         'Homer',                'Epic Poetry',        -800, ''),
    ('9780307387899', 'The Road',                            'Cormac McCarthy',      'Post-Apocalyptic',   2006, ''),
    ('9780385504201', 'The Da Vinci Code (Special)',         'Dan Brown',            'Mystery',           2003, '');

    -- ---------------------------------------------------------------------------
    -- 2. Insert 2 physical copies per book (20 copies total)
    --    Barcodes follow the pattern: BC-{book_id}-{copy_number}
    -- ---------------------------------------------------------------------------
    INSERT INTO book_copies (book_id, barcode, status, acquired_at)
    SELECT b.id, CONCAT('BC-', b.id, '-1'), 'available', '2025-01-15'
    FROM books b
    UNION ALL
    SELECT b.id, CONCAT('BC-', b.id, '-2'), 'available', '2025-06-01'
    FROM books b;
