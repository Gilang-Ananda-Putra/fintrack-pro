<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Root Entry Point
 *
 * Mengarahkan user ke dashboard jika sudah login,
 * atau ke halaman login jika belum terautentikasi.
 */

require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
} else {
    header('Location: ' . rtrim(BASE_URL, '/') . '/auth/login.php');
}

exit;
