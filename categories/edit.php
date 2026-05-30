<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    redirect('../auth/login.php');
}

$categoryIdRaw = trim((string) ($_GET['id'] ?? $_POST['id'] ?? ''));
if (!ctype_digit($categoryIdRaw)) {
    set_flash('error', 'ID kategori tidak valid.');
    redirect('index.php');
}

$categoryId = (int) $categoryIdRaw;

$categoryStmt = $pdo->prepare(
    'SELECT id, name, type, description
     FROM categories
     WHERE id = :id AND user_id = :user_id
     LIMIT 1'
);
$categoryStmt->execute([
    'id' => $categoryId,
    'user_id' => $userId,
]);
$category = $categoryStmt->fetch();

if ($category === false) {
    set_flash('error', 'Kategori tidak ditemukan atau bukan milik Anda.');
    redirect('index.php');
}

$name = (string) $category['name'];
$type = (string) ($category['type'] ?? '');
$description = (string) ($category['description'] ?? '');

$errorMessage = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $type = trim((string) ($_POST['type'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if (!verify_csrf_token($csrfToken)) {
        $errors[] = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    }

    if ($name === '') {
        $errors[] = 'Nama kategori wajib diisi.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Nama kategori maksimal 100 karakter.';
    }

    if (!in_array($type, ['income', 'expense'], true)) {
        $errors[] = 'Tipe kategori harus income atau expense.';
    }

    if (mb_strlen($description) > 500) {
        $errors[] = 'Deskripsi kategori maksimal 500 karakter.';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT id
             FROM categories
             WHERE user_id = :user_id
               AND LOWER(name) = LOWER(:name)
               AND id <> :id
             LIMIT 1'
        );
        $duplicateStmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'id' => $categoryId,
        ]);

        if ($duplicateStmt->fetchColumn() !== false) {
            $errors[] = 'Nama kategori sudah digunakan. Silakan gunakan nama lain.';
        }
    }

    if ($errors === []) {
        $updateStmt = $pdo->prepare(
            'UPDATE categories
             SET name = :name,
                 type = :type,
                 description = :description
             WHERE id = :id AND user_id = :user_id'
        );

        $updated = $updateStmt->execute([
            'name' => $name,
            'type' => $type,
            'description' => $description !== '' ? $description : null,
            'id' => $categoryId,
            'user_id' => $userId,
        ]);

        if ($updated) {
            set_flash('success', 'Kategori berhasil diperbarui.');
            redirect('index.php');
        }

        $errors[] = 'Gagal memperbarui kategori. Silakan coba lagi.';
    }

    $errorMessage = implode(' ', $errors);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Edit Category — ' . APP_NAME;
    include __DIR__ . '/../includes/head.php';
    ?>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-w-0">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Edit Kategori</h1>
                    <p class="text-sm text-slate-500 mt-1">Perbarui detail kategori transaksi Anda.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700 text-sm">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <form method="POST" action="" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) $categoryId, ENT_QUOTES, 'UTF-8'); ?>">

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori</label>
                            <input id="name" name="name" type="text" maxlength="100" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Tipe</label>
                            <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                <option value="">Pilih tipe</option>
                                <option value="income" <?= $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                <option value="expense" <?= $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                            </select>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                            <textarea id="description" name="description" rows="4" maxlength="500" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                            <a href="index.php" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Kembali ke Daftar Kategori</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Update Kategori</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
