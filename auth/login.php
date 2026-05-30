@@ -3,51 +3,51 @@
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
$error = get_flash('error');
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
@@ -63,29 +63,34 @@ if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
<body class="bg-surface text-on-surface min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-md bg-surface-container-lowest rounded-xl border border-outline-variant/30 shadow-lg p-8">
<div class="text-center mb-6"><div class="w-12 h-12 rounded-lg bg-primary-container mx-auto flex items-center justify-center"><span class="material-symbols-outlined text-white">account_balance_wallet</span></div><h1 class="text-2xl font-bold mt-3">Welcome back</h1><p class="text-sm text-on-surface-variant">Masuk ke akun <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?></p></div>
<?php if ($error !== ''): ?><div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 p-3 text-sm"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<a href="google.php" class="mb-4 flex w-full items-center justify-center gap-2 rounded-lg border border-outline-variant px-3 py-2.5 text-sm font-semibold text-on-surface hover:bg-surface-container-high transition">
    <span class="material-symbols-outlined text-[18px]">login</span>
    Masuk dengan Google
</a>
<div class="mb-4 flex items-center gap-3 text-xs text-on-surface-variant"><span class="h-px flex-1 bg-outline-variant"></span><span>atau masuk manual</span><span class="h-px flex-1 bg-outline-variant"></span></div>
<form method="POST" action="" class="space-y-4"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>"><div><label for="email" class="block text-sm mb-1">Email</label><input id="email" type="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required></div><div><label for="password" class="block text-sm mb-1">Password</label><input id="password" type="password" name="password" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required></div><button class="w-full rounded-lg bg-primary text-on-primary py-2.5 font-semibold">Masuk</button></form>
<p class="text-center text-sm mt-5 text-on-surface-variant">Belum punya akun? <a href="register.php" class="text-primary font-semibold">Daftar sekarang</a></p></div>
</body>
</html>