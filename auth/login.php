<?php

declare(strict_types=1);

/**
 * FinTrack Pro — Login
 *
 * Menangani autentikasi user. Setelah login berhasil,
 * data id, name, dan email disimpan ke session
 * supaya bisa digunakan di seluruh halaman aplikasi.
 */

// Pastikan session dimulai sebelum apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        // Validasi input tidak kosong
        if ($email === '' || $password === '') {
            $error = 'Email dan password wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Ambil id, name, email, password dari database
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user !== false && password_verify($password, (string) $user['password'])) {
                // Regenerate session ID untuk mencegah session fixation attack
                session_regenerate_id(true);

                // Simpan data user ke session secara lengkap
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['name']    = (string) $user['name'];
                $_SESSION['email']   = (string) $user['email'];

                header('Location: ' . rtrim(BASE_URL, '/') . '/dashboard/index.php');
                exit;
            }

            // Pesan error generik (tidak memberitahu apakah email atau password yang salah)
            $error = 'Email atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Login — ' . APP_NAME;
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
            <p class="text-sm text-slate-500 mt-1">Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">

            <h2 class="text-xl font-semibold text-slate-900 mb-6">Masuk</h2>

            <?php if ($error !== ''): ?>
                <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 mb-5 text-sm text-rose-700">
                    <span class="material-symbols-outlined text-rose-500 text-[18px] mt-0.5 shrink-0">error</span>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" novalidate>
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"
                >

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

                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-4 rounded-lg text-sm transition"
                >
                    Masuk
                </button>
            </form>

        </div>

        <p class="text-center mt-5 text-sm text-slate-600">
            Belum punya akun?
            <a href="register.php" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                Daftar sekarang
            </a>
        </p>

    </div>

</body>
</html>
