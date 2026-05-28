-- =============================================================
-- FinTrack Pro - MySQL Database Schema
-- Engine: InnoDB
-- Desain ini mendukung multi-user, relasi kategori per user,
-- dan transaksi keuangan dengan integritas referensial.
-- =============================================================

-- Opsional: buat database jika belum ada
CREATE DATABASE IF NOT EXISTS fintrack_pro
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fintrack_pro;

-- -------------------------------------------------------------
-- 1) Tabel users
-- Menyimpan data akun pengguna aplikasi.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Email harus unik agar tidak ada akun duplikat
  CONSTRAINT uq_users_email UNIQUE (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Master data pengguna aplikasi';

-- -------------------------------------------------------------
-- 2) Tabel categories
-- Menyimpan kategori transaksi milik user tertentu.
-- Satu user dapat memiliki banyak kategori.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  color VARCHAR(20) DEFAULT NULL,
  icon VARCHAR(100) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Hindari nama kategori duplikat untuk user yang sama
  CONSTRAINT uq_categories_user_name UNIQUE (user_id, name),

  -- Relasi ke users: jika user dihapus, kategori ikut dihapus
  CONSTRAINT fk_categories_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Kategori transaksi per pengguna';

-- -------------------------------------------------------------
-- 3) Tabel transactions
-- Menyimpan pemasukan/pengeluaran user.
-- Relasi ke user dan kategori untuk pelaporan yang konsisten.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED DEFAULT NULL,
  title VARCHAR(150) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  type ENUM('income', 'expense') NOT NULL,
  note TEXT DEFAULT NULL,
  transaction_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  -- Index untuk query umum dashboard/report
  INDEX idx_transactions_user_date (user_id, transaction_date),
  INDEX idx_transactions_category (category_id),
  INDEX idx_transactions_type (type),

  -- Relasi ke users: jika user dihapus, transaksi ikut dihapus
  CONSTRAINT fk_transactions_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  -- Relasi ke categories: jika kategori dihapus,
  -- transaksi tetap ada, hanya category_id dibuat NULL
  CONSTRAINT fk_transactions_category
    FOREIGN KEY (category_id)
    REFERENCES categories(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Data transaksi keuangan user';
