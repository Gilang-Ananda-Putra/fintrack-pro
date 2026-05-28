<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Auth Guard
 *
 * Include file ini di awal setiap halaman yang memerlukan autentikasi.
 * Jika user belum login, akan di-redirect ke halaman login
 * menggunakan BASE_URL sehingga path selalu benar di semua environment.
 */

// Muat konfigurasi app jika belum dimuat
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// Mulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Periksa keberadaan user_id di session
if (!isset($_SESSION['user_id']) || !is_int($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/auth/login.php');
    exit;
}
