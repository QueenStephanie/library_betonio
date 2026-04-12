-- Migration: Drop legacy admin auth tables after role-based unified login rollout
-- Safe to run multiple times.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS admin_session_registry;
DROP TABLE IF EXISTS admin_credentials;

SET FOREIGN_KEY_CHECKS = 1;
