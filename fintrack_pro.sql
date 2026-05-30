-- =============================================================================
-- FinTrack Pro — Database Schema Final
-- Engine  : InnoDB
-- Charset : utf8mb4
-- Notes   : Fresh install script. This script DROPS existing tables in fintrack_pro.
-- =============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE IF NOT EXISTS `fintrack_pro`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `fintrack_pro`;

-- Drop child tables first so the script is safe to rerun.
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- =============================================================================
-- 1. users
-- =============================================================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` VARCHAR(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_id` VARCHAR(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_url` VARCHAR(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 2. categories
-- =============================================================================
CREATE TABLE `categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` ENUM('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT '#2563EB',
  `icon` VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT 'wallet',
  `description` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_user_name` (`user_id`,`name`),
  KEY `idx_categories_user_type` (`user_id`,`type`),
  CONSTRAINT `fk_categories_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- 3. transactions
-- =============================================================================
CREATE TABLE `transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `type` ENUM('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` TEXT COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transactions_user_date` (`user_id`,`transaction_date`),
  KEY `idx_transactions_user_type` (`user_id`,`type`),
  KEY `idx_transactions_category` (`category_id`),
  KEY `idx_transactions_user_month` (`user_id`,`transaction_date`,`type`),
  CONSTRAINT `fk_transactions_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_transactions_amount_positive` CHECK (`amount` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Seed data (optional demo account)
-- Demo email    : demo@fintrack.pro
-- Demo password : Demo1234
-- =============================================================================
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `email_verified`)
VALUES (
  'Demo User',
  'demo@fintrack.pro',
  '$2y$12$uUK2PRnP9CxcPbJcW1rr3eGnxwyv4Ct5K8NbR2YSQ0t8wQjXXTzq2',
  1
);

INSERT IGNORE INTO `categories` (`user_id`, `name`, `type`, `color`, `icon`, `description`)
SELECT u.`id`, c.`name`, c.`type`, c.`color`, c.`icon`, c.`description`
FROM `users` u
CROSS JOIN (
  SELECT 'Gaji' AS `name`, 'income' AS `type`, '#006e2f' AS `color`, 'payments' AS `icon`, 'Gaji bulanan dan bonus' AS `description`
  UNION ALL SELECT 'Freelance', 'income', '#0ea5e9', 'laptop_mac', 'Pendapatan dari pekerjaan sampingan'
  UNION ALL SELECT 'Investasi', 'income', '#8b5cf6', 'trending_up', 'Dividen, bunga deposito, reksa dana'
  UNION ALL SELECT 'Bisnis', 'income', '#f59e0b', 'storefront', 'Pendapatan dari usaha'
  UNION ALL SELECT 'Makanan & Minuman', 'expense', '#ba1a1a', 'restaurant', 'Makan, minum, kopi, snack'
  UNION ALL SELECT 'Transportasi', 'expense', '#dc2626', 'directions_car', 'Bensin, parkir, ojek, transport umum'
  UNION ALL SELECT 'Tagihan & Utilitas', 'expense', '#7c3aed', 'receipt_long', 'Listrik, air, internet, pulsa'
  UNION ALL SELECT 'Belanja', 'expense', '#db2777', 'shopping_bag', 'Pakaian, elektronik, kebutuhan rumah'
  UNION ALL SELECT 'Kesehatan', 'expense', '#0891b2', 'health_and_safety', 'Obat, dokter, vitamin'
  UNION ALL SELECT 'Hiburan', 'expense', '#65a30d', 'movie', 'Film, game, konser, streaming'
  UNION ALL SELECT 'Pendidikan', 'expense', '#2563eb', 'school', 'Kursus, buku, biaya sekolah'
  UNION ALL SELECT 'Tabungan', 'expense', '#059669', 'savings', 'Transfer ke rekening tabungan'
) c
WHERE u.`email` = 'demo@fintrack.pro';

INSERT INTO `transactions` (`user_id`, `category_id`, `title`, `amount`, `type`, `note`, `transaction_date`)
SELECT u.`id`, c.`id`, t.`title`, t.`amount`, t.`type`, t.`note`, t.`transaction_date`
FROM `users` u
JOIN (
  SELECT 'Gaji' AS `category_name`, 'Gaji Mei 2026' AS `title`, 8000000.00 AS `amount`, 'income' AS `type`, NULL AS `note`, '2026-05-01' AS `transaction_date`
  UNION ALL SELECT 'Freelance', 'Project Website Client A', 3500000.00, 'income', 'Pembayaran tahap 2', '2026-05-05'
  UNION ALL SELECT 'Makanan & Minuman', 'Makan siang kantin', 45000.00, 'expense', NULL, '2026-05-06'
  UNION ALL SELECT 'Transportasi', 'Grab ke kantor', 25000.00, 'expense', NULL, '2026-05-07'
  UNION ALL SELECT 'Tagihan & Utilitas', 'Tagihan listrik April', 350000.00, 'expense', 'PLN pascabayar', '2026-05-08'
  UNION ALL SELECT 'Hiburan', 'Netflix bulan Mei', 54000.00, 'expense', 'Langganan streaming', '2026-05-10'
  UNION ALL SELECT 'Investasi', 'Dividen reksa dana', 210000.00, 'income', NULL, '2026-05-15'
  UNION ALL SELECT 'Kesehatan', 'Vitamin dan suplemen', 125000.00, 'expense', 'Apotik K24', '2026-05-15'
  UNION ALL SELECT 'Belanja', 'Baju baru', 399000.00, 'expense', 'Diskon 20%', '2026-05-17'
  UNION ALL SELECT 'Pendidikan', 'Kursus UI/UX online', 299000.00, 'expense', NULL, '2026-05-20'
  UNION ALL SELECT 'Tabungan', 'Transfer ke tabungan darurat', 1000000.00, 'expense', 'Rutin tiap bulan', '2026-05-26'
) t
JOIN `categories` c ON c.`user_id` = u.`id` AND c.`name` = t.`category_name`
WHERE u.`email` = 'demo@fintrack.pro';

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
