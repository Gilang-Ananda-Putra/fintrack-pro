<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Register
 *
 * Menangani pendaftaran user baru.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
    exit;
}

$error = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
            $error = 'Semua field wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } elseif (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $error = 'Password minimal 8 karakter dan harus mengandung huruf serta angka.';
        } elseif (!hash_equals($password, $confirmPassword)) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $checkStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStmt->execute(['email' => $email]);

            if ($checkStmt->fetch() !== false) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
                );
                $isInserted = $insertStmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                if ($isInserted) {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;

                    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
                    exit;
                }

                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
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
<body class="bg-surface text-on-surface min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-lg p-8">
    <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-lg bg-primary-container mx-auto flex items-center justify-center">
            <span class="material-symbols-outlined text-white">person_add</span>
        </div>
        <h1 class="text-2xl font-bold mt-3">Create your account</h1>
        <p class="text-sm text-on-surface-variant">Buat akun <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <a href="google.php" class="mb-4 flex w-full items-center justify-center gap-2 rounded-lg border border-outline-variant px-3 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-high transition">
        <span class="material-symbols-outlined text-[18px]">login</span>
        Daftar / Masuk dengan Google
    </a>

    <div class="mb-4 flex items-center gap-3 text-xs text-on-surface-variant">
        <span class="h-px flex-1 bg-outline-variant"></span>
        <span>atau daftar manual</span>
        <span class="h-px flex-1 bg-outline-variant"></span>
    </div>

    <form method="POST" action="" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input name="name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nama lengkap" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
        <input type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
        <input type="password" name="password" placeholder="Password" minlength="8" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
        <input type="password" name="confirm_password" placeholder="Konfirmasi password" minlength="8" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
        <button class="w-full rounded-lg bg-primary text-on-primary py-2.5 font-semibold">Daftar & Masuk</button>
    </form>

    <p class="text-center text-sm mt-5 text-on-surface-variant">Sudah punya akun? <a href="login.php" class="text-primary font-semibold">Masuk</a></p>
</div>
</body>
</html>
