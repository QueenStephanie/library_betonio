-- Migration: align verification_attempts.attempt_type with Auth logger values
-- Safe to run once on existing databases.

ALTER TABLE verification_attempts
  MODIFY COLUMN attempt_type ENUM('password_reset', 'registration', 'login_attempt', 'password_reset_verify') NOT NULL;
