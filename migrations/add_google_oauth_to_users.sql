-- FinTrack Pro migration: add Google OAuth fields to existing users table.
-- Run this once before enabling Google login on an existing database.

USE fintrack_pro;

ALTER TABLE users
    ADD COLUMN google_id VARCHAR(191) NULL DEFAULT NULL AFTER password,
    ADD COLUMN avatar_url VARCHAR(500) NULL DEFAULT NULL AFTER google_id,
    ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER avatar_url,
    ADD UNIQUE KEY uq_users_google_id (google_id);

UPDATE users
SET email_verified = 1
WHERE email = 'demo@fintrack.pro';