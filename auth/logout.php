<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Logout
 *
 * Menghapus semua data session dan mengarahkan user ke halaman login.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

// Hapus semua variabel session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ' . rtrim(BASE_URL, '/') . '/auth/login.php');
exit;
