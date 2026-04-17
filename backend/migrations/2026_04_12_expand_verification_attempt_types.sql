-- Canonical migration: align verification_attempts.attempt_type with Auth logger values
-- Safe to run once on existing databases.
-- NOTE: 2026_04_17_harden_verification_attempt_types.sql is intentionally superseded
--       to prevent duplicate enum-alter confusion.

ALTER TABLE verification_attempts
  MODIFY COLUMN attempt_type ENUM('password_reset', 'registration', 'login_attempt', 'password_reset_verify', 'otp_verify', 'otp_resend', 'csrf_reject', 'login_blocked') NOT NULL;
