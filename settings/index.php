<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$userStmt = $pdo->prepare('SELECT id, name, email, password FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

if ($user === false) {
    set_flash('error', 'Sesi tidak valid. Silakan login kembali.');
    redirect(rtrim(BASE_URL, '/') . '/auth/logout.php');
}

$name = (string) $user['name'];
$email = (string) $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $formAction = (string) ($_POST['form_action'] ?? '');

    if (!verify_csrf_token($csrfToken)) {
        set_flash('error', 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.');
        redirect('index.php');
    }

    if ($formAction === 'update_profile') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Nama tidak boleh kosong.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }

        if ($errors === []) {
            $emailStmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
            $emailStmt->execute([
                'email' => $email,
                'id' => $userId,
            ]);

            if ($emailStmt->fetchColumn() !== false) {
                $errors[] = 'Email sudah digunakan oleh akun lain.';
            }
        }

        if ($errors === []) {
            $updateStmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
            $updated = $updateStmt->execute([
                'name' => $name,
                'email' => $email,
                'id' => $userId,
            ]);

            if ($updated) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                set_flash('success', 'Profil berhasil diperbarui.');
                redirect('index.php');
            }

            $errors[] = 'Gagal memperbarui profil. Silakan coba lagi.';
        }

        set_flash('error', implode(' ', $errors));
        redirect('index.php');
    }

    if ($formAction === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        $errors = [];

        if (!password_verify($currentPassword, (string) $user['password'])) {
            $errors[] = 'Password saat ini tidak sesuai.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }

        if (!hash_equals($newPassword, $confirmPassword)) {
            $errors[] = 'Konfirmasi password baru tidak sama.';
        }

        if ($errors === []) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordStmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
            $updated = $passwordStmt->execute([
                'password' => $passwordHash,
                'id' => $userId,
            ]);

            if ($updated) {
                set_flash('success', 'Password berhasil diperbarui.');
                redirect('index.php');
            }

            $errors[] = 'Gagal memperbarui password. Silakan coba lagi.';
        }

        set_flash('error', implode(' ', $errors));
        redirect('index.php');
    }

    set_flash('error', 'Aksi form tidak dikenali.');
    redirect('index.php');
}

$successMessage = get_flash('success');
$errorMessage = get_flash('error');
$csrfToken = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Settings — ' . APP_NAME;
    include __DIR__ . '/../includes/head.php';
    ?>
</head>
<body class="bg-background min-h-screen">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 md:ml-[280px]">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="p-4 md:p-8">
            <div class="max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 class="text-2xl font-bold">Settings</h1>
                    <p class="text-on-surface-variant">Manage your account preferences and security settings.</p>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-outline-variant/40 p-6">
                    <h2 class="font-semibold mb-4">Profile Settings</h2>
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_action" value="update_profile">

                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-on-surface mb-1">Name</label>
                                <input id="name" name="name" type="text" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-on-surface mb-1">Email</label>
                                <input id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-primary text-on-primary px-4 py-2.5 font-semibold">Update Profile</button>
                        </div>
                    </form>
                </section>

                <section class="bg-white rounded-xl border border-outline-variant/40 p-6">
                    <h2 class="font-semibold mb-4">Preferences</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <select class="rounded-lg border border-outline-variant px-3 py-2.5">
                            <option>IDR (Rp)</option>
                            <option>USD ($)</option>
                        </select>
                        <select class="rounded-lg border border-outline-variant px-3 py-2.5">
                            <option>Indonesia</option>
                            <option>English</option>
                        </select>
                    </div>
                </section>

                <section class="bg-white rounded-xl border border-outline-variant/40 p-6">
                    <h2 class="font-semibold mb-4">Security</h2>
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_action" value="change_password">

                        <div class="grid md:grid-cols-3 gap-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-on-surface mb-1">Current Password</label>
                                <input id="current_password" name="current_password" type="password" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
                            </div>

                            <div>
                                <label for="new_password" class="block text-sm font-medium text-on-surface mb-1">New Password</label>
                                <input id="new_password" name="new_password" type="password" minlength="8" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
                            </div>

                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-on-surface mb-1">Confirm Password</label>
                                <input id="confirm_password" name="confirm_password" type="password" minlength="8" class="w-full rounded-lg border border-outline-variant px-3 py-2.5" required>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-primary text-on-primary px-4 py-2.5 font-semibold">Change Password</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
