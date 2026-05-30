/*
SQLyog Ultimate v13.1.1 (64 bit)
MySQL - 8.0.30 : Database - fintrack_pro
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`fintrack_pro` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `fintrack_pro`;

/*Table structure for table `categories` */

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#2563EB',
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'wallet',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_user_name` (`user_id`,`name`),
  KEY `idx_categories_user_type` (`user_id`,`type`),
  CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `categories` */

insert  into `categories`(`id`,`user_id`,`name`,`type`,`color`,`icon`,`description`,`created_at`,`updated_at`) values 
(1,1,'Gaji','income','#006e2f','payments','Gaji bulanan dan bonus','2026-05-30 10:47:47',NULL),
(2,1,'Freelance','income','#0ea5e9','laptop_mac','Pendapatan dari pekerjaan sampingan','2026-05-30 10:47:47',NULL),
(3,1,'Investasi','income','#8b5cf6','trending_up','Dividen, bunga deposito, reksa dana','2026-05-30 10:47:47',NULL),
(4,1,'Makanan & Minuman','expense','#ba1a1a','restaurant','Makan, minum, kopi, snack','2026-05-30 10:47:47',NULL),
(5,1,'Transportasi','expense','#dc2626','directions_car','Bensin, parkir, ojek, transport umum','2026-05-30 10:47:47',NULL),
(6,1,'Tagihan & Utilitas','expense','#7c3aed','receipt_long','Listrik, air, internet, pulsa','2026-05-30 10:47:47',NULL),
(7,1,'Belanja','expense','#db2777','shopping_bag','Pakaian, elektronik, kebutuhan rumah','2026-05-30 10:47:47',NULL),
(8,1,'Kesehatan','expense','#0891b2','health_and_safety','Obat, dokter, vitamin','2026-05-30 10:47:47',NULL),
(9,1,'Hiburan','expense','#65a30d','movie','Film, game, konser, streaming','2026-05-30 10:47:47',NULL),
(10,1,'Pendidikan','expense','#2563eb','school','Kursus, buku, biaya sekolah','2026-05-30 10:47:47',NULL),
(11,1,'Tabungan','expense','#059669','savings','Transfer ke rekening tabungan','2026-05-30 10:47:47',NULL);

/*Table structure for table `transactions` */

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transactions_user_date` (`user_id`,`transaction_date`),
  KEY `idx_transactions_user_type` (`user_id`,`type`),
  KEY `idx_transactions_category` (`category_id`),
  KEY `idx_transactions_user_month` (`user_id`,`transaction_date`,`type`),
  CONSTRAINT `fk_transactions_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_transactions_amount_positive` CHECK ((`amount` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `transactions` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `users` */

insert  into `users`(`id`,`name`,`email`,`password`,`created_at`,`updated_at`) values 
(1,'Demo User','demo@fintrack.pro','$2y$10$wH6ehKko.L3yxDWbPdtvKe0y7LgWrCj3S4lW0LZfQ7bX1mER5eBym','2026-05-30 10:47:47',NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
