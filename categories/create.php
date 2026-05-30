<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$errors = [];
$errorMessage = '';

$name = '';
$type = '';
$description = '';

$categoryColumnsStmt = $pdo->query('SHOW COLUMNS FROM categories');
$categoryColumns = $categoryColumnsStmt->fetchAll(PDO::FETCH_COLUMN);
$supportsType = in_array('type', $categoryColumns, true);
$supportsDescription = in_array('description', $categoryColumns, true);

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
    }

    if ($supportsType && !in_array($type, ['income', 'expense'], true)) {
        $errors[] = 'Type wajib dipilih (income/expense).';
    }

    if ($errors === []) {
        $duplicateStmt = $pdo->prepare(
            'SELECT id FROM categories WHERE user_id = :user_id AND LOWER(name) = LOWER(:name) LIMIT 1'
        );

        $duplicateStmt->execute([
            'user_id' => $userId,
            'name' => $name,
        ]);

        if ($duplicateStmt->fetchColumn() !== false) {
            $errors[] = 'Nama kategori sudah ada.';
        }
    }

    if ($errors === []) {
        $insertColumns = ['user_id', 'name'];
        $insertParams = [
            'user_id' => $userId,
            'name' => $name,
        ];

        if ($supportsType) {
            $insertColumns[] = 'type';
            $insertParams['type'] = $type;
        }

        if ($supportsDescription) {
            $insertColumns[] = 'description';
            $insertParams['description'] = $description !== '' ? $description : null;
        }

        $placeholders = array_map(static fn (string $column): string => ':' . $column, $insertColumns);

        $insertSql = sprintf(
            'INSERT INTO categories (%s) VALUES (%s)',
            implode(', ', $insertColumns),
            implode(', ', $placeholders)
        );

        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute($insertParams);

        $_SESSION['success_message'] = 'Kategori berhasil ditambahkan.';
        header('Location: index.php');
        exit;
    }

    $errorMessage = implode(' ', $errors);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Tambah Kategori — ' . APP_NAME;
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
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Tambah Kategori</h1>
                    <p class="text-sm text-slate-500 mt-1">Buat kategori baru untuk transaksi Anda.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-700 text-sm">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 sm:p-6">
                    <form method="POST" action="" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori</label>
                            <input id="name" name="name" type="text" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                        </div>

                        <?php if ($supportsType): ?>
                            <div>
                                <label for="type" class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                                <select id="type" name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>
                                    <option value="">Pilih type</option>
                                    <option value="income" <?= $type === 'income' ? 'selected' : ''; ?>>Income</option>
                                    <option value="expense" <?= $type === 'expense' ? 'selected' : ''; ?>>Expense</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                            <textarea id="description" name="description" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                            <a href="index.php" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Batal</a>
                            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">Simpan Kategori</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
