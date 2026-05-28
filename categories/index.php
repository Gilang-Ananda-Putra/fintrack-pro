<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$flashSuccess = '';
$flashError = '';

if (isset($_SESSION['success_message']) && is_string($_SESSION['success_message'])) {
    $flashSuccess = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message']) && is_string($_SESSION['error_message'])) {
    $flashError = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $categoryId = trim((string) ($_POST['id'] ?? ''));

    if (!ctype_digit($categoryId)) {
        $_SESSION['error_message'] = 'ID kategori tidak valid.';
        header('Location: index.php');
        exit;
    }

    try {
        $deleteStmt = $pdo->prepare('DELETE FROM categories WHERE id = :id AND user_id = :user_id');
        $deleteStmt->execute([
            'id' => (int) $categoryId,
            'user_id' => $userId,
        ]);

        if ($deleteStmt->rowCount() > 0) {
            $_SESSION['success_message'] = 'Kategori berhasil dihapus.';
        } else {
            $_SESSION['error_message'] = 'Kategori tidak ditemukan atau bukan milik Anda.';
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $_SESSION['error_message'] = 'Kategori tidak dapat dihapus karena masih dipakai transaksi lain.';
        } else {
            $_SESSION['error_message'] = 'Terjadi kesalahan saat menghapus kategori.';
        }
    }

    header('Location: index.php');
    exit;
}

$listStmt = $pdo->prepare(
    'SELECT c.id, c.name, c.color, c.icon,
            COUNT(t.id) AS usage_count,
            MAX(t.type) AS type_sample
     FROM categories c
     LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = c.user_id
     WHERE c.user_id = :user_id
     GROUP BY c.id, c.name, c.color, c.icon
     ORDER BY c.name ASC'
);
$listStmt->execute(['user_id' => $userId]);
$categories = $listStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $pageTitle = 'Categories — ' . APP_NAME;
    include __DIR__ . '/../includes/head.php';
    ?>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex-1 min-w-0">
        <?php include __DIR__ . '/../includes/topbar.php'; ?>

        <main class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Categories</h1>
                        <p class="text-sm text-slate-500 mt-1">Kelola kategori transaksi Anda.</p>
                    </div>
                    <a
                        href="create.php"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition"
                    >
                        <span class="material-symbols-outlined text-[18px]">add</span>
                        Tambah Kategori
                    </a>
                </div>

                <?php if ($flashSuccess !== ''): ?>
                    <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                        <span class="material-symbols-outlined text-emerald-500 text-[18px] mt-0.5 shrink-0">check_circle</span>
                        <span><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($flashError !== ''): ?>
                    <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                        <span class="material-symbols-outlined text-rose-500 text-[18px] mt-0.5 shrink-0">error</span>
                        <span><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                <?php endif; ?>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-100 text-slate-700">
                            <tr>
                                <th class="text-left px-4 py-3 font-semibold w-16">No</th>
                                <th class="text-left px-4 py-3 font-semibold">Nama Kategori</th>
                                <th class="text-left px-4 py-3 font-semibold">Tipe</th>
                                <th class="text-left px-4 py-3 font-semibold">Deskripsi</th>
                                <th class="text-center px-4 py-3 font-semibold w-48">Aksi</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                            <?php if ($categories === []): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">Belum ada kategori.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <?php
                                    $catId = (int) $category['id'];
                                    $type = (string) ($category['type_sample'] ?? '');
                                    $typeLabel = $type !== '' ? $type : '-';
                                    $description = [];
                                    if (!empty($category['color'])) {
                                        $description[] = 'Warna: ' . (string) $category['color'];
                                    }
                                    if (!empty($category['icon'])) {
                                        $description[] = 'Ikon: ' . (string) $category['icon'];
                                    }
                                    $description[] = 'Dipakai: ' . (string) ((int) $category['usage_count']) . ' transaksi';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-4 py-3"><?= $index + 1; ?></td>
                                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars((string) $category['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars(implode(' • ', $description), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="edit.php?id=<?= $catId; ?>" class="inline-flex items-center rounded-md border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">Edit</a>
                                                <form method="POST" action="index.php" onsubmit="return confirm('Yakin ingin menghapus kategori ini?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $catId; ?>">
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 transition">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
