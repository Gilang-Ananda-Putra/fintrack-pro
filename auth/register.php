<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Register
 *
 * Menangani pendaftaran user baru.
 */

// Pastikan session dimulai sebelum apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
    exit;
}

$error   = '';
$success = '';
$name    = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        // Ambil data form
        $name             = trim((string) ($_POST['name'] ?? ''));
        $email            = trim((string) ($_POST['email'] ?? ''));
        $password         = (string) ($_POST['password'] ?? '');
        $confirmPassword  = (string) ($_POST['confirm_password'] ?? '');

        // Validasi: semua field wajib diisi
        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $error = 'Semua field wajib diisi.';

        // Validasi format email
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';

        // Validasi kekuatan password (minimal 8 karakter, ada huruf & angka)
        } elseif (
            strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/\d/', $password)
        ) {
            $error = 'Password minimal 8 karakter dan harus mengandung minimal 1 huruf serta 1 angka.';

        // Validasi konfirmasi password
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error = 'Konfirmasi password tidak cocok.';

        } else {
            // Cek apakah email sudah digunakan
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch() !== false) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
                );
                $isInserted = $insertStmt->execute([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => $hashedPassword,
                ]);

                if ($isInserted) {
                    $success = 'Pendaftaran berhasil! Mengarahkan ke halaman login…';
                    header('Refresh: 2; url=' . rtrim(BASE_URL, '/') . '/auth/login.php');
                } else {
                    $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Daftar — ' . APP_NAME;
    include __DIR__ . '/../includes/head.php';
    ?>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 mb-4">
                <span class="material-symbols-outlined text-white text-3xl">account_balance_wallet</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-sm text-slate-500 mt-1">Buat akun baru untuk mulai mencatat keuangan Anda</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">

            <h2 class="text-xl font-semibold text-slate-900 mb-6">Daftar Akun</h2>

            <?php if ($error !== ''): ?>
                <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 mb-5 text-sm text-rose-700">
                    <span class="material-symbols-outlined text-rose-500 text-[18px] mt-0.5 shrink-0">error</span>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 mb-5 text-sm text-emerald-700">
                    <span class="material-symbols-outlined text-emerald-500 text-[18px] mt-0.5 shrink-0">check_circle</span>
                    <span><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
                >

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="name">
                        Nama Lengkap
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="Nama Anda"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">
                        Alamat Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="contoh@email.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="Min. 8 karakter, ada huruf & angka"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="confirm_password">
                        Konfirmasi Password
                    </label>
                    <input
                        id="confirm_password"
                        type="password"
                        name="confirm_password"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="Ulangi password Anda"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition"
                    <?= $success !== '' ? 'disabled' : ''; ?>
                >
                    Daftar Sekarang
                </button>
            </form>

        </div>

        <p class="text-center mt-5 text-sm text-slate-600">
            Sudah punya akun?
            <a href="login.php" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                Masuk di sini
            </a>
        </p>

    </div>

</body>
</html>
