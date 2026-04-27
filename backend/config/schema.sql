-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql100.infinityfree.com
-- Generation Time: Apr 27, 2026 at 09:13 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41706072_library_betonio_lab`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_profiles`
--

CREATE TABLE `admin_profiles` (
  `id` int(11) NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(40) NOT NULL,
  `address` varchar(255) NOT NULL,
  `appointment_date` date NOT NULL,
  `access_level` varchar(150) NOT NULL DEFAULT 'Full Access - Super Administrator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_profiles`
--

INSERT INTO `admin_profiles` (`id`, `admin_username`, `full_name`, `email`, `phone`, `address`, `appointment_date`, `access_level`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'System Administrator', 'admin@libris.com', '(555) 000-1111', 'Smoke Test Office', '2026-04-01', 'Full Access - Super Administrator', '2026-04-01 11:10:10', '2026-04-01 11:10:10'),
(2, 'admin@local.admin', 'System Administrator', 'admin@local.admin', '(555) 123-4567', 'Administrator Office', '2026-04-12', 'Full Access - Super Administrator', '2026-04-12 08:28:24', '2026-04-12 08:30:53'),
(4, 'mike.sordilla@nmsc.edu.ph', 'System Administrator', 'mike.sordilla@nmsc.edu.ph@queenlib.com', '(555) 123-4567', '456 Admin Boulevard, Central City', '2026-04-12', 'Full Access - Super Administrator', '2026-04-12 09:17:34', '2026-04-12 09:17:34');

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publish_year` int(11) DEFAULT NULL,
  `edition` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `isbn`, `title`, `author`, `category`, `publisher`, `publish_year`, `edition`, `description`, `cover_image`, `total_copies`, `available_copies`, `location`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '978-0-06-112008-4', 'To Kill a Mockingbird', 'Harper Lee', 'Fiction', 'J.B. Lippincott & Co.', 1960, NULL, NULL, NULL, 5, 5, 'Shelf A1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(2, '978-0-452-28423-4', '1984', 'George Orwell', 'Fiction', 'Secker & Warburg', 1949, NULL, NULL, NULL, 4, 4, 'Shelf A1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(3, '978-0-7432-7356-5', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Fiction', 'Charles Scribner\'s Sons', 1925, NULL, NULL, NULL, 3, 3, 'Shelf A2', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(4, '978-0-14-028329-7', 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 'Little, Brown and Company', 1951, NULL, NULL, NULL, 3, 3, 'Shelf A2', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(5, '978-0-06-093546-7', 'To Kill a Kingdom', 'Alexandra Christo', 'Fantasy', 'Feiwel & Friends', 2018, NULL, NULL, NULL, 2, 2, 'Shelf B1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(6, '978-0-547-92822-7', 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 'Allen & Unwin', 1937, NULL, NULL, NULL, 6, 6, 'Shelf B1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(7, '978-0-439-02348-1', 'The Hunger Games', 'Suzanne Collins', 'Young Adult', 'Scholastic Press', 2008, NULL, NULL, NULL, 4, 4, 'Shelf C1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(8, '978-0-06-120008-4', 'Brave New World', 'Aldous Huxley', 'Fiction', 'Chatto & Windus', 1932, NULL, NULL, NULL, 3, 3, 'Shelf A1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(9, '978-0-14-118776-1', 'One Hundred Years of Solitude', 'Gabriel García Márquez', 'Fiction', 'Harper & Row', 1967, NULL, NULL, NULL, 2, 2, 'Shelf A3', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(10, '978-0-14-044913-6', 'Pride and Prejudice', 'Jane Austen', 'Classic', 'T. Egerton', 1813, NULL, NULL, NULL, 4, 4, 'Shelf D1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(11, '978-0-06-112008-5', 'Animal Farm', 'George Orwell', 'Fiction', 'Secker & Warburg', 1945, NULL, NULL, NULL, 5, 5, 'Shelf A2', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(12, '978-0-14-044926-6', 'Frankenstein', 'Mary Shelley', 'Classic', 'Lackington, Hughes, Harding', 1818, NULL, NULL, NULL, 2, 2, 'Shelf D1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(13, '978-0-19-953556-4', 'The Republic', 'Plato', 'Philosophy', 'Oxford University Press', -380, NULL, NULL, NULL, 2, 2, 'Shelf E1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(14, '978-0-14-044913-7', 'A Brief History of Time', 'Stephen Hawking', 'Science', 'Bantam Books', 1988, NULL, NULL, NULL, 3, 3, 'Shelf F1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(15, '978-0-06-093546-8', 'The Art of War', 'Sun Tzu', 'Philosophy', 'Various', -500, NULL, NULL, NULL, 4, 4, 'Shelf E1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(16, '978-0-14-028329-8', 'Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Non-Fiction', 'Harper', 2011, NULL, NULL, NULL, 3, 3, 'Shelf F2', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(17, '978-0-06-112009-1', 'The Alchemist', 'Paulo Coelho', 'Fiction', 'HarperOne', 1988, NULL, NULL, NULL, 5, 5, 'Shelf A3', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(18, '978-0-14-028330-4', 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Psychology', 'Farrar, Straus and Giroux', 2011, NULL, NULL, NULL, 2, 2, 'Shelf G1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(19, '978-0-06-112010-7', 'The Subtle Art of Not Giving a F*ck', 'Mark Manson', 'Self-Help', 'HarperOne', 2016, NULL, NULL, NULL, 4, 4, 'Shelf H1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(20, '978-0-14-028331-1', 'Educated: A Memoir', 'Tara Westover', 'Non-Fiction', 'Random House', 2018, NULL, NULL, NULL, 3, 3, 'Shelf F2', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(21, '978-0-06-112011-4', 'Atomic Habits', 'James Clear', 'Self-Help', 'Avery', 2018, NULL, NULL, NULL, 5, 5, 'Shelf H1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(22, '978-0-14-028332-8', 'Becoming', 'Michelle Obama', 'Non-Fiction', 'Crown Publishing', 2018, NULL, NULL, NULL, 3, 3, 'Shelf F3', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(23, '978-0-06-112012-1', 'Where the Crawdads Sing', 'Delia Owens', 'Fiction', 'G.P. Putnam\'s Sons', 2018, NULL, NULL, NULL, 4, 4, 'Shelf A3', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(24, '978-0-14-028333-5', 'The Silent Patient', 'Alex Michaelides', 'Thriller', 'Celadon Books', 2019, NULL, NULL, NULL, 3, 3, 'Shelf I1', 1, '2026-04-02 13:48:08', '2026-04-02 13:48:08'),
(25, '9780141439600', 'Pride and Prejudice', 'Jane Austen', 'Classic', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, 1, '2026-04-12 09:17:07', '2026-04-12 09:17:07'),
(26, '9780743273565', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, 1, '2026-04-12 09:17:07', '2026-04-12 09:17:07'),
(31, '9780306406904', 'Smoke Test 855084', 'Agent', 'Test', NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, 1, '2026-04-19 11:25:45', '2026-04-19 11:25:45'),
(34, '0123456789', 'Test 1776725817', 'Test Auth', 'Fiction', NULL, NULL, NULL, NULL, '', 1, 1, NULL, 1, '2026-04-20 22:56:57', '2026-04-20 22:56:57'),
(35, '9781234567897', 'Test Book', 'Test Author', 'Fiction', NULL, 2023, NULL, NULL, '', 1, 1, NULL, 1, '2026-04-20 23:18:12', '2026-04-20 23:18:12');

-- --------------------------------------------------------

--
-- Table structure for table `book_copies`
--

CREATE TABLE `book_copies` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `barcode` varchar(64) NOT NULL,
  `status` enum('available','reserved','loaned','lost','damaged','maintenance') NOT NULL DEFAULT 'available',
  `acquired_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_copies`
--

INSERT INTO `book_copies` (`id`, `book_id`, `barcode`, `status`, `acquired_at`, `created_at`, `updated_at`) VALUES
(1, 25, 'QB-PP-001', 'available', '2026-04-12', '2026-04-12 09:17:07', '2026-04-12 09:17:07'),
(2, 26, 'QB-TGG-001', 'available', '2026-04-12', '2026-04-12 09:17:07', '2026-04-12 09:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `fine_assessments`
--

CREATE TABLE `fine_assessments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `borrower_user_id` int(11) NOT NULL,
  `assessed_by_user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT 'Overdue return',
  `days_overdue` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','paid','waived') NOT NULL DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fine_collections`
--

CREATE TABLE `fine_collections` (
  `id` int(11) NOT NULL,
  `borrower_user_id` int(11) DEFAULT NULL,
  `collected_by_user_id` int(11) DEFAULT NULL,
  `receipt_code` varchar(64) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('collected','voided') NOT NULL DEFAULT 'collected',
  `notes` varchar(255) DEFAULT NULL,
  `collected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `borrower_user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `checkout_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('active','returned','overdue','lost') NOT NULL DEFAULT 'active',
  `renewed` tinyint(1) DEFAULT 0,
  `renewed_at` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_events`
--

CREATE TABLE `loan_events` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `event_type` enum('checkout','renewal','return','mark_overdue','mark_lost','void') NOT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `event_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `is_successful` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('overdue_warning','overdue_alert','reservation_ready','reservation_expired','fine_assessed','fine_payment','loan_returned','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `related_loan_id` int(11) DEFAULT NULL,
  `related_reservation_id` int(11) DEFAULT NULL,
  `related_fine_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('email_verification','password_reset') DEFAULT 'email_verification',
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `borrower_user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` enum('pending','ready','fulfilled','cancelled','expired') NOT NULL DEFAULT 'pending',
  `reserved_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ready_at` datetime DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `borrower_user_id`, `book_id`, `status`, `reserved_at`, `ready_at`, `fulfilled_at`, `expires_at`, `notes`, `created_at`, `updated_at`) VALUES
(22, 71, 8, 'cancelled', '2026-04-20 17:11:35', NULL, NULL, NULL, NULL, '2026-04-20 01:11:35', '2026-04-20 12:25:11'),
(23, 71, 9, 'cancelled', '2026-04-20 17:22:11', NULL, NULL, NULL, NULL, '2026-04-20 01:22:11', '2026-04-20 21:51:25'),
(24, 71, 15, 'cancelled', '2026-04-20 17:25:22', NULL, NULL, NULL, NULL, '2026-04-20 01:25:22', '2026-04-20 21:51:26'),
(25, 71, 2, '', '2026-04-20 17:28:13', NULL, NULL, NULL, NULL, '2026-04-20 01:28:13', '2026-04-21 01:28:37'),
(26, 71, 25, 'cancelled', '2026-04-20 20:23:55', NULL, NULL, NULL, NULL, '2026-04-20 04:23:55', '2026-04-23 13:51:00'),
(27, 71, 11, 'cancelled', '2026-04-21 05:33:24', NULL, NULL, NULL, NULL, '2026-04-20 13:33:24', '2026-04-20 21:41:55'),
(28, 71, 17, 'cancelled', '2026-04-21 05:41:58', NULL, NULL, NULL, NULL, '2026-04-20 13:41:58', '2026-04-20 21:50:30'),
(29, 71, 8, 'cancelled', '2026-04-21 05:50:32', NULL, NULL, NULL, NULL, '2026-04-20 13:50:32', '2026-04-23 13:51:02'),
(30, 71, 11, 'cancelled', '2026-04-21 05:51:30', NULL, NULL, NULL, NULL, '2026-04-20 13:51:30', '2026-04-23 13:51:05'),
(31, 71, 14, 'cancelled', '2026-04-21 06:05:20', NULL, NULL, NULL, NULL, '2026-04-20 14:05:20', '2026-04-20 22:07:58'),
(32, 71, 9, 'cancelled', '2026-04-21 06:08:47', NULL, NULL, NULL, NULL, '2026-04-20 14:08:47', '2026-04-23 13:51:06'),
(33, 71, 14, 'ready', '2026-04-23 07:10:03', NULL, NULL, '2026-04-29 18:53:38', NULL, '2026-04-23 21:10:03', '2026-04-27 01:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `role_profiles`
--

CREATE TABLE `role_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('admin','librarian','borrower') NOT NULL,
  `role_information` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_profiles`
--

INSERT INTO `role_profiles` (`id`, `user_id`, `role`, `role_information`, `created_at`, `updated_at`) VALUES
(3, 45, 'admin', 'System superadmin account', '2026-04-01 20:46:49', '2026-04-19 11:05:25'),
(27, 69, 'librarian', '', '2026-04-20 06:57:07', '2026-04-20 06:57:07'),
(28, 71, 'borrower', '', '2026-04-20 09:11:25', '2026-04-20 09:11:25');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

CREATE TABLE `schema_migrations` (
  `migration_name` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `role` enum('admin','librarian','borrower') NOT NULL DEFAULT 'borrower',
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password_hash`, `is_verified`, `verification_token`, `verification_token_expires`, `reset_token`, `reset_token_expires`, `created_at`, `updated_at`, `last_login`, `is_active`, `role`, `is_superadmin`) VALUES
(45, 'Super', 'Admin', 'admin@local.admin', '$2y$12$JrVcNXCPVDNsIGTcksRs4u9VkyopKKnUwtNSsjtPefruT3FkY4QiC', 1, NULL, NULL, NULL, NULL, '2026-04-01 20:46:49', '2026-04-19 11:05:25', NULL, 1, 'admin', 1),
(69, 'QUEEN', 'BETONIO', 'queenstephanie.betonio@nmsc.edu.ph', '$2y$12$lR/UEaxrVULMwkNpSVDIQ.JHsDwa8Oxsy38makXy4T8Lx3URGFaiW', 1, NULL, NULL, '$2y$10$5gopj4qaOPy95qLvPAGPzeh3HNrRUMjjV5I70f0nuBeTYFoZXIDTG', '2026-04-20 12:10:21', '2026-04-19 23:32:14', '2026-04-20 12:00:21', NULL, 1, 'librarian', 0),
(71, 'Mike', 'Sordilla', 'mike.sordilla@nmsc.edu.ph', '$2y$12$RkrGLPN8dmSe4WBdNH7TlezX1CcCFqjPqvsKMxbzF6jvD8evG55Tq', 1, NULL, NULL, '$2y$10$bRe4BtSdxDMDOVaDX28RmubjuVH0U5JidhhnUng.CYe3aSg5OWuui', '2026-04-20 12:11:00', '2026-04-20 09:11:25', '2026-04-20 12:01:00', NULL, 1, 'borrower', 0);

-- --------------------------------------------------------

--
-- Table structure for table `verification_attempts`
--

CREATE TABLE `verification_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `attempt_type` enum('password_reset','registration','login_attempt','password_reset_verify') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `is_successful` tinyint(1) DEFAULT 0,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `verification_attempts`
--

INSERT INTO `verification_attempts` (`id`, `email`, `attempt_type`, `ip_address`, `is_successful`, `attempted_at`) VALUES
(16, 'mike.sordilla@nmsc.edu.ph', 'password_reset', '::1', 1, '2026-04-03 11:57:48'),
(17, 'mike.sordilla@nmsc.edu.ph', '', '::1', 1, '2026-04-03 11:58:06'),
(18, 'mike.sordilla@nmsc.edu.ph', 'password_reset', '::1', 1, '2026-04-03 12:03:57'),
(19, 'mike.sordilla@nmsc.edu.ph', '', '::1', 1, '2026-04-03 12:04:14'),
(20, 'sordillamike1@gmail.com', 'password_reset', '::1', 1, '2026-04-03 22:29:27'),
(21, 'sordillamike1@gmail.com', '', '::1', 1, '2026-04-03 22:29:44'),
(22, 'sordillamike1@gmail.com', 'password_reset', '::1', 1, '2026-04-05 08:27:23'),
(23, 'sordillamike1@gmail.com', '', '::1', 1, '2026-04-05 08:27:36'),
(24, 'sordillamike1@gmail.com', 'password_reset', '::1', 1, '2026-04-05 08:42:54'),
(25, 'sordillamike1@gmail.com', '', '::1', 1, '2026-04-05 08:43:07'),
(26, 'sordillamike1@gmail.com', 'password_reset', '::1', 1, '2026-04-05 21:36:38'),
(27, 'sordillamike1@gmail.com', '', '::1', 1, '2026-04-05 21:36:51'),
(28, 'mike.sordilla@nmsc.edu.ph', 'password_reset', '::1', 1, '2026-04-12 08:50:31'),
(29, 'mike.sordilla@nmsc.edu.ph', '', '::1', 1, '2026-04-12 08:50:49'),
(30, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-17 12:30:40'),
(31, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-17 12:30:48'),
(32, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 12:31:00'),
(33, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 12:44:22'),
(34, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-17 13:50:49'),
(35, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-17 13:50:55'),
(36, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-17 13:51:04'),
(37, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 13:51:37'),
(38, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 13:51:59'),
(39, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 14:47:59'),
(40, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-17 14:48:12'),
(41, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-17 14:48:25'),
(42, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 12:36:30'),
(43, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-18 14:07:43'),
(44, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-18 14:07:53'),
(45, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-18 14:08:01'),
(46, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 14:08:13'),
(47, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 14:37:06'),
(48, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 14:55:46'),
(49, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-18 15:06:17'),
(50, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-18 15:06:25'),
(51, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 15:06:35'),
(52, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 22:51:00'),
(53, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 22:51:05'),
(54, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 22:51:13'),
(55, 'admin@local.admin', '', '::1', 0, '2026-04-18 22:51:22'),
(56, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 22:52:26'),
(57, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 22:53:27'),
(58, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 23:04:14'),
(59, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 23:04:57'),
(60, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 23:05:00'),
(61, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-18 23:07:48'),
(62, 'admin@local.admin', '', '::1', 0, '2026-04-18 23:07:58'),
(63, 'admin@local.admin', '', '::1', 0, '2026-04-18 23:08:26'),
(64, 'qa.login.test@example.com', 'login_attempt', '127.0.0.1', 1, '2026-04-18 23:26:20'),
(65, 'qa.login.test@example.com', 'login_attempt', '127.0.0.1', 1, '2026-04-18 23:26:26'),
(66, 'qa.login.test@example.com', 'login_attempt', '::1', 1, '2026-04-18 23:26:39'),
(67, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 23:27:22'),
(68, 'qa.login.test@example.com', 'login_attempt', '::1', 1, '2026-04-18 23:28:16'),
(69, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-18 23:29:21'),
(70, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-18 23:29:38'),
(71, 'qa.login.test@example.com', 'login_attempt', '::1', 1, '2026-04-18 23:32:42'),
(72, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 07:52:38'),
(73, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 07:52:42'),
(74, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 07:52:51'),
(75, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 07:53:01'),
(76, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 07:53:27'),
(77, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 07:53:36'),
(78, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 07:53:50'),
(79, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 08:02:28'),
(80, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 08:11:50'),
(81, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 08:13:40'),
(82, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 10:15:29'),
(83, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 10:56:50'),
(84, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 11:14:07'),
(85, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 12:04:25'),
(86, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 13:21:56'),
(87, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 13:27:03'),
(88, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-19 13:48:41'),
(89, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 13:48:45'),
(90, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 14:27:14'),
(91, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 14:49:08'),
(92, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 14:54:17'),
(93, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 15:02:09'),
(94, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 15:29:30'),
(95, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 15:30:32'),
(96, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:26:33'),
(97, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 21:28:56'),
(98, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 21:29:05'),
(99, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:32:27'),
(100, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:34:34'),
(101, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:38:15'),
(102, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 21:39:25'),
(103, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 21:39:34'),
(104, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 21:54:06'),
(105, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 21:54:13'),
(106, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:55:14'),
(107, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 21:56:44'),
(108, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-19 23:31:29'),
(109, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-19 23:31:37'),
(110, 'queenstephanie.betonio@nmsc.edu.ph', '', '::1', 1, '2026-04-19 23:32:29'),
(111, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 23:32:34'),
(112, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-19 23:44:37'),
(113, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-20 00:22:02'),
(114, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 00:22:09'),
(115, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 06:55:37'),
(116, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 06:55:50'),
(117, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-20 06:56:25'),
(118, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 06:56:38'),
(119, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 06:57:15'),
(120, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-20 06:58:51'),
(121, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 06:58:59'),
(122, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 06:59:17'),
(123, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 07:26:27'),
(124, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-20 07:34:50'),
(125, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 07:34:53'),
(126, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:21:39'),
(127, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:28:54'),
(128, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:34:34'),
(129, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:39:09'),
(130, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:42:51'),
(131, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 08:44:05'),
(132, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 09:03:03'),
(133, 'admin@library.local', 'login_attempt', '::1', 0, '2026-04-20 09:08:34'),
(134, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 09:08:43'),
(135, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 09:09:38'),
(136, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-20 09:10:19'),
(137, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-20 09:10:23'),
(138, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 09:10:30'),
(139, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 0, '2026-04-20 09:10:53'),
(140, 'admin@local.admin', 'login_attempt', '::1', 1, '2026-04-20 09:11:06'),
(141, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 09:11:30'),
(142, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 09:27:59'),
(143, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 09:28:10'),
(144, 'queenstephanie.betonio@nmsc.edu.ph', 'password_reset', '::1', 1, '2026-04-20 12:00:28'),
(145, 'mike.sordilla@nmsc.edu.ph', 'password_reset', '::1', 1, '2026-04-20 12:01:06'),
(146, 'mike.sordilla@nmsc.edu.ph', '', '::1', 0, '2026-04-20 12:01:19'),
(147, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 12:23:49'),
(148, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 21:33:14'),
(149, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 22:05:10'),
(150, 'queenstephanie.betonio@nmsc.edu.ph', '', '::1', 0, '2026-04-20 22:14:20'),
(151, 'queenstephanie.betonio@nmsc.edu.ph', '', '::1', 0, '2026-04-20 22:14:25'),
(152, 'queenstephaniebetonio@gmail.com', '', '::1', 0, '2026-04-20 22:14:40'),
(153, 'admin@local.admin', '', '::1', 0, '2026-04-20 22:14:49'),
(154, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:07:18'),
(155, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:08:04'),
(156, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-20 23:08:25'),
(157, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:15:29'),
(158, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:21:05'),
(159, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:22:45'),
(160, 'queenstephaniebetonio@gmail.com', 'login_attempt', '::1', 0, '2026-04-20 23:23:38'),
(161, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-21 01:28:09'),
(162, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-21 01:58:02'),
(163, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-23 13:50:33'),
(164, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '::1', 1, '2026-04-23 13:51:22'),
(165, 'admin@local.admin', 'login_attempt', '124.217.16.30', 1, '2026-04-23 14:08:32'),
(166, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '124.217.16.30', 1, '2026-04-23 14:09:41'),
(167, 'queenstephanie.betonio@nmsc.edu.p', 'login_attempt', '124.217.16.30', 0, '2026-04-23 14:11:29'),
(168, 'queenstephanie.betonio@gmail.com', 'login_attempt', '124.217.16.30', 0, '2026-04-23 14:12:21'),
(169, 'admin@local.admin', 'login_attempt', '124.217.16.30', 1, '2026-04-23 14:12:32'),
(170, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '124.217.16.30', 1, '2026-04-23 14:12:47'),
(171, 'mike.sordilla@nmsc.edu.ph', 'login_attempt', '124.217.16.30', 1, '2026-04-26 13:04:27'),
(172, 'queenstephanie.betonio@gmail.com', 'login_attempt', '124.217.16.30', 0, '2026-04-26 13:05:12'),
(173, 'admin@local.admin', 'login_attempt', '124.217.16.30', 1, '2026-04-26 13:05:27'),
(174, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '124.217.16.30', 1, '2026-04-26 13:05:43'),
(175, 'admin@local.admin', 'login_attempt', '124.217.16.30', 1, '2026-04-27 01:48:46'),
(176, 'admin@local.admin', 'login_attempt', '124.217.16.30', 1, '2026-04-27 01:49:32'),
(177, 'queenstephanie.betonio@nmsc.edu.ph', 'login_attempt', '124.217.16.30', 1, '2026-04-27 01:53:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin_username` (`admin_username`),
  ADD KEY `idx_admin_profiles_username` (`admin_username`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_books_title` (`title`),
  ADD KEY `idx_books_author` (`author`),
  ADD KEY `idx_books_category` (`category`),
  ADD KEY `idx_books_isbn` (`isbn`),
  ADD KEY `idx_books_available` (`available_copies`);
ALTER TABLE `books` ADD FULLTEXT KEY `idx_books_search` (`title`,`author`,`description`);

--
-- Indexes for table `book_copies`
--
ALTER TABLE `book_copies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_book_copies_barcode` (`barcode`),
  ADD KEY `idx_book_copies_status` (`status`),
  ADD KEY `idx_book_copies_book_id` (`book_id`);

--
-- Indexes for table `fine_assessments`
--
ALTER TABLE `fine_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessed_by_user_id` (`assessed_by_user_id`),
  ADD KEY `idx_fine_assessments_loan` (`loan_id`),
  ADD KEY `idx_fine_assessments_borrower` (`borrower_user_id`),
  ADD KEY `idx_fine_assessments_status` (`status`);

--
-- Indexes for table `fine_collections`
--
ALTER TABLE `fine_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_code` (`receipt_code`),
  ADD KEY `borrower_user_id` (`borrower_user_id`),
  ADD KEY `idx_fine_collections_collected_at` (`collected_at`),
  ADD KEY `idx_fine_collections_status` (`status`),
  ADD KEY `idx_fine_collections_collector` (`collected_by_user_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loans_borrower` (`borrower_user_id`),
  ADD KEY `idx_loans_book` (`book_id`),
  ADD KEY `idx_loans_status` (`status`),
  ADD KEY `idx_loans_due_date` (`due_date`),
  ADD KEY `idx_loans_return_date` (`return_date`);

--
-- Indexes for table `loan_events`
--
ALTER TABLE `loan_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loan_events_loan_time` (`loan_id`,`event_at`),
  ADD KEY `idx_loan_events_actor` (`actor_user_id`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_login` (`user_id`,`login_time`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `related_loan_id` (`related_loan_id`),
  ADD KEY `related_reservation_id` (`related_reservation_id`),
  ADD KEY `related_fine_id` (`related_fine_id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_type` (`type`),
  ADD KEY `idx_notifications_created` (`created_at`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_otp` (`user_id`,`is_used`),
  ADD KEY `idx_otp_expires` (`expires_at`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservations_borrower` (`borrower_user_id`),
  ADD KEY `idx_reservations_book` (`book_id`),
  ADD KEY `idx_reservations_status` (`status`),
  ADD KEY `idx_reservations_expires` (`expires_at`);

--
-- Indexes for table `role_profiles`
--
ALTER TABLE `role_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_profiles_user` (`user_id`),
  ADD KEY `idx_role_profiles_role` (`role`);

--
-- Indexes for table `schema_migrations`
--
ALTER TABLE `schema_migrations`
  ADD PRIMARY KEY (`migration_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_verification_token` (`verification_token`),
  ADD KEY `idx_users_reset_token` (`reset_token`),
  ADD KEY `idx_users_is_verified` (`is_verified`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_is_superadmin` (`is_superadmin`);

--
-- Indexes for table `verification_attempts`
--
ALTER TABLE `verification_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_type` (`email`,`attempt_type`),
  ADD KEY `idx_ip_time` (`ip_address`,`attempted_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_profiles`
--
ALTER TABLE `admin_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `book_copies`
--
ALTER TABLE `book_copies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fine_assessments`
--
ALTER TABLE `fine_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fine_collections`
--
ALTER TABLE `fine_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_events`
--
ALTER TABLE `loan_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `role_profiles`
--
ALTER TABLE `role_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `verification_attempts`
--
ALTER TABLE `verification_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=178;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `book_copies`
--
ALTER TABLE `book_copies`
  ADD CONSTRAINT `book_copies_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fine_assessments`
--
ALTER TABLE `fine_assessments`
  ADD CONSTRAINT `fine_assessments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fine_assessments_ibfk_2` FOREIGN KEY (`borrower_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fine_assessments_ibfk_3` FOREIGN KEY (`assessed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fine_collections`
--
ALTER TABLE `fine_collections`
  ADD CONSTRAINT `fine_collections_ibfk_1` FOREIGN KEY (`borrower_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fine_collections_ibfk_2` FOREIGN KEY (`collected_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`borrower_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loan_events`
--
ALTER TABLE `loan_events`
  ADD CONSTRAINT `loan_events_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_events_ibfk_2` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `login_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_loan_id`) REFERENCES `loans` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`related_reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`related_fine_id`) REFERENCES `fine_assessments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `otp_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`borrower_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_profiles`
--
ALTER TABLE `role_profiles`
  ADD CONSTRAINT `role_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
