-- ============================================================================
-- Seed Books — 70 titles across different genres with cover images
-- ============================================================================
-- Run AFTER clear_books.sql (or on a fresh database).
-- Run from phpMyAdmin or via: mysql -u root -p library_betonio < seed_books_70.sql
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Reset existing seeded data to avoid duplicate barcode errors on re-run
-- ---------------------------------------------------------------------------

DELETE FROM book_copies;
DELETE FROM books;

-- ---------------------------------------------------------------------------
-- Insert 70 books with cover images from public/uploads/book-covers/books cover/
-- ---------------------------------------------------------------------------

INSERT INTO books (isbn, title, author, category, publisher, publish_year, edition, description, cover_image, total_copies, available_copies, location, is_active) VALUES
('9780005000000', 'The Great Adventure', 'John Smith', 'Adventure', 'Adventure Press', 2020, 'First Edition', 'An epic journey through uncharted territories.', 'public/uploads/book-covers/books cover/0005000000.jpg', 3, 3, 'Shelf A1', 1),
('9780005000002', 'Mystery of the Lost City', 'Jane Doe', 'Mystery', 'Mystery House', 2019, 'Second Edition', 'A thrilling mystery set in an ancient civilization.', 'public/uploads/book-covers/books cover/0005000002.jpg', 2, 2, 'Shelf A2', 1),
('9780005000003', 'Science Fiction Odyssey', 'Robert Johnson', 'Science Fiction', 'Galaxy Books', 2021, 'First Edition', 'A space opera spanning multiple galaxies.', 'public/uploads/book-covers/books cover/0005000003.jpg', 4, 4, 'Shelf B1', 1),
('9780005000005', 'Romance in Paris', 'Emily Brown', 'Romance', 'Love Stories', 2022, 'First Edition', 'A heartwarming love story set in the City of Light.', 'public/uploads/book-covers/books cover/0005000005.jpg', 3, 3, 'Shelf C1', 1),
('9780005000006', 'The Dark Forest', 'Michael Wilson', 'Horror', 'Nightmare Publishing', 2020, 'First Edition', 'A terrifying tale of survival in the wilderness.', 'public/uploads/book-covers/books cover/0005000006.jpg', 2, 2, 'Shelf D1', 1),
('9780005000008', 'Historical Chronicles', 'Sarah Davis', 'History', 'History Press', 2018, 'Third Edition', 'A comprehensive look at medieval times.', 'public/uploads/book-covers/books cover/0005000008.jpg', 5, 5, 'Shelf E1', 1),
('9780005000009', 'Fantasy Realms', 'David Miller', 'Fantasy', 'Dragon Books', 2023, 'First Edition', 'Magic and dragons in a world of wonder.', 'public/uploads/book-covers/books cover/0005000009.jpg', 3, 3, 'Shelf F1', 1),
('9780005000010', 'The Detective', 'James Anderson', 'Thriller', 'Crime Stories', 2021, 'First Edition', 'A gripping detective novel with twists and turns.', 'public/uploads/book-covers/books cover/0005000010.jpg', 4, 4, 'Shelf G1', 1),
('9780005000011', 'Cooking Masterclass', 'Maria Garcia', 'Cooking', 'Culinary Arts', 2022, 'First Edition', 'Master the art of French cuisine.', 'public/uploads/book-covers/books cover/0005000011.jpg', 2, 2, 'Shelf H1', 1),
('9780005000012', 'Self-Improvement Guide', 'Thomas Taylor', 'Self-Help', 'Personal Growth', 2020, 'Second Edition', 'Transform your life with practical advice.', 'public/uploads/book-covers/books cover/0005000012.jpg', 3, 3, 'Shelf I1', 1),
('9780005000013', 'The Art of Painting', 'Lisa White', 'Art', 'Creative Press', 2019, 'First Edition', 'Learn techniques from master painters.', 'public/uploads/book-covers/books cover/0005000013.jpg', 2, 2, 'Shelf J1', 1),
('9780005000014', 'Business Strategies', 'Christopher Lee', 'Business', 'Success Books', 2021, 'First Edition', 'Proven strategies for business success.', 'public/uploads/book-covers/books cover/0005000014.jpg', 4, 4, 'Shelf K1', 1),
('9780005000015', 'Travel Adventures', 'Amanda Clark', 'Travel', 'Journey Books', 2022, 'First Edition', 'Explore the world''s most beautiful places.', 'public/uploads/book-covers/books cover/0005000015.jpg', 3, 3, 'Shelf L1', 1),
('9780005000016', 'The Philosophy of Life', 'Daniel Lewis', 'Philosophy', 'Wisdom Press', 2020, 'First Edition', 'Deep thoughts on existence and meaning.', 'public/uploads/book-covers/books cover/0005000016.jpg', 2, 2, 'Shelf M1', 1),
('9780005000017', 'Medical Miracles', 'Dr. Rachel Green', 'Medical', 'Health Publications', 2021, 'First Edition', 'Breakthroughs in modern medicine.', 'public/uploads/book-covers/books cover/0005000017.jpg', 3, 3, 'Shelf N1', 1),
('9780005000018', 'Technology Revolution', 'Kevin Hall', 'Technology', 'Tech Future', 2023, 'First Edition', 'The future of technology and AI.', 'public/uploads/book-covers/books cover/0005000018.jpg', 4, 4, 'Shelf O1', 1),
('9780005000019', 'Sports Champions', 'Brian Adams', 'Sports', 'Athletic Press', 2022, 'First Edition', 'Stories of legendary athletes.', 'public/uploads/book-covers/books cover/0005000019.jpg', 2, 2, 'Shelf P1', 1),
('9780005000021', 'Music Theory', 'Jennifer King', 'Music', 'Melody Books', 2020, 'Second Edition', 'Understanding the fundamentals of music.', 'public/uploads/book-covers/books cover/0005000021.jpg', 3, 3, 'Shelf Q1', 1),
('9780005000022', 'The Poet''s Journey', 'Mark Wright', 'Poetry', 'Verse Press', 2021, 'First Edition', 'A collection of heartfelt poems.', 'public/uploads/book-covers/books cover/0005000022.jpg', 2, 2, 'Shelf R1', 1),
('9780005000023', 'Gardening Secrets', 'Susan Lopez', 'Gardening', 'Green Thumb', 2022, 'First Edition', 'Create your dream garden.', 'public/uploads/book-covers/books cover/0005000023.jpg', 3, 3, 'Shelf S1', 1),
('9780005000024', 'The Science of Mind', 'Dr. Patricia Hill', 'Psychology', 'Mind Matters', 2020, 'First Edition', 'Understanding human behavior.', 'public/uploads/book-covers/books cover/0005000024.jpg', 4, 4, 'Shelf T1', 1),
('9780005000025', 'Architecture Wonders', 'Richard Scott', 'Architecture', 'Design Press', 2021, 'First Edition', 'Stunning architectural masterpieces.', 'public/uploads/book-covers/books cover/0005000025.jpg', 2, 2, 'Shelf U1', 1),
('9780005000026', 'The Ocean''s Depths', 'Margaret Young', 'Nature', 'Wild Books', 2022, 'First Edition', 'Explore the mysteries of the deep sea.', 'public/uploads/book-covers/books cover/0005000026.jpg', 3, 3, 'Shelf V1', 1),
('9780005000027', 'Political Science', 'William Allen', 'Politics', 'Government Press', 2020, 'Second Edition', 'Understanding political systems.', 'public/uploads/book-covers/books cover/0005000027.jpg', 4, 4, 'Shelf W1', 1),
('9780005000028', 'The Economic World', 'Elizabeth King', 'Economics', 'Finance Books', 2021, 'First Edition', 'Global economics explained.', 'public/uploads/book-covers/books cover/0005000028.jpg', 2, 2, 'Shelf X1', 1),
('9780005000030', 'Religious Studies', 'Father John Moore', 'Religion', 'Faith Press', 2022, 'First Edition', 'Comparative study of world religions.', 'public/uploads/book-covers/books cover/0005000030.jpg', 3, 3, 'Shelf Y1', 1),
('9780005000032', 'The Legal System', 'Attorney Mary Taylor', 'Law', 'Justice Books', 2020, 'First Edition', 'Understanding the law.', 'public/uploads/book-covers/books cover/0005000032.jpg', 4, 4, 'Shelf Z1', 1),
('9780005000033', 'Education Revolution', 'Dr. Nancy White', 'Education', 'Learning Press', 2021, 'First Edition', 'Transforming education for the future.', 'public/uploads/book-covers/books cover/0005000033.jpg', 2, 2, 'Shelf A2', 1),
('9780005000034', 'The Social Network', 'Thomas Harris', 'Sociology', 'Society Books', 2022, 'First Edition', 'Understanding social dynamics.', 'public/uploads/book-covers/books cover/0005000034.jpg', 3, 3, 'Shelf B2', 1),
('9780005000035', 'Communication Skills', 'Dr. Linda Martin', 'Communication', 'Speak Well', 2020, 'Second Edition', 'Master the art of communication.', 'public/uploads/book-covers/books cover/0005000035.jpg', 4, 4, 'Shelf C2', 1),
('9780005000036', 'The Environment', 'Dr. George Thompson', 'Environment', 'Green Future', 2021, 'First Edition', 'Protecting our planet.', 'public/uploads/book-covers/books cover/0005000036.jpg', 2, 2, 'Shelf D2', 1),
('9780005000037', 'Mathematics Genius', 'Prof. Robert Garcia', 'Mathematics', 'Number Press', 2022, 'First Edition', 'Advanced mathematical concepts.', 'public/uploads/book-covers/books cover/0005000037.jpg', 3, 3, 'Shelf E2', 1),
('9780005000038', 'The Physics World', 'Dr. Maria Martinez', 'Physics', 'Science Books', 2020, 'First Edition', 'Understanding the universe.', 'public/uploads/book-covers/books cover/0005000038.jpg', 4, 4, 'Shelf F2', 1),
('9780005000039', 'Chemistry Essentials', 'Dr. James Robinson', 'Chemistry', 'Molecular Press', 2021, 'First Edition', 'Fundamentals of chemistry.', 'public/uploads/book-covers/books cover/0005000039.jpg', 2, 2, 'Shelf G2', 1),
('9780005000040', 'Biology Discoveries', 'Dr. Susan Clark', 'Biology', 'Life Sciences', 2022, 'First Edition', 'Exploring the living world.', 'public/uploads/book-covers/books cover/0005000040.jpg', 3, 3, 'Shelf H2', 1),
('9780005000041', 'The Computer Age', 'David Rodriguez', 'Computers', 'Tech Press', 2020, 'Second Edition', 'Computing in the modern world.', 'public/uploads/book-covers/books cover/0005000041.jpg', 4, 4, 'Shelf I2', 1),
('9780005000042', 'Engineering Marvels', 'Dr. Michael Lewis', 'Engineering', 'Build Books', 2021, 'First Edition', 'Great engineering achievements.', 'public/uploads/book-covers/books cover/0005000042.jpg', 2, 2, 'Shelf J2', 1),
('9780005000043', 'The Literary World', 'Prof. Elizabeth Walker', 'Literature', 'Classic Press', 2022, 'First Edition', 'Survey of world literature.', 'public/uploads/book-covers/books cover/0005000043.jpg', 3, 3, 'Shelf K2', 1),
('9780005000045', 'Linguistics Studies', 'Dr. John Hall', 'Linguistics', 'Language Books', 2020, 'First Edition', 'The science of language.', 'public/uploads/book-covers/books cover/0005000045.jpg', 4, 4, 'Shelf L2', 1),
('9780005000046', 'The Anthropologist', 'Dr. Sarah Young', 'Anthropology', 'Human Studies', 2021, 'First Edition', 'Understanding human cultures.', 'public/uploads/book-covers/books cover/0005000046.jpg', 2, 2, 'Shelf M2', 1),
('9780005000047', 'Archaeology Adventures', 'Dr. Richard Allen', 'Archaeology', 'Ancient Press', 2022, 'First Edition', 'Uncovering ancient civilizations.', 'public/uploads/book-covers/books cover/0005000047.jpg', 3, 3, 'Shelf N2', 1),
('9780005000048', 'The Astronomer', 'Dr. Patricia King', 'Astronomy', 'Star Books', 2020, 'First Edition', 'Exploring the cosmos.', 'public/uploads/book-covers/books cover/0005000048.jpg', 4, 4, 'Shelf O2', 1),
('9780005000049', 'Geology Rocks', 'Dr. William Wright', 'Geology', 'Earth Press', 2021, 'First Edition', 'Understanding our planet.', 'public/uploads/book-covers/books cover/0005000049.jpg', 2, 2, 'Shelf P2', 1),
('9780005000050', 'The Meteorologist', 'Dr. Jennifer Lopez', 'Meteorology', 'Weather Books', 2022, 'First Edition', 'Predicting the weather.', 'public/uploads/book-covers/books cover/0005000050.jpg', 3, 3, 'Shelf Q2', 1),
('9780005000051', 'The Zoologist', 'Dr. Mark Hill', 'Zoology', 'Animal Press', 2020, 'First Edition', 'Study of animal life.', 'public/uploads/book-covers/books cover/0005000051.jpg', 4, 4, 'Shelf R2', 1),
('9780005000052', 'Botany Basics', 'Dr. Nancy Adams', 'Botany', 'Plant Books', 2021, 'First Edition', 'Understanding plant life.', 'public/uploads/book-covers/books cover/0005000052.jpg', 2, 2, 'Shelf S2', 1),
('9780005000053', 'The Ecologist', 'Dr. Thomas Baker', 'Ecology', 'Nature Press', 2022, 'First Edition', 'Ecosystems and environment.', 'public/uploads/book-covers/books cover/0005000053.jpg', 3, 3, 'Shelf T2', 1),
('9780005000054', 'The Geneticist', 'Dr. Lisa Carter', 'Genetics', 'DNA Books', 2020, 'First Edition', 'The science of heredity.', 'public/uploads/book-covers/books cover/0005000054.jpg', 4, 4, 'Shelf U2', 1),
('9780005000055', 'The Biologist', 'Dr. Kevin Edwards', 'Biology', 'Life Press', 2021, 'First Edition', 'Advanced biological concepts.', 'public/uploads/book-covers/books cover/0005000055.jpg', 2, 2, 'Shelf V2', 1),
('9780005000057', 'The Physicist', 'Dr. Rachel Collins', 'Physics', 'Quantum Books', 2022, 'First Edition', 'Quantum mechanics explained.', 'public/uploads/book-covers/books cover/0005000057.jpg', 3, 3, 'Shelf W2', 1),
('9780005000058', 'The Chemist', 'Dr. Brian Stewart', 'Chemistry', 'Element Books', 2020, 'First Edition', 'Chemical reactions and processes.', 'public/uploads/book-covers/books cover/0005000058.jpg', 4, 4, 'Shelf X2', 1),
('9780005000059', 'The Mathematician', 'Dr. Sandra Morris', 'Mathematics', 'Calculus Books', 2021, 'First Edition', 'Advanced mathematics.', 'public/uploads/book-covers/books cover/0005000059.jpg', 2, 2, 'Shelf Y2', 1),
('9780005000060', 'The Statistician', 'Dr. Donald Reed', 'Statistics', 'Data Books', 2022, 'First Edition', 'Statistical analysis methods.', 'public/uploads/book-covers/books cover/0005000060.jpg', 3, 3, 'Shelf Z2', 1),
('9780005000061', 'The Computer Scientist', 'Dr. Katherine Cook', 'Computer Science', 'Code Books', 2020, 'First Edition', 'Programming and algorithms.', 'public/uploads/book-covers/books cover/0005000061.jpg', 4, 4, 'Shelf A3', 1),
('9780005000063', 'The Engineer', 'Dr. George Bailey', 'Engineering', 'Design Books', 2021, 'First Edition', 'Engineering principles.', 'public/uploads/book-covers/books cover/0005000063.jpg', 2, 2, 'Shelf B3', 1),
('9780005000064', 'The Architect', 'Dr. Helen Rivera', 'Architecture', 'Building Books', 2022, 'First Edition', 'Architectural design.', 'public/uploads/book-covers/books cover/0005000064.jpg', 3, 3, 'Shelf C3', 1),
('9780005000065', 'The Artist', 'Dr. Ruth Cooper', 'Art', 'Creative Books', 2020, 'First Edition', 'Artistic expression.', 'public/uploads/book-covers/books cover/0005000065.jpg', 4, 4, 'Shelf D3', 1),
('9780005000066', 'The Musician', 'Dr. Frank Richardson', 'Music', 'Harmony Books', 2021, 'First Edition', 'Music theory and composition.', 'public/uploads/book-covers/books cover/0005000066.jpg', 2, 2, 'Shelf E3', 1),
('9780005000068', 'The Writer', 'Dr. Dorothy Cox', 'Writing', 'Author Books', 2022, 'First Edition', 'Creative writing techniques.', 'public/uploads/book-covers/books cover/0005000068.jpg', 3, 3, 'Shelf F3', 1),
('9780005000069', 'The Journalist', 'Dr. Edward Howard', 'Journalism', 'Media Books', 2020, 'First Edition', 'News and reporting.', 'public/uploads/book-covers/books cover/0005000069.jpg', 4, 4, 'Shelf G3', 1),
('9780005000070', 'The Photographer', 'Dr. Virginia Ward', 'Photography', 'Image Books', 2021, 'First Edition', 'Photography techniques.', 'public/uploads/book-covers/books cover/0005000070.jpg', 2, 2, 'Shelf H3', 1),
('9780005000071', 'The Filmmaker', 'Dr. Ronald Torres', 'Film', 'Cinema Books', 2022, 'First Edition', 'Film production.', 'public/uploads/book-covers/books cover/0005000071.jpg', 3, 3, 'Shelf I3', 1),
('9780005000072', 'The Actor', 'Dr. Lisa Peterson', 'Acting', 'Stage Books', 2020, 'First Edition', 'Acting techniques.', 'public/uploads/book-covers/books cover/0005000072.jpg', 4, 4, 'Shelf J3', 1),
('9780005000073', 'The Dancer', 'Dr. Karen Gray', 'Dance', 'Movement Books', 2021, 'First Edition', 'Dance and choreography.', 'public/uploads/book-covers/books cover/0005000073.jpg', 2, 2, 'Shelf K3', 1),
('9780005000074', 'The Singer', 'Dr. James Ramirez', 'Music', 'Vocal Books', 2022, 'First Edition', 'Vocal techniques.', 'public/uploads/book-covers/books cover/0005000074.jpg', 3, 3, 'Shelf L3', 1),
('9780005000075', 'The Composer', 'Dr. Diane Lewis', 'Music', 'Composition Books', 2020, 'First Edition', 'Music composition.', 'public/uploads/book-covers/books cover/0005000075.jpg', 4, 4, 'Shelf M3', 1),
('9780005000076', 'The Conductor', 'Dr. Paul Butler', 'Music', 'Orchestra Books', 2021, 'First Edition', 'Orchestral conducting.', 'public/uploads/book-covers/books cover/0005000076.jpg', 2, 2, 'Shelf N3', 1),
('9780005000077', 'The Producer', 'Dr. Nancy Bell', 'Film', 'Production Books', 2022, 'First Edition', 'Film production.', 'public/uploads/book-covers/books cover/0005000077.jpg', 3, 3, 'Shelf O3', 1),
('9780005000078', 'The Director', 'Dr. Roger Coleman', 'Film', 'Direction Books', 2020, 'First Edition', 'Film direction.', 'public/uploads/book-covers/books cover/0005000078.jpg', 4, 4, 'Shelf P3', 1),
('9780005000079', 'The Screenwriter', 'Dr. Cynthia Jenkins', 'Writing', 'Script Books', 2021, 'First Edition', 'Screenwriting techniques.', 'public/uploads/book-covers/books cover/0005000079.jpg', 2, 2, 'Shelf Q3', 1);

-- ---------------------------------------------------------------------------
-- Insert 2 physical copies per book (140 copies total)
-- Barcodes follow the pattern: BC-{book_id}-{copy_number}
-- ---------------------------------------------------------------------------

INSERT INTO book_copies (book_id, barcode, status, acquired_at)
SELECT b.id, CONCAT('BC-', b.id, '-1'), 'available', '2025-01-15'
FROM books b
UNION ALL
SELECT b.id, CONCAT('BC-', b.id, '-2'), 'available', '2025-06-01'
FROM books b;
