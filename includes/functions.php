<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Fungsi-fungsi Pembantu (Helper Functions)
 *
 * File ini berisi fungsi utilitas yang digunakan di seluruh aplikasi.
 * Include file ini setelah session_start() dan config/app.php.
 */

/**
 * Redirect ke URL tertentu dan hentikan eksekusi script.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Format angka menjadi format mata uang Rupiah.
 * Contoh: 150000 → "Rp 150.000"
 */
function formatRupiah(float|int $number): string
{
    return 'Rp ' . number_format((float) $number, 0, ',', '.');
}

/**
 * Generate atau ambil CSRF token dari session.
 * Session harus sudah aktif sebelum fungsi ini dipanggil.
 */
function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        trigger_error('generate_csrf_token() dipanggil sebelum session aktif.', E_USER_WARNING);
        return '';
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token yang dikirim via POST.
 * Mengembalikan true jika token valid, false jika tidak.
 */
function verify_csrf_token(string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $token);
}

/**
 * Set flash message ke session (hanya tampil sekali).
 *
 * @param 'success'|'error' $type
 */
function set_flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $key = match ($type) {
        'success' => 'success_message',
        'error'   => 'error_message',
        default   => 'success_message',
    };

    $_SESSION[$key] = $message;
}

/**
 * Ambil dan hapus flash message dari session (consume once).
 * Mengembalikan string kosong jika tidak ada.
 */
function get_flash(string $type): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }

    $key = match ($type) {
        'success' => 'success_message',
        'error'   => 'error_message',
        default   => 'success_message',
    };

    if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key])) {
        return '';
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $message;
}
